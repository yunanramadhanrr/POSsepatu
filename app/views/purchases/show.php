<?php
$title = 'Detail Pembelian';
$currentRouteKey = 'purchases.index';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a href="<?= url('/purchases') ?>" class="btn btn-sm btn-outline-secondary">&larr; Kembali</a>
    <button onclick="window.print()" class="btn btn-sm btn-primary">🖨️ Cetak Invoice</button>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between mb-4">
            <div>
                <h5 class="mb-0"><?= e(APP_NAME) ?></h5>
                <p class="text-muted small mb-0">Invoice Pembelian Barang</p>
            </div>
            <div class="text-end">
                <p class="mb-0"><strong>No. Invoice:</strong> <?= e($purchase['invoice_number']) ?></p>
                <p class="mb-0"><strong>Tanggal:</strong> <?= format_tanggal($purchase['purchase_date'], 'd-m-Y') ?></p>
                <p class="mb-0">
                    <strong>Status:</strong>
                    <span class="badge <?= $purchase['status'] === 'completed' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= e($purchase['status']) ?>
                    </span>
                </p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <h6>Supplier</h6>
                <p class="mb-0"><?= e($purchase['supplier_name']) ?></p>
                <p class="mb-0 text-muted small"><?= e($purchase['supplier_address']) ?></p>
                <p class="mb-0 text-muted small"><?= e($purchase['supplier_phone']) ?></p>
            </div>
            <div class="col-md-6 text-md-end">
                <h6>Dibuat oleh</h6>
                <p class="mb-0"><?= e($purchase['user_name']) ?></p>
            </div>
        </div>

        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Varian</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Harga</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['product_name']) ?></td>
                        <td><?= e($item['size']) ?> / <?= e($item['color']) ?></td>
                        <td class="text-end"><?= (int) $item['qty'] ?></td>
                        <td class="text-end"><?= format_rupiah($item['price']) ?></td>
                        <td class="text-end"><?= format_rupiah($item['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="row justify-content-end">
            <div class="col-md-4">
                <div class="d-flex justify-content-between">
                    <span>Subtotal</span><span><?= format_rupiah($purchase['subtotal']) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>PPN</span><span><?= format_rupiah($purchase['tax']) ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Diskon</span><span>- <?= format_rupiah($purchase['discount']) ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between fw-bold fs-5">
                    <span>Grand Total</span><span><?= format_rupiah($purchase['grand_total']) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
