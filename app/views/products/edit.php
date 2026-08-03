<?php
$title = 'Edit Produk';
$currentRouteKey = 'products.index';
ob_start();
?>

<form method="POST" action="<?= url('/products/' . $product['id'] . '/update') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">Informasi Produk</h6>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Kode Produk</label>
                            <input type="text" class="form-control" value="<?= e($product['product_code']) ?>" readonly>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Nama Sepatu</label>
                            <input type="text" name="name" class="form-control" value="<?= e($product['name']) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Kategori</label>
                            <select name="category_id" class="form-select">
                                <option value="">- Pilih -</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $product['category_id'] == $c['id'] ? 'selected' : '' ?>>
                                        <?= e($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Brand</label>
                            <select name="brand_id" class="form-select">
                                <option value="">- Pilih -</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= $product['brand_id'] == $b['id'] ? 'selected' : '' ?>>
                                        <?= e($b['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">- Pilih -</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= $product['supplier_id'] == $s['id'] ? 'selected' : '' ?>>
                                        <?= e($s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <?php foreach (['Unisex', 'Pria', 'Wanita', 'Anak'] as $g): ?>
                                    <option value="<?= $g ?>" <?= $product['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                                <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Foto Produk</label>
                            <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            <?php if ($product['photo']): ?>
                                <img src="<?= e(UPLOAD_URL . $product['photo']) ?>" class="mt-2 rounded" width="60" height="60" style="object-fit: cover;">
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="2"><?= e($product['description']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0">Varian (Ukuran / Warna)</h6>
                        <button type="button" id="btnAddVariant" class="btn btn-sm btn-outline-primary">+ Tambah Varian</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="variantTable">
                            <thead>
                                <tr>
                                    <th>Ukuran</th>
                                    <th>Warna</th>
                                    <th>Barcode</th>
                                    <th>Harga Modal</th>
                                    <th>Harga Jual</th>
                                    <th>Diskon</th>
                                    <th>Pajak %</th>
                                    <th>Stok</th>
                                    <th>Min. Stok</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="variantTbody">
                                <?php foreach ($variants as $v): ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="variant_id[]" value="<?= (int) $v['id'] ?>">
                                            <input type="text" name="variant_size[]" class="form-control form-control-sm" value="<?= e($v['size']) ?>">
                                        </td>
                                        <td><input type="text" name="variant_color[]" class="form-control form-control-sm" value="<?= e($v['color']) ?>"></td>
                                        <td><input type="text" name="variant_barcode[]" class="form-control form-control-sm" value="<?= e($v['barcode']) ?>"></td>
                                        <td><input type="number" step="0.01" min="0" name="variant_cost_price[]" class="form-control form-control-sm" value="<?= e($v['cost_price']) ?>"></td>
                                        <td><input type="number" step="0.01" min="0" name="variant_sell_price[]" class="form-control form-control-sm" value="<?= e($v['sell_price']) ?>"></td>
                                        <td><input type="number" step="0.01" min="0" name="variant_discount[]" class="form-control form-control-sm" value="<?= e($v['discount']) ?>"></td>
                                        <td><input type="number" step="0.01" min="0" name="variant_tax_percent[]" class="form-control form-control-sm" value="<?= e($v['tax_percent']) ?>"></td>
                                        <td><input type="number" min="0" name="variant_stock[]" class="form-control form-control-sm" value="<?= e($v['stock']) ?>"></td>
                                        <td><input type="number" min="0" name="variant_min_stock[]" class="form-control form-control-sm" value="<?= e($v['min_stock']) ?>"></td>
                                        <td><button type="button" class="btn btn-sm btn-outline-danger btnRemoveVariant">&times;</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-2">Simpan Perubahan</button>
                    <a href="<?= url('/products') ?>" class="btn btn-outline-secondary w-100">Batal</a>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="variantRowTemplate">
    <tr>
        <td>
            <input type="hidden" name="variant_id[]" value="">
            <input type="text" name="variant_size[]" class="form-control form-control-sm" placeholder="40">
        </td>
        <td><input type="text" name="variant_color[]" class="form-control form-control-sm" placeholder="Hitam"></td>
        <td><input type="text" name="variant_barcode[]" class="form-control form-control-sm" placeholder="opsional"></td>
        <td><input type="number" step="0.01" min="0" name="variant_cost_price[]" class="form-control form-control-sm" value="0"></td>
        <td><input type="number" step="0.01" min="0" name="variant_sell_price[]" class="form-control form-control-sm" value="0"></td>
        <td><input type="number" step="0.01" min="0" name="variant_discount[]" class="form-control form-control-sm" value="0"></td>
        <td><input type="number" step="0.01" min="0" name="variant_tax_percent[]" class="form-control form-control-sm" value="0"></td>
        <td><input type="number" min="0" name="variant_stock[]" class="form-control form-control-sm" value="0"></td>
        <td><input type="number" min="0" name="variant_min_stock[]" class="form-control form-control-sm" value="5"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btnRemoveVariant">&times;</button></td>
    </tr>
</template>

<script>
(function () {
    const tbody = document.getElementById('variantTbody');
    const template = document.getElementById('variantRowTemplate');

    // Pasang tombol hapus ke baris yang sudah ada dari server (hasil foreach PHP di atas)
    tbody.querySelectorAll('.btnRemoveVariant').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.target.closest('tr').remove();
        });
    });

    document.getElementById('btnAddVariant').addEventListener('click', function () {
        const clone = template.content.cloneNode(true);
        clone.querySelector('.btnRemoveVariant').addEventListener('click', function (e) {
            e.target.closest('tr').remove();
        });
        tbody.appendChild(clone);
    });
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
