<?php
$title = 'Laporan Stok';
$currentRouteKey = 'reports.index';
ob_start();
?>

<a href="<?= url('/reports') ?>" class="btn btn-sm btn-outline-secondary mb-3 no-print">&larr; Kembali</a>
<h5 class="mb-3">Laporan Stok</h5>

<div class="d-flex justify-content-end gap-2 mb-3 no-print">
    <a href="<?= url('/reports/stock?export=csv') ?>" class="btn btn-sm btn-outline-success">📥 Export Excel (CSV)</a>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">🖨️ Print / Simpan PDF</button>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="text-muted small">Total Nilai Stok (berdasarkan harga modal)</div>
        <div class="fs-4 fw-bold"><?= format_rupiah($totalNilai) ?></div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-sm table-hover">
            <thead><tr><th>Produk</th><th>Varian</th><th>Barcode</th><th class="text-end">Stok</th><th class="text-end">Min. Stok</th><th class="text-end">Nilai Stok</th></tr></thead>
            <tbody>
                <?php if (empty($detail)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data produk.</td></tr>
                <?php endif; ?>
                <?php foreach ($detail as $r): ?>
                    <tr class="<?= $r['stock'] <= $r['min_stock'] ? 'table-warning' : '' ?>">
                        <td><?= e($r['product_name']) ?></td>
                        <td><?= e($r['size']) ?>/<?= e($r['color']) ?></td>
                        <td><code><?= e($r['barcode']) ?></code></td>
                        <td class="text-end"><?= (int) $r['stock'] ?></td>
                        <td class="text-end"><?= (int) $r['min_stock'] ?></td>
                        <td class="text-end"><?= format_rupiah($r['nilai_stok']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
