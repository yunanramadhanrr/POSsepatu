<?php
$title = 'Detail Retur Penjualan';
$currentRouteKey = 'returns.index';
ob_start();
?>

<a href="<?= url('/returns/sales') ?>" class="btn btn-sm btn-outline-secondary mb-3">&larr; Kembali</a>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
            <div>
                <p class="mb-0"><strong>No. Retur:</strong> <?= e($return['return_number']) ?></p>
                <p class="mb-0"><strong>Invoice Asal:</strong> <?= e($return['invoice_number']) ?></p>
                <p class="mb-0"><strong>Tanggal:</strong> <?= format_tanggal($return['return_date'], 'd-m-Y') ?></p>
            </div>
            <div class="text-end">
                <p class="mb-0"><strong>Pelanggan:</strong> <?= e($return['customer_name'] ?? '-') ?></p>
                <p class="mb-0"><strong>Diproses oleh:</strong> <?= e($return['user_name']) ?></p>
            </div>
        </div>

        <table class="table table-sm">
            <thead><tr><th>Produk</th><th>Varian</th><th class="text-end">Qty</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th></tr></thead>
            <tbody>
                <?php foreach ($return['items'] as $item): ?>
                    <tr>
                        <td><?= e($item['product_name']) ?></td>
                        <td><?= e($item['size']) ?>/<?= e($item['color']) ?></td>
                        <td class="text-end"><?= (int) $item['qty'] ?></td>
                        <td class="text-end"><?= format_rupiah($item['price']) ?></td>
                        <td class="text-end"><?= format_rupiah($item['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="row justify-content-end">
            <div class="col-md-4">
                <div class="d-flex justify-content-between fw-bold fs-5">
                    <span>Total Refund</span><span><?= format_rupiah($return['total']) ?></span>
                </div>
            </div>
        </div>

        <p class="mt-3"><strong>Alasan:</strong> <?= e($return['reason']) ?></p>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
