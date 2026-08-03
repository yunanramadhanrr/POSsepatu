<?php
$title = 'Produk';
$currentRouteKey = 'products.index';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Daftar Produk</h5>
    <?php if (user_can('products.index', 'create')): ?>
        <a href="<?= url('/products/create') ?>" class="btn btn-primary btn-sm">+ Tambah Produk</a>
    <?php endif; ?>
</div>

<form method="GET" action="<?= url('/products') ?>" class="mb-3">
    <div class="input-group" style="max-width: 400px;">
        <input type="text" name="search" class="form-control" placeholder="Cari nama atau kode produk..."
               value="<?= e($_GET['search'] ?? '') ?>">
        <button class="btn btn-outline-secondary" type="submit">Cari</button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 60px;">Foto</th>
                    <th>Kode</th>
                    <th>Nama Sepatu</th>
                    <th>Kategori</th>
                    <th>Brand</th>
                    <th>Varian</th>
                    <th>Total Stok</th>
                    <th>Status</th>
                    <th style="width: 160px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada produk.</td></tr>
                <?php endif; ?>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td>
                            <?php if ($p['photo']): ?>
                                <img src="<?= e(UPLOAD_URL . $p['photo']) ?>" alt="" width="40" height="40"
                                     class="rounded object-fit-cover" style="object-fit: cover;">
                            <?php else: ?>
                                <span class="text-muted">👟</span>
                            <?php endif; ?>
                        </td>
                        <td><code><?= e($p['product_code']) ?></code></td>
                        <td><?= e($p['name']) ?></td>
                        <td><?= e($p['category_name'] ?? '-') ?></td>
                        <td><?= e($p['brand_name'] ?? '-') ?></td>
                        <td><?= (int) $p['total_variants'] ?> varian</td>
                        <td>
                            <?= (int) $p['total_stock'] ?>
                        </td>
                        <td>
                            <span class="badge <?= $p['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= e($p['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <?php if (user_can('products.index', 'edit')): ?>
                                <a href="<?= url('/products/' . $p['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <?php endif; ?>
                            <?php if (user_can('products.index', 'delete')): ?>
                                <form method="POST" action="<?= url('/products/' . $p['id'] . '/delete') ?>"
                                      class="d-inline" onsubmit="return confirm('Hapus produk ini beserta seluruh variannya?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
