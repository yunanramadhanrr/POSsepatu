<?php
$title = 'Lupa Password';
ob_start();
?>

<p class="text-muted small">Masukkan email Anda, kami akan mengirimkan link untuk mengatur ulang password.</p>

<form method="POST" action="<?= url('/forgot-password') ?>">
    <?= csrf_field() ?>

    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required autofocus>
    </div>

    <button type="submit" class="btn btn-primary w-100">Kirim Link Reset</button>

    <div class="text-center mt-3">
        <a href="<?= url('/login') ?>" class="small">Kembali ke login</a>
    </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/guest.php';
