<?php
$title = 'Stok Keluar';
$currentRouteKey = 'stock.index';
ob_start();
?>

<a href="<?= url('/stock') ?>" class="btn btn-sm btn-outline-secondary mb-3">&larr; Kembali</a>

<div class="card shadow-sm" style="max-width: 600px;">
    <div class="card-body">
        <h6 class="card-title">Catat Stok Keluar Manual</h6>
        <p class="text-muted small">Gunakan untuk barang rusak, hilang, atau dipakai internal (penjualan resmi otomatis tercatat lewat menu Kasir).</p>
        <form method="POST" action="<?= url('/stock/out') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Produk</label>
                <select name="product_variant_id" class="form-select" required>
                    <option value="">- Pilih Produk -</option>
                    <?php foreach ($variants as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= e($v['product_name']) ?> (<?= e($v['size']) ?>/<?= e($v['color']) ?>) — stok saat ini: <?= (int) $v['stock'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Qty Keluar</label>
                <input type="number" name="qty" min="1" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Catatan</label>
                <textarea name="note" class="form-control" rows="2" placeholder="Contoh: barang rusak, hilang, dipakai display, dsb." required></textarea>
            </div>
            <button type="submit" class="btn btn-danger">Simpan Stok Keluar</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
