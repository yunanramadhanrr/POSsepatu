<?php
$title = 'Stok Masuk';
$currentRouteKey = 'stock.index';
ob_start();
?>

<a href="<?= url('/stock') ?>" class="btn btn-sm btn-outline-secondary mb-3">&larr; Kembali</a>

<div class="card shadow-sm" style="max-width: 600px;">
    <div class="card-body">
        <h6 class="card-title">Catat Stok Masuk Manual</h6>
        <p class="text-muted small">Gunakan untuk stok awal, temuan barang, atau kasus di luar pembelian resmi (pembelian resmi otomatis tercatat lewat menu Pembelian).</p>
        <form method="POST" action="<?= url('/stock/in') ?>">
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
                <label class="form-label">Qty Masuk</label>
                <input type="number" name="qty" min="1" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Catatan</label>
                <textarea name="note" class="form-control" rows="2" placeholder="Contoh: stok awal toko, temuan barang, dsb."></textarea>
            </div>
            <button type="submit" class="btn btn-success">Simpan Stok Masuk</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
