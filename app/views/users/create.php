<?php
$title = 'Tambah User';
$currentRouteKey = 'users.index';
ob_start();
?>

<a href="<?= url('/users') ?>" class="btn btn-sm btn-outline-secondary mb-3">&larr; Kembali</a>

<div class="card shadow-sm" style="max-width: 500px;">
    <div class="card-body">
        <h6 class="card-title">Tambah User Baru</h6>
        <form method="POST" action="<?= url('/users') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="name" class="form-control" value="<?= old('name') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= old('email') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" minlength="8" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select" required>
                    <option value="">- Pilih Role -</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak Aktif</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
