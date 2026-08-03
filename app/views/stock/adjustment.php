<?php
$title = 'Penyesuaian Stok';
$currentRouteKey = 'stock.index';
ob_start();
?>

<a href="<?= url('/stock') ?>" class="btn btn-sm btn-outline-secondary mb-3">&larr; Kembali</a>

<div class="card shadow-sm" style="max-width: 600px;">
    <div class="card-body">
        <h6 class="card-title">Penyesuaian Stok</h6>
        <p class="text-muted small">Set stok langsung ke nilai baru tertentu dengan alasan yang jelas (selisihnya otomatis tercatat di riwayat).</p>
        <form method="POST" action="<?= url('/stock/adjustment') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Produk</label>
                <select name="product_variant_id" id="variantSelect" class="form-select" required>
                    <option value="">- Pilih Produk -</option>
                    <?php foreach ($variants as $v): ?>
                        <option value="<?= $v['id'] ?>" data-stock="<?= (int) $v['stock'] ?>">
                            <?= e($v['product_name']) ?> (<?= e($v['size']) ?>/<?= e($v['color']) ?>) — stok saat ini: <?= (int) $v['stock'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Stok Baru</label>
                <input type="number" name="new_stock" id="newStockInput" min="0" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Alasan</label>
                <textarea name="reason" class="form-control" rows="2" required placeholder="Contoh: hasil hitung ulang manual, koreksi data awal, dsb."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Simpan Penyesuaian</button>
        </form>
    </div>
</div>

<script>
document.getElementById('variantSelect').addEventListener('change', function (e) {
    const opt = e.target.options[e.target.selectedIndex];
    const stock = opt ? opt.getAttribute('data-stock') : '';
    document.getElementById('newStockInput').value = stock || '';
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
