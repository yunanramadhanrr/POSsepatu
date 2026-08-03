<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? APP_NAME) ?> - <?= e(APP_NAME) ?></title>
    <link href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body class="guest-bg d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow-sm border-0" style="width: 100%; max-width: 400px;">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <h4 class="fw-bold mb-0">👟 <?= e(APP_NAME) ?></h4>
            </div>

            <?php if ($msg = flash('success')): ?>
                <div class="alert alert-success py-2"><?= $msg /* sudah berupa string statis, aman */ ?></div>
            <?php endif; ?>

            <?php if ($msg = flash('errors')): ?>
                <div class="alert alert-danger py-2"><?= $msg /* pesan validasi statis dari server, aman */ ?></div>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </div>
</body>
</html>
