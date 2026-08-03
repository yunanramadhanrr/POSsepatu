<?php
$title = 'Laporan Pembelian';
$currentRouteKey = 'reports.index';
$reportPath = '/reports/purchases';
ob_start();
?>

<a href="<?= url('/reports') ?>" class="btn btn-sm btn-outline-secondary mb-3 no-print">&larr; Kembali</a>
<h5 class="mb-3">Laporan Pembelian</h5>

<?php require __DIR__ . '/_filter.php'; ?>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body">
        <div class="text-muted small">Total Transaksi</div><div class="fs-5 fw-bold"><?= (int) $summary['total_transaksi'] ?></div>
    </div></div></div>
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body">
        <div class="text-muted small">Total Pembelian</div><div class="fs-5 fw-bold"><?= format_rupiah($summary['total_pembelian']) ?></div>
    </div></div></div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-sm table-hover">
            <thead><tr><th>Invoice</th><th>Tanggal</th><th>Supplier</th><th>Dibuat Oleh</th><th class="text-end">Subtotal</th><th class="text-end">Pajak</th><th class="text-end">Diskon</th><th class="text-end">Grand Total</th></tr></thead>
            <tbody>
                <?php if (empty($detail)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data pada periode ini.</td></tr>
                <?php endif; ?>
                <?php foreach ($detail as $r): ?>
                    <tr>
                        <td><code><?= e($r['invoice_number']) ?></code></td>
                        <td><?= format_tanggal($r['purchase_date'], 'd-m-Y') ?></td>
                        <td><?= e($r['supplier_name']) ?></td>
                        <td><?= e($r['user_name']) ?></td>
                        <td class="text-end"><?= format_rupiah($r['subtotal']) ?></td>
                        <td class="text-end"><?= format_rupiah($r['tax']) ?></td>
                        <td class="text-end"><?= format_rupiah($r['discount']) ?></td>
                        <td class="text-end fw-semibold"><?= format_rupiah($r['grand_total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
