<?php
$title = 'Supplier';
$currentRouteKey = 'suppliers.index';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Daftar Supplier</h5>
    <?php if (user_can('suppliers.index', 'create')): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddSupplier">
            + Tambah Supplier
        </button>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>Nama</th>
                    <th>PIC</th>
                    <th>No. HP</th>
                    <th>Email</th>
                    <th style="width: 160px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada supplier.</td></tr>
                <?php endif; ?>
                <?php foreach ($suppliers as $i => $sup): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($sup['name']) ?></td>
                        <td><?= e($sup['pic']) ?></td>
                        <td><?= e($sup['phone']) ?></td>
                        <td><?= e($sup['email']) ?></td>
                        <td class="text-end">
                            <?php if (user_can('suppliers.index', 'edit')): ?>
                                <button class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditSupplier<?= $sup['id'] ?>">
                                    Edit
                                </button>
                            <?php endif; ?>
                            <?php if (user_can('suppliers.index', 'delete')): ?>
                                <form method="POST" action="<?= url('/suppliers/' . $sup['id'] . '/delete') ?>"
                                      class="d-inline" onsubmit="return confirm('Hapus supplier ini?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalEditSupplier<?= $sup['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="<?= url('/suppliers/' . $sup['id'] . '/update') ?>">
                                    <?= csrf_field() ?>
                                    <div class="modal-header">
                                        <h6 class="modal-title">Edit Supplier</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-2">
                                            <label class="form-label">Nama</label>
                                            <input type="text" name="name" class="form-control" value="<?= e($sup['name']) ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">PIC</label>
                                            <input type="text" name="pic" class="form-control" value="<?= e($sup['pic']) ?>">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">No. HP</label>
                                            <input type="text" name="phone" class="form-control" value="<?= e($sup['phone']) ?>">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" value="<?= e($sup['email']) ?>">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Alamat</label>
                                            <textarea name="address" class="form-control" rows="2"><?= e($sup['address']) ?></textarea>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Catatan</label>
                                            <textarea name="note" class="form-control" rows="2"><?= e($sup['note']) ?></textarea>
                                        </div>
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

<div class="modal fade" id="modalAddSupplier" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('/suppliers') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h6 class="modal-title">Tambah Supplier</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">PIC</label>
                        <input type="text" name="pic" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Catatan</label>
                        <textarea name="note" class="form-control" rows="2"></textarea>
                    </div>
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
