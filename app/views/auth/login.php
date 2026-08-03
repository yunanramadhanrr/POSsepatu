<?php
$title = 'Login';
ob_start();
?>

<form method="POST" action="<?= url('/login') ?>">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= old('email') ?>" required autofocus>
    </div>

    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>

    <div class="mb-3 form-check">
        <input type="checkbox" name="remember" class="form-check-input" id="remember">
        <label class="form-check-label" for="remember">Ingat saya</label>
    </div>

    <button type="submit" class="btn btn-primary w-100">Masuk</button>

    <div class="text-center mt-3">
        <a href="<?= url('/forgot-password') ?>" class="small">Lupa password?</a>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/guest.php';
