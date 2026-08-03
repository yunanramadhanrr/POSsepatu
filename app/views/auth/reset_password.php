<?php
$title = 'Reset Password';
ob_start();
?>

<form method="POST" action="<?= url('/reset-password') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <div class="mb-3">
        <label class="form-label">Password Baru</label>
        <input type="password" name="password" class="form-control" minlength="8" required autofocus>
    </div>

    <div class="mb-3">
        <label class="form-label">Konfirmasi Password Baru</label>
        <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
    </div>

    <button type="submit" class="btn btn-primary w-100">Simpan Password Baru</button>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/guest.php';
