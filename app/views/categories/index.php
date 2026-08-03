<?php
$title = 'Kategori';
$currentRouteKey = 'categories.index';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Daftar Kategori</h5>
    <?php if (user_can('categories.index', 'create')): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddCategory">
            + Tambah Kategori
        </button>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Nama Kategori</th>
                    <th style="width: 160px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">Belum ada kategori.</td></tr>
                <?php endif; ?>
                <?php foreach ($categories as $i => $cat): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($cat['name']) ?></td>
                        <td class="text-end">
                            <?php if (user_can('categories.index', 'edit')): ?>
                                <button class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditCategory<?= $cat['id'] ?>">
                                    Edit
                                </button>
                            <?php endif; ?>
                            <?php if (user_can('categories.index', 'delete')): ?>
                                <form method="POST" action="<?= url('/categories/' . $cat['id'] . '/delete') ?>"
                                      class="d-inline" onsubmit="return confirm('Hapus kategori ini?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- Modal Edit per baris -->
                    <div class="modal fade" id="modalEditCategory<?= $cat['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="<?= url('/categories/' . $cat['id'] . '/update') ?>">
                                    <?= csrf_field() ?>
                                    <div class="modal-header">
                                        <h6 class="modal-title">Edit Kategori</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <label class="form-label">Nama Kategori</label>
                                        <input type="text" name="name" class="form-control"
                                               value="<?= e($cat['name']) ?>" required>
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

<!-- Modal Tambah -->
<div class="modal fade" id="modalAddCategory" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('/categories') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h6 class="modal-title">Tambah Kategori</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Nama Kategori</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Sneakers" required>
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
