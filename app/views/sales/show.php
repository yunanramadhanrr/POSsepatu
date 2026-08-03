<?php
$title = 'Detail Transaksi';
$currentRouteKey = 'sales.index';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a href="<?= url('/sales') ?>" class="btn btn-sm btn-outline-secondary">&larr; Kasir Baru</a>
    <a href="<?= url('/sales/' . $sale['id'] . '/receipt') ?>" target="_blank" class="btn btn-sm btn-primary">🖨️ Cetak Struk Thermal</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <div class="text-center mb-3">
            <h5 class="mb-0"><?= e(APP_NAME) ?></h5>
            <p class="text-muted small mb-0">Struk sederhana — desain thermal 58/80mm menyusul di Tahap 8</p>
        </div>

        <div class="d-flex justify-content-between mb-3">
            <div>
                <p class="mb-0"><strong>No. Invoice:</strong> <?= e($sale['invoice_number']) ?></p>
                <p class="mb-0"><strong>Kasir:</strong> <?= e($sale['user_name']) ?></p>
                <?php if ($sale['customer_name']): ?>
                    <p class="mb-0"><strong>Pelanggan:</strong> <?= e($sale['customer_name']) ?> (<?= e($sale['member_code']) ?>)</p>
                <?php endif; ?>
            </div>
            <div class="text-end">
                <p class="mb-0"><strong>Tanggal:</strong> <?= format_tanggal($sale['sale_date']) ?></p>
                <p class="mb-0">
                    <strong>Status:</strong>
                    <span class="badge <?= $sale['status'] === 'completed' ? 'bg-success' : 'bg-secondary' ?>"><?= e($sale['status']) ?></span>
                </p>
            </div>
        </div>

        <table class="table table-sm">
            <thead>
                <tr><th>Produk</th><th>Varian</th><th class="text-end">Qty</th><th class="text-end">Harga</th><th class="text-end">Diskon</th><th class="text-end">Subtotal</th></tr>
            </thead>
            <tbody>
                <?php foreach ($sale['items'] as $item): ?>
                    <tr>
                        <td><?= e($item['product_name']) ?></td>
                        <td><?= e($item['size']) ?>/<?= e($item['color']) ?></td>
                        <td class="text-end"><?= (int) $item['qty'] ?></td>
                        <td class="text-end"><?= format_rupiah($item['price']) ?></td>
                        <td class="text-end"><?= format_rupiah($item['discount']) ?></td>
                        <td class="text-end"><?= format_rupiah($item['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="row justify-content-end">
            <div class="col-md-5">
                <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= format_rupiah($sale['subtotal']) ?></span></div>
                <div class="d-flex justify-content-between"><span>Diskon</span><span>- <?= format_rupiah($sale['discount_total']) ?></span></div>
                <div class="d-flex justify-content-between"><span>Pajak</span><span><?= format_rupiah($sale['tax']) ?></span></div>
                <hr>
                <div class="d-flex justify-content-between fw-bold fs-5"><span>Grand Total</span><span><?= format_rupiah($sale['grand_total']) ?></span></div>

                <?php if ($sale['status'] === 'completed'): ?>
                    <hr>
                    <p class="mb-1 fw-semibold">Pembayaran:</p>
                    <?php foreach ($sale['payments'] as $p): ?>
                        <div class="d-flex justify-content-between small">
                            <span><?= e($p['payment_method_name']) ?><?= $p['reference_number'] ? ' (' . e($p['reference_number']) . ')' : '' ?></span>
                            <span><?= format_rupiah($p['amount']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="d-flex justify-content-between"><span>Total Dibayar</span><span><?= format_rupiah($sale['paid_amount']) ?></span></div>
                    <div class="d-flex justify-content-between fw-bold"><span>Kembalian</span><span><?= format_rupiah($sale['change_amount']) ?></span></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($sale['note']): ?>
            <p class="text-muted small mt-3">Catatan: <?= e($sale['note']) ?></p>
        <?php endif; ?>

        <p class="text-center text-muted small mt-4">Terima kasih atas kunjungan Anda 🙏</p>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
