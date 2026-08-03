<?php
$title = 'Brand';
$currentRouteKey = 'brands.index';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Daftar Brand</h5>
    <?php if (user_can('brands.index', 'create')): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddBrand">
            + Tambah Brand
        </button>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Nama Brand</th>
                    <th style="width: 160px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($brands)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">Belum ada brand.</td></tr>
                <?php endif; ?>
                <?php foreach ($brands as $i => $brand): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($brand['name']) ?></td>
                        <td class="text-end">
                            <?php if (user_can('brands.index', 'edit')): ?>
                                <button class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditBrand<?= $brand['id'] ?>">
                                    Edit
                                </button>
                            <?php endif; ?>
                            <?php if (user_can('brands.index', 'delete')): ?>
                                <form method="POST" action="<?= url('/brands/' . $brand['id'] . '/delete') ?>"
                                      class="d-inline" onsubmit="return confirm('Hapus brand ini?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalEditBrand<?= $brand['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="<?= url('/brands/' . $brand['id'] . '/update') ?>">
                                    <?= csrf_field() ?>
                                    <div class="modal-header">
                                        <h6 class="modal-title">Edit Brand</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <label class="form-label">Nama Brand</label>
                                        <input type="text" name="name" class="form-control"
                                               value="<?= e($brand['name']) ?>" required>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalAddBrand" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('/brands') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h6 class="modal-title">Tambah Brand</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Nama Brand</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Nike" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
