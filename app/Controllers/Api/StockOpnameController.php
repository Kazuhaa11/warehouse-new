<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\StockOpnameItemModel;
use App\Models\StockOpnameSessionModel;
use App\Libraries\StockOpnameExcel;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StockOpnameController extends BaseController
{
    use \CodeIgniter\API\ResponseTrait;

    protected $helpers = ['stockopname'];

    /** @var \CodeIgniter\HTTP\IncomingRequest */
    protected $request;

    protected StockOpnameSessionModel $sessions;
    protected StockOpnameItemModel $items;

    protected string $barangTable = 'barang';

    public function __construct()
    {
        $this->sessions = new StockOpnameSessionModel();
        $this->items = new StockOpnameItemModel();
    }

    public function index()
    {
        $data = $this->sessions->orderBy('id', 'DESC')->findAll();
        return $this->respond(['success' => true, 'data' => $data]);
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? $this->request->getPost();
        $code = $this->sessions->nextCode();

        $uid = auth()->id();
        if (!$uid) {
            return $this->failUnauthorized('Unauthorized');
        }

        $row = [
            'code' => $code,
            'scheduled_at' => $payload['scheduled_at'] ?? null,
            'note' => $payload['note'] ?? null,
            'created_by' => (int) $uid,
        ];

        if (!$this->sessions->insert($row)) {
            return $this->fail(['message' => 'Gagal membuat sesi', 'errors' => $this->sessions->errors()], 422);
        }

        $created = $this->sessions->find($this->sessions->getInsertID());
        return $this->respondCreated(['success' => true, 'data' => $created]);
    }

    public function show($id)
    {
        $sess = $this->sessions->find($id);
        if (!$sess)
            return $this->failNotFound('Sesi tidak ditemukan');

        $items = $this->items->where('session_id', (int) $id)->orderBy('diff_qty', 'DESC')->findAll();
        $summary = buildSummary($items);

        return $this->respond([
            'success' => true,
            'data' => [
                'session' => $sess,
                'items' => $items,
                'summary' => $summary,
            ],
        ]);
    }

    public function items($id)
    {
        if (!$this->sessions->find($id)) {
            return $this->failNotFound('Sesi tidak ditemukan');
        }

        // query params
        $q = trim((string) $this->request->getGet('q'));
        $plant = trim((string) $this->request->getGet('plant'));
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $per = min(100, max(1, (int) ($this->request->getGet('per_page') ?? 25)));
        $sort = (string) ($this->request->getGet('sort') ?? 'diff_desc');

        $base = $this->items->builder()->where('session_id', (int) $id);

        if ($plant !== '') {
            $base->where('plant', $plant);
        }

        if ($q !== '') {
            $base->groupStart()
                ->like('material', $q)
                ->orLike('material_description', $q)
                ->orLike('storage_location', $q)
                ->groupEnd();
        }

        $total = (clone $base)->countAllResults();

        switch ($sort) {
            case 'diff_asc':
                $base->orderBy('(counted_qty - (qty_unrestricted + qty_transit_and_transfer + qty_blocked))', 'ASC', false);
                break;
            case 'counted_asc':
                $base->orderBy('counted_qty', 'ASC');
                break;
            case 'counted_desc':
                $base->orderBy('counted_qty', 'DESC');
                break;
            default:
                $base->orderBy('(counted_qty - (qty_unrestricted + qty_transit_and_transfer + qty_blocked))', 'DESC', false);
        }

        $rows = $base->limit($per, ($page - 1) * $per)->get()->getResultArray();

        $plantsRows = $this->items->builder()
            ->select('plant')
            ->distinct()
            ->where('session_id', (int) $id)
            ->orderBy('plant', 'ASC')
            ->get()->getResultArray();

        $plants = array_values(array_filter(array_map(fn($r) => $r['plant'] ?? '', $plantsRows)));


        return $this->respond([
            'success' => true,
            'data' => $rows,
            'meta' => [
                'page' => $page,
                'per_page' => $per,
                'total' => $total,
                'total_pages' => (int) ceil($total / $per),
                'sort' => $sort,
                'q' => $q,
                'plant' => $plant,
                'plants' => $plants,
            ],
        ]);
    }


    public function storeItem($sessionId)
    {
        $sess = $this->sessions->find($sessionId);
        if (!$sess)
            return $this->failNotFound('Sesi tidak ditemukan');
        if (!empty($sess['finalized_at']))
            return $this->failForbidden('Sesi sudah final');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        [$u, $tt, $b, $material, $sloc, $barangIdResolved] = resolveSystemQty($data, $this->barangTable);

        $row = [
            'session_id' => (int) $sessionId,
            'barang_id' => $data['barang_id'] ?? ($barangIdResolved ?: null),
            'storage_id' => $data['storage_id'] ?? null,
            'material' => $data['material'] ?? $material,
            'material_description' => $data['material_description'] ?? null,
            'plant' => $data['plant'] ?? null,
            'material_group' => $data['material_group'] ?? null,
            'storage_location' => $data['storage_location'] ?? $sloc,
            'storage_location_desc' => $data['storage_location_desc'] ?? null,
            'df_stor_loc_level' => $data['df_stor_loc_level'] ?? null,
            'base_unit_of_measure' => $data['base_unit_of_measure'] ?? null,
            'material_type' => $data['material_type'] ?? null,
            'qty_unrestricted' => array_key_exists('qty_unrestricted', $data) ? (float) $data['qty_unrestricted'] : $u,
            'qty_transit_and_transfer' => array_key_exists('qty_transit_and_transfer', $data) ? (float) $data['qty_transit_and_transfer'] : $tt,
            'qty_blocked' => array_key_exists('qty_blocked', $data) ? (float) $data['qty_blocked'] : $b,
            'counted_qty' => (float) ($data['counted_qty'] ?? 0),
            'note' => $data['note'] ?? null,
        ];

        if (!$this->items->insert($row)) {
            return $this->fail(['message' => 'Gagal menyimpan item', 'errors' => $this->items->errors()], 422);
        }

        $created = $this->items->find($this->items->getInsertID());
        return $this->respondCreated(['success' => true, 'data' => $created]);
    }

    public function updateItem($sessionId, $itemId)
    {
        $sess = $this->sessions->find($sessionId);
        if (!$sess)
            return $this->failNotFound('Sesi tidak ditemukan');
        if (!empty($sess['finalized_at']))
            return $this->failForbidden('Sesi sudah final');

        $item = $this->items->find($itemId);
        if (!$item || (int) $item['session_id'] !== (int) $sessionId) {
            return $this->failNotFound('Item tidak ditemukan');
        }

        $data = $this->request->getJSON(true) ?? $this->request->getRawInput();

        foreach (['counted_qty', 'qty_unrestricted', 'qty_transit_and_transfer', 'qty_blocked'] as $k) {
            if (isset($data[$k]))
                $data[$k] = (float) $data[$k];
        }

        if (!$this->items->update($itemId, $data)) {
            return $this->fail(['message' => 'Gagal update item', 'errors' => $this->items->errors()], 422);
        }

        return $this->respond(['success' => true, 'data' => $this->items->find($itemId)]);
    }

    public function deleteItem($sessionId, $itemId)
    {
        $sess = $this->sessions->find($sessionId);
        if (!$sess)
            return $this->failNotFound('Sesi tidak ditemukan');
        if (!empty($sess['finalized_at']))
            return $this->failForbidden('Sesi sudah final');

        $item = $this->items->find($itemId);
        if (!$item || (int) $item['session_id'] !== (int) $sessionId) {
            return $this->failNotFound('Item tidak ditemukan');
        }

        $this->items->delete($itemId);
        return $this->respondDeleted(['success' => true]);
    }

    public function finalize($id)
    {
        $sess = $this->sessions->find($id);
        if (!$sess)
            return $this->failNotFound('Sesi tidak ditemukan');
        if (!empty($sess['finalized_at'])) {
            return $this->respond(['success' => true, 'message' => 'Sudah final']);
        }

        $this->sessions->update($id, ['finalized_at' => date('Y-m-d H:i:s')]);
        return $this->respond(['success' => true, 'message' => 'Sesi difinalkan']);
    }

    public function recap($id)
    {
        if (!$this->sessions->find($id))
            return $this->failNotFound('Sesi tidak ditemukan');
        $items = $this->items->where('session_id', (int) $id)->findAll();
        return $this->respond(['success' => true, 'data' => buildSummary($items)]);
    }

    public function importItems($sessionId)
    {
        $sess = $this->sessions->find($sessionId);
        if (!$sess)
            return $this->failNotFound('Sesi tidak ditemukan');
        if (!empty($sess['finalized_at']))
            return $this->failForbidden('Sesi sudah final');

        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return $this->failValidationErrors(['file' => 'File tidak valid']);
        }

        try {
            $lib = new StockOpnameExcel();
            [$ins, $skip, $errs] = $lib->import($file->getTempName(), (int) $sessionId, $this->barangTable);
        } catch (\Throwable $e) {
            return $this->fail(['message' => 'Gagal import', 'detail' => $e->getMessage()], 422);
        }

        return $this->respond([
            'success' => empty($errs),
            'inserted' => $ins,
            'skipped' => $skip,
            'errors' => $errs,
        ], empty($errs) ? 200 : 207);
    }

    public function export($id)
    {
        $sess = $this->sessions->find($id);
        if (!$sess) {
            return $this->failNotFound('Sesi tidak ditemukan');
        }

        try {
            $excel = new StockOpnameExcel();
            [$headers, $rows] = $excel->exportRows((int) $id);
            $ss = $excel->makeSpreadsheet($headers, $rows);

            @is_dir(WRITEPATH . 'exports') || @mkdir(WRITEPATH . 'exports', 0775, true);
            $file = WRITEPATH . 'exports/StockOpname_' . (int) $id . '_' . date('Ymd_His') . '.xlsx';

            $writer = new Xlsx($ss);
            $writer->save($file);

            return $this->response
                ->download($file, null)
                ->setFileName(basename($file));
        } catch (\Throwable $e) {
            return $this->fail(['message' => 'Gagal export', 'detail' => $e->getMessage()], 500);
        }
    }
}
