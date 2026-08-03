<?php
$title = 'Manajemen Stok';
$currentRouteKey = 'stock.index';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="mb-0">Riwayat Pergerakan Stok</h5>
    <?php if (user_can('stock.index', 'create')): ?>
        <div class="d-flex gap-2">
            <a href="<?= url('/stock/in') ?>" class="btn btn-sm btn-success">+ Stok Masuk</a>
            <a href="<?= url('/stock/out') ?>" class="btn btn-sm btn-danger">- Stok Keluar</a>
            <a href="<?= url('/stock/mutation') ?>" class="btn btn-sm btn-outline-secondary">Mutasi</a>
            <a href="<?= url('/stock/adjustment') ?>" class="btn btn-sm btn-outline-secondary">Penyesuaian</a>
            <a href="<?= url('/stock/opname') ?>" class="btn btn-sm btn-warning">Stock Opname</a>
        </div>
    <?php endif; ?>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="<?= url('/stock') ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Cari Produk/Barcode</label>
                <input type="text" name="search" class="form-control form-control-sm" value="<?= e($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Tipe</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach (['in' => 'Masuk', 'out' => 'Keluar', 'adjustment' => 'Penyesuaian', 'opname' => 'Opname'] as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($_GET['type'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($_GET['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($_GET['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-sm table-hover align-middle">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Produk</th>
                    <th>Tipe</th>
                    <th class="text-end">Qty</th>
                    <th>Referensi</th>
                    <th>Catatan</th>
                    <th>Oleh</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada riwayat pergerakan stok.</td></tr>
                <?php endif; ?>
                <?php foreach ($movements as $m): ?>
                    <?php
                        $badgeClass = match ($m['type']) {
                            'in' => 'bg-success',
                            'out' => 'bg-danger',
                            'adjustment' => 'bg-secondary',
                            'opname' => 'bg-warning text-dark',
                            default => 'bg-secondary',
                        };
                        $labelMap = ['in' => 'Masuk', 'out' => 'Keluar', 'adjustment' => 'Penyesuaian', 'opname' => 'Opname'];
                    ?>
                    <tr>
                        <td class="small"><?= format_tanggal($m['created_at']) ?></td>
                        <td><?= e($m['product_name']) ?> <span class="text-muted small">(<?= e($m['size']) ?>/<?= e($m['color']) ?>)</span></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= $labelMap[$m['type']] ?? e($m['type']) ?></span></td>
                        <td class="text-end"><?= (int) $m['qty'] ?></td>
                        <td class="small text-muted"><?= e($m['reference_type'] ?? '-') ?></td>
                        <td class="small"><?= e($m['note']) ?></td>
                        <td class="small"><?= e($m['user_name']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
