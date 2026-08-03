<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>404 - Halaman Tidak Ditemukan</title>
    <link href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
    <div class="text-center">
        <h1 class="display-1 fw-bold">404</h1>
        <p class="fs-4">Halaman yang Anda cari tidak ditemukan.</p>
        <a href="<?= url('/dashboard') ?>" class="btn btn-primary">Kembali ke Dashboard</a>
    </div>
</body>
</html>
