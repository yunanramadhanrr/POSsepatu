<?php
$title = 'Retur Penjualan Baru';
$currentRouteKey = 'returns.index';
ob_start();
?>

<a href="<?= url('/returns/sales') ?>" class="btn btn-sm btn-outline-secondary mb-3">&larr; Kembali</a>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="<?= url('/returns/sales/create') ?>" class="d-flex gap-2">
            <input type="text" name="invoice" class="form-control" placeholder="Masukkan No. Invoice, contoh: INV-20260728-1234"
                   value="<?= e($invoice ?? '') ?>" required>
            <button type="submit" class="btn btn-primary">Cari</button>
        </form>
    </div>
</div>

<?php if ($sale): ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-3">
                <div>
                    <p class="mb-0"><strong>Invoice:</strong> <?= e($sale['invoice_number']) ?></p>
                    <p class="mb-0"><strong>Tanggal:</strong> <?= format_tanggal($sale['sale_date']) ?></p>
                </div>
                <div class="text-end">
                    <p class="mb-0"><strong>Pelanggan:</strong> <?= e($sale['customer_name'] ?? '-') ?></p>
                    <p class="mb-0"><strong>Kasir:</strong> <?= e($sale['user_name']) ?></p>
                </div>
            </div>

            <?php if (empty($returnableItems)): ?>
                <p class="text-muted">Seluruh item pada invoice ini sudah diretur sepenuhnya.</p>
            <?php else: ?>
                <form method="POST" action="<?= url('/returns/sales') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="sale_id" value="<?= (int) $sale['id'] ?>">

                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Varian</th>
                                <th>Sisa Bisa Diretur</th>
                                <th style="width: 120px;">Qty Retur</th>
                                <th>Harga Satuan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($returnableItems as $item): ?>
                                <tr>
                                    <td>
                                        <?= e($item['product_name']) ?>
                                        <input type="hidden" name="return_variant_id[]" value="<?= (int) $item['product_variant_id'] ?>">
                                        <input type="hidden" name="return_price[]" value="<?= e($item['unit_price']) ?>">
                                    </td>
                                    <td><?= e($item['size']) ?>/<?= e($item['color']) ?></td>
                                    <td><?= (int) $item['remaining_qty'] ?></td>
                                    <td>
                                        <input type="number" min="0" max="<?= (int) $item['remaining_qty'] ?>"
                                               name="return_qty[]" class="form-control form-control-sm" value="0">
                                    </td>
                                    <td><?= format_rupiah($item['unit_price']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <label class="form-label">Alasan Retur</label>
                    <textarea name="reason" class="form-control mb-3" rows="2" required placeholder="Contoh: Ukuran tidak sesuai, produk cacat, dsb."></textarea>

                    <button type="submit" class="btn btn-primary">Proses Retur</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
