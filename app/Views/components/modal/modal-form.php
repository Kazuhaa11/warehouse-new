<?php
// Komponen modal dengan form, untuk tambah/edit data via API
// Variabel yang tersedia: 
$modalId = $modalId ?? 'modalForm';
$formId = $formId ?? ($modalId . 'Form');
$method = strtoupper($method ?? 'POST');
$submitText = $submitText ?? 'Simpan';
$allowedSizes = ['sm','md','lg','xl'];
$size = (isset($size) && in_array($size, $allowedSizes, true)) ? $size : 'md';

$fields = $fields ?? [];

$title = $title ?? 'Form';
$api   = $api   ?? '#';

$dialogClass = 'modal-dialog';
if ($size === 'sm')
    $dialogClass .= ' modal-sm';
if ($size === 'lg')
    $dialogClass .= ' modal-lg';
if ($size === 'xl')
    $dialogClass .= ' modal-xl';

$split = isset($split) && is_int($split) ? max(0, (int) $split) : (int) ceil(count($fields) / 2);
$leftFields = array_slice($fields, 0, $split);
$rightFields = array_slice($fields, $split);

$renderField = function ($f) use ($formId) {
    $name = $f['name'] ?? '';
    if ($name === '')
        return;
    $label = $f['label'] ?? ucfirst($name);
    $type = strtolower($f['type'] ?? 'text');
    $ph = $f['placeholder'] ?? '';
    $req = !empty($f['required']);
    $step = $f['step'] ?? null;
    $val = array_key_exists('value', $f) ? $f['value'] : null;
    $options = $f['options'] ?? [];
    $id = $formId . '_' . $name;
    ?>
    <div class="mb-2">
        <label class="form-label mb-1" for="<?= esc($id) ?>">
            <?= esc($label) ?>    <?= $req ? ' <span class="text-danger">*</span>' : '' ?>
        </label>

        <?php if ($type === 'textarea'): ?>
            <textarea class="form-control form-control-sm" id="<?= esc($id) ?>" name="<?= esc($name) ?>"
                placeholder="<?= esc($ph) ?>" <?= $req ? 'required' : '' ?> rows="3"><?= esc((string) ($val ?? '')) ?></textarea>

        <?php elseif ($type === 'select'): ?>
            <select class="form-select form-select-sm" id="<?= esc($id) ?>" name="<?= esc($name) ?>" <?= $req ? 'required' : '' ?>>
                <option value="">— Pilih —</option>
                <?php foreach ($options as $opt):
                    $ov = (string) ($opt['value'] ?? '');
                    $ol = $opt['label'] ?? $ov;
                    $selected = (string) ($val ?? '') !== '' && (string) $val === $ov ? 'selected' : '';
                    ?>
                    <option value="<?= esc($ov) ?>" <?= $selected ?>><?= esc($ol) ?></option>
                <?php endforeach; ?>
            </select>

        <?php else: /* text|number|date|... */ ?>
            <input type="<?= esc($type) ?>" class="form-control form-control-sm" id="<?= esc($id) ?>" name="<?= esc($name) ?>"
                placeholder="<?= esc($ph) ?>" value="<?= esc((string) ($val ?? '')) ?>" <?= $req ? 'required' : '' ?>
                <?= $type === 'number' && $step ? 'step="' . esc($step) . '"' : '' ?>>
        <?php endif; ?>
    </div>
    <?php
};
?>
<div class="modal fade" id="<?= esc($modalId) ?>" tabindex="-1" aria-labelledby="<?= esc($modalId) ?>Label"
    aria-hidden="true">
    <div class="<?= esc($dialogClass) ?> modal-dialog-centered">
        <div class="modal-content">
            <form id="<?= esc($formId) ?>" data-modal-form data-api="<?= esc($api) ?>"
                data-method="<?= esc(data: $method) ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="<?= esc($modalId) ?>Label"><?= esc($title) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-danger d-none small mb-2" data-role="error"></div>

                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <?php foreach ($leftFields as $f)
                                $renderField($f); ?>
                        </div>
                        <div class="col-12 col-lg-6">
                            <?php foreach ($rightFields as $f)
                                $renderField($f); ?>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-save me-1"></i> <?= esc($submitText) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>