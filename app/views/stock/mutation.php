<?php
$title = 'Mutasi Stok';
$currentRouteKey = 'stock.index';
ob_start();
?>

<a href="<?= url('/stock') ?>" class="btn btn-sm btn-outline-secondary mb-3">&larr; Kembali</a>

<div class="card shadow-sm" style="max-width: 700px;">
    <div class="card-body">
        <h6 class="card-title">Mutasi Stok</h6>
        <p class="text-muted small">Pindahkan stok dari satu varian ke varian lain, misalnya untuk mengoreksi kesalahan input ukuran/warna saat pembelian.</p>
        <form method="POST" action="<?= url('/stock/mutation') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Dari Produk (asal)</label>
                <select name="from_variant_id" class="form-select" required>
                    <option value="">- Pilih Produk -</option>
                    <?php foreach ($variants as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= e($v['product_name']) ?> (<?= e($v['size']) ?>/<?= e($v['color']) ?>) — stok: <?= (int) $v['stock'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Ke Produk (tujuan)</label>
                <select name="to_variant_id" class="form-select" required>
                    <option value="">- Pilih Produk -</option>
                    <?php foreach ($variants as $v): ?>
                        <option value="<?= $v['id'] ?>"><?= e($v['product_name']) ?> (<?= e($v['size']) ?>/<?= e($v['color']) ?>) — stok: <?= (int) $v['stock'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Qty</label>
                <input type="number" name="qty" min="1" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Catatan</label>
                <textarea name="note" class="form-control" rows="2" placeholder="Contoh: koreksi salah input ukuran saat pembelian"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Proses Mutasi</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
