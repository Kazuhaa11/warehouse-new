<?php
namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Libraries\Auth;
use Config\Database;



class PeminjamanApi extends BaseApiController
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    private function generateNumber(): string
    {
        $prefix = 'PJ-' . date('Ymd') . '-';
        $sql = "SELECT MAX(CAST(SUBSTRING(no_nota, LENGTH(?) + 1) AS UNSIGNED)) AS last_no
                FROM peminjaman
                WHERE no_nota LIKE ?";
        $q = $this->db->query($sql, [$prefix, $prefix . '%']);
        if ($q === false) {
            $err = $this->db->error();
            throw new \RuntimeException('SQL error generateNumber: ' . ($err['message'] ?? 'unknown'));
        }
        $row = $q->getRowArray() ?? [];
        $next = (int) ($row['last_no'] ?? 0) + 1;
        return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    public function index()
    {
        $q = trim((string) $this->request->getGet('q'));
        $plant = trim((string) $this->request->getGet('plant'));
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = min(200, max(1, (int) ($this->request->getGet('per_page') ?? 50)));

        $b = $this->db->table('peminjaman p')
            ->select("
                p.id,
                p.no_nota AS nomor,
                DATE(p.borrow_date) AS tanggal,
                p.status,
                p.note,
                p.peminjam_id,
                u.username AS peminjam_username,
                GROUP_CONCAT(DISTINCT b.plant ORDER BY b.plant SEPARATOR ',') AS plants
            ", false)
            ->join('users u', 'u.id = p.peminjam_id', 'left')
            ->join('peminjaman_items pi', 'pi.peminjaman_id = p.id', 'left')
            ->join('barang b', 'b.id = pi.barang_id', 'left');

        $role = Auth::role();
        $uid = Auth::id();

        if ($role === 'mobile' && $uid) {
            $b->where('p.peminjam_id', $uid);
        }

        if ($q !== '') {
            $b->groupStart()
                ->like('p.no_nota', $q)
                ->orLike('p.note', $q)
                ->orLike('u.username', $q)
                ->groupEnd();
        }
        if ($plant !== '') {
            $b->where('b.plant', $plant);
        }

        $count = clone $b;
        $total = (int) ($count->select('COUNT(DISTINCT p.id) AS c', false)->get()->getRow('c') ?? 0);

        $rows = $b->groupBy('p.id')
            ->orderBy('p.id', 'DESC')
            ->limit($perPage, ($page - 1) * $perPage)
            ->get()->getResultArray();

        return $this->ok($rows, [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int) ceil($total / ($perPage ?: 1)),
        ]);
    }

    public function show($id)
    {
        $id = (int) $id;

        $row = $this->db->table('peminjaman p')
            ->select('
                p.id,
                p.no_nota AS nomor,
                DATE(p.borrow_date) AS tanggal,
                DATE(p.due_date)    AS jatuh_tempo,
                p.status,
                p.note,
                p.peminjam_id,
                u.username AS peminjam_username,
                p.created_at,
                p.updated_at
            ')
            ->join('users u', 'u.id = p.peminjam_id', 'left')
            ->where('p.id', $id)
            ->get()->getRowArray();

        if (!$row) {
            return $this->failMsg('Data tidak ditemukan', 404);
        }

        $role = Auth::role();
        $uid = Auth::id();

        if ($role === 'mobile' && $uid && (int) $row['peminjam_id'] !== $uid) {
            return $this->failMsg('Forbidden: tidak boleh akses data orang lain', 403);
        }

        $plants = $this->db->table('peminjaman_items pi')
            ->select('GROUP_CONCAT(DISTINCT b.plant ORDER BY b.plant SEPARATOR ",") AS plants', false)
            ->join('barang b', 'b.id = pi.barang_id', 'left')
            ->where('pi.peminjaman_id', $id)
            ->get()->getRowArray();
        $row['plants'] = $plants['plants'] ?? null;

        $items = $this->db->table('peminjaman_items pi')
            ->select('pi.id, pi.barang_id, pi.material, pi.requested_qty AS qty, pi.uom, pi.storage_location')
            ->where('pi.peminjaman_id', $id)
            ->get()->getResultArray();

        return $this->ok(['header' => $row, 'items' => $items]);
    }

    public function create()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->fail('Unauthorized', 401);
            }

            if (($user['role'] ?? null) !== 'mobile') {
                return $this->fail('Hanya user dengan role "mobile" yang boleh membuat peminjaman', 403);
            }

            $peminjamId = (int) $user['id'];
            $p = $this->request->getJSON(true) ?? [];

            $tanggal = $p['tanggal'] ?? date('Y-m-d');
            $dueDate = $p['due_date'] ?? null;
            $plant = $p['plant'] ?? null;
            $note = $p['note'] ?? null;
            $items = is_array($p['items'] ?? null) ? $p['items'] : [];

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
                return $this->fail('Format tanggal harus YYYY-MM-DD', 400);
            }
            if ($dueDate !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
                return $this->fail('Format due_date harus YYYY-MM-DD', 400);
            }
            if ($plant !== null && !in_array($plant, ['1200', '1300'], true)) {
                return $this->fail('Plant hanya 1200 atau 1300', 400);
            }

            if (empty($items)) {
                return $this->fail('Minimal 1 item harus dikirim', 400);
            }

            $this->db->transStart();

            $no = $this->generateNumber();
            $this->db->table('peminjaman')->insert([
                'no_nota' => $no,
                'peminjam_id' => $peminjamId,
                'borrow_date' => $tanggal . ' 00:00:00',
                'due_date' => $dueDate ? $dueDate . ' 00:00:00' : null,
                'status' => 'draft',
                'note' => $note,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $peminjamanIdNew = (int) $this->db->insertID();
            $rows = [];

            foreach ($items as $i => $it) {
                $scan = $it['scan'] ?? null;
                $barangId = isset($it['barang_id']) ? (int) $it['barang_id'] : null;
                $qty = (float) ($it['qty'] ?? $it['quantity'] ?? 0);
                $plantItem = $it['plant'] ?? $plant;
                $storLoc = $it['storage_location'] ?? null;
                $storageId = isset($it['storage_id']) ? (int) $it['storage_id'] : null;

                if ($qty <= 0) {
                    throw new \InvalidArgumentException("Item ke-" . ($i + 1) . ": qty harus > 0");
                }
                if ($plantItem !== null && !in_array($plantItem, ['1200', '1300'], true)) {
                    throw new \InvalidArgumentException("Item ke-" . ($i + 1) . ": plant hanya 1200/1300");
                }

                $barang = $this->resolveBarang($scan, $barangId);
                if (!$barang) {
                    throw new \InvalidArgumentException("Item ke-" . ($i + 1) . ": barang tidak ditemukan");
                }

                $qtyUnrestricted = (float) ($barang['qty_unrestricted'] ?? 0);
                log_message('debug', 'Barang ditemukan: ID=' . $barang['id'] .
                    ' material=' . $barang['material'] .
                    ' stok=' . $qtyUnrestricted .
                    ' qtyReq=' . $qty);

                if ($qtyUnrestricted <= 0) {
                    throw new \InvalidArgumentException("Item ke-" . ($i + 1) . ": stok barang kosong (qty_unrestricted = 0)");
                }
                if ($qty > $qtyUnrestricted) {
                    throw new \InvalidArgumentException("Item ke-" . ($i + 1) . ": stok tidak mencukupi, sisa $qtyUnrestricted");
                }
                $this->db->table('barang')
                    ->where('id', (int) $barang['id'])
                    ->set('qty_unrestricted', 'qty_unrestricted - ' . $qty, false)
                    ->update();

                $rows[] = [
                    'peminjaman_id' => $peminjamanIdNew,
                    'barang_id' => (int) $barang['id'],
                    'material' => $barang['material'],
                    'requested_qty' => $qty,
                    'approved_qty' => 0,
                    'uom' => $barang['base_unit_of_measure'] ?? 'EA',
                    'storage_location' => $storLoc ?: ($barang['storage_location'] ?? null),
                    'storage_id' => $storageId ?: ($barang['storage_id'] ?? null),
                    'note' => $it['note'] ?? null,
                ];
            }

            if (!empty($rows)) {
                $this->db->table('peminjaman_items')->insertBatch($rows);
            }

            $this->db->transComplete();
            if (!$this->db->transStatus()) {
                return $this->fail('Transaksi gagal disimpan', 500);
            }

            $header = $this->db->table('peminjaman p')
                ->select('
                p.id,
                p.no_nota,
                DATE(p.borrow_date) AS tanggal,
                DATE(p.due_date)    AS jatuh_tempo,
                p.status,
                p.note,
                p.peminjam_id,
                u.username AS peminjam_username,
                u.username AS peminjam
            ')
                ->join('users u', 'u.id = p.peminjam_id', 'left')
                ->where('p.id', $peminjamanIdNew)
                ->get()->getRowArray();

            $detail = $this->db->table('peminjaman_items pi')
                ->select('pi.id, pi.barang_id, pi.material, pi.requested_qty AS qty, pi.uom, pi.storage_location')
                ->where('pi.peminjaman_id', $peminjamanIdNew)
                ->get()->getResultArray();

            return $this->respond([
                'success' => true,
                'data' => [
                    'header' => $header,
                    'items' => $detail,
                ],
            ]);

        } catch (\InvalidArgumentException $e) {
            $this->db->transRollback();
            log_message('warning', '[PeminjamanApi] ' . $e->getMessage());
            return $this->fail($e->getMessage(), 400);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', '[PeminjamanApi] ' . $e->getMessage());
            return $this->failServerError('Terjadi kesalahan pada server.');
        }
    }

    private function resolveBarang(?string $scan, ?int $barangId): ?array
    {
        $builder = $this->db->table('barang')
            ->select('id, material, base_unit_of_measure, storage_location, storage_id, qty_unrestricted');

        if ($barangId && $barangId > 0) {
            $row = $builder->where('id', $barangId)->get()->getRowArray();
            if ($row) {
                return $row;
            }
        }

        if (!empty($scan)) {
            $row = $builder->where('material', $scan)->get()->getRowArray();
            if ($row) {
                return $row;
            }
        }

        return null;
    }

    private function setStatus(int $id, string $status)
    {
        $allowed = ['draft', 'submitted', 'approved', 'rejected', 'loaned', 'returned', 'lost'];
        if (!in_array($status, $allowed, true)) {
            return $this->failMsg('Status tidak valid', 422);
        }

        $exists = $this->db->table('peminjaman')->select('id')->where('id', $id)->get()->getRowArray();
        if (!$exists) {
            return $this->failMsg('Data tidak ditemukan', 404);
        }

        $this->db->table('peminjaman')->where('id', $id)->update(['status' => $status]);
        return $this->ok(['id' => $id, 'status' => $status]);
    }

    public function setSubmitted($id)
    {
        return $this->setStatus((int) $id, 'submitted');
    }

    public function setApproved($id)
    {
        return $this->setStatus((int) $id, 'approved');
    }

    public function setReturned($id)
    {
        return $this->setStatus((int) $id, 'returned');
    }

    public function setRejected($id)
    {
        return $this->setStatus((int) $id, 'rejected');
    }

    public function reportPdf()
    {
        try {
            helper('peminjaman_report');

            $mode = $this->request->getGet('mode') ?: 'range';
            $fromDate = $this->request->getGet('from_date');
            $toDate = $this->request->getGet('to_date');
            $month = (int) ($this->request->getGet('month') ?? 0);
            $year = (int) ($this->request->getGet('year') ?? 0);
            $plant = $this->request->getGet('plant');
            $sortBy = $this->request->getGet('sort_by') ?: 'tanggal';
            $sortDir = strtolower($this->request->getGet('sort_dir') ?: 'desc');

            $dl = (int) $this->request->getGet('dl') === 1;
            $inline = !$dl;

            if (!in_array($sortBy, ['tanggal', 'no_nota'], true))
                $sortBy = 'tanggal';
            if (!in_array($sortDir, ['asc', 'desc'], true))
                $sortDir = 'desc';

            $b = $this->db->table('peminjaman p')
                ->select("
                p.id,
                p.no_nota,
                DATE(p.borrow_date) AS tanggal,
                DATE(p.due_date)    AS due_date,
                p.status,
                p.note,
                u.username AS peminjam_username,
                GROUP_CONCAT(DISTINCT b.plant ORDER BY b.plant SEPARATOR ',') AS plants
            ", false)
                ->join('users u', 'u.id = p.peminjam_id', 'left')
                ->join('peminjaman_items pi', 'pi.peminjaman_id = p.id', 'left')
                ->join('barang b', 'b.id = pi.barang_id', 'left');

            if ($mode === 'range') {
                if (
                    !$fromDate || !$toDate
                    || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)
                    || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)
                ) {
                    return $this->failMsg('Rentang tanggal tidak valid (YYYY-MM-DD).', 422);
                }
                $b->where('DATE(p.borrow_date) >=', $fromDate)
                    ->where('DATE(p.borrow_date) <=', $toDate);
            } else {
                if ($month < 1 || $month > 12 || $year < 1970) {
                    return $this->failMsg('Bulan/tahun tidak valid.', 422);
                }
                $b->where('MONTH(p.borrow_date)', $month)
                    ->where('YEAR(p.borrow_date)', $year);
            }

            if (!empty($plant))
                $b->where('b.plant', $plant);

            $sortBy === 'no_nota'
                ? $b->orderBy('p.no_nota', $sortDir)
                : $b->orderBy('p.borrow_date', $sortDir)->orderBy('p.id', $sortDir);

            $headers = $b->groupBy('p.id')->get()->getResultArray();

            $itemsByHeader = [];
            if ($headers) {
                $ids = array_column($headers, 'id');
                $rows = $this->db->table('peminjaman_items pi')
                    ->select('pi.peminjaman_id, pi.material, pi.requested_qty, pi.uom, pi.storage_location, b.material_description')
                    ->join('barang b', 'b.id = pi.barang_id', 'left')
                    ->whereIn('pi.peminjaman_id', $ids)
                    ->orderBy('pi.peminjaman_id', 'ASC')->orderBy('pi.id', 'ASC')
                    ->get()->getResultArray();
                foreach ($rows as $r) {
                    $itemsByHeader[$r['peminjaman_id']][] = $r;
                }
            }

            $html = peminjaman_report_build_html(
                $headers,
                $itemsByHeader,
                compact('mode', 'fromDate', 'toDate', 'month', 'year', 'plant', 'sortBy', 'sortDir')
            );

            return peminjaman_report_send_pdf($html, 'laporan-peminjaman.pdf', $inline);

        } catch (\Throwable $e) {
            return $this->failMsg('Gagal membuat laporan', 500, $e->getMessage());
        }
    }


}
