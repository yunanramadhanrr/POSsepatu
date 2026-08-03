<?php
$title = $type === 'best' ? 'Produk Terlaris' : 'Produk Tidak Laku';
$currentRouteKey = 'reports.index';
$reportPath = '/reports/products';
$extraQuery = ['type' => $type];
ob_start();
?>

<a href="<?= url('/reports') ?>" class="btn btn-sm btn-outline-secondary mb-3 no-print">&larr; Kembali</a>
<h5 class="mb-3"><?= $type === 'best' ? '⭐ Produk Terlaris' : '📉 Produk Tidak Laku' ?></h5>

<div class="mb-3 no-print">
    <a href="<?= url('/reports/products?type=best') ?>" class="btn btn-sm <?= $type === 'best' ? 'btn-primary' : 'btn-outline-primary' ?>">Terlaris</a>
    <a href="<?= url('/reports/products?type=worst') ?>" class="btn btn-sm <?= $type === 'worst' ? 'btn-primary' : 'btn-outline-primary' ?>">Tidak Laku</a>
</div>

<?php require __DIR__ . '/_filter.php'; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if ($type === 'best'): ?>
            <table class="table table-sm table-hover">
                <thead><tr><th>Produk</th><th>Varian</th><th class="text-end">Qty Terjual</th><th class="text-end">Omzet</th></tr></thead>
                <tbody>
                    <?php if (empty($detail)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada data penjualan pada periode ini.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($detail as $r): ?>
                        <tr>
                            <td><?= e($r['product_name']) ?></td>
                            <td><?= e($r['size']) ?>/<?= e($r['color']) ?></td>
                            <td class="text-end fw-semibold"><?= (int) $r['total_qty'] ?></td>
                            <td class="text-end"><?= format_rupiah($r['total_omzet']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <table class="table table-sm table-hover">
                <thead><tr><th>Produk</th><th>Varian</th><th class="text-end">Stok Saat Ini</th></tr></thead>
                <tbody>
                    <?php if (empty($detail)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">Semua produk aktif terjual pada periode ini. 🎉</td></tr>
                    <?php endif; ?>
                    <?php foreach ($detail as $r): ?>
                        <tr>
                            <td><?= e($r['product_name']) ?></td>
                            <td><?= e($r['size']) ?>/<?= e($r['color']) ?></td>
                            <td class="text-end"><?= (int) $r['stock'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
