<?php
$title = 'Ganti Password';
$user = current_user();
ob_start();
?>

<div class="card shadow-sm" style="max-width: 480px;">
    <div class="card-body">
        <form method="POST" action="<?= url('/change-password') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Password Lama</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <input type="password" name="new_password" class="form-control" minlength="8" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="new_password_confirmation" class="form-control" minlength="8" required>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
