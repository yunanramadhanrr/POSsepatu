<?php
$title = 'Stock Opname';
$currentRouteKey = 'stock.index';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= url('/stock') ?>" class="btn btn-sm btn-outline-secondary">&larr; Kembali</a>
    <?php if (user_can('stock.index', 'create')): ?>
        <a href="<?= url('/stock/opname/create') ?>" class="btn btn-warning btn-sm">+ Stock Opname Baru</a>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h6 class="card-title">Riwayat Sesi Stock Opname</h6>
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>No. Opname</th>
                    <th>Tanggal</th>
                    <th>Dilakukan oleh</th>
                    <th>Jumlah Item</th>
                    <th>Total Selisih (abs)</th>
                    <th style="width: 100px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($opnames)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada sesi stock opname.</td></tr>
                <?php endif; ?>
                <?php foreach ($opnames as $o): ?>
                    <tr>
                        <td><code><?= e($o['opname_number']) ?></code></td>
                        <td><?= format_tanggal($o['opname_date'], 'd-m-Y') ?></td>
                        <td><?= e($o['user_name']) ?></td>
                        <td><?= (int) $o['total_items'] ?></td>
                        <td><?= (int) $o['total_selisih'] ?></td>
                        <td class="text-end">
                            <a href="<?= url('/stock/opname/' . $o['id']) ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
