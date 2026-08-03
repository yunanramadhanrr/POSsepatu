<?php $user = current_user(); ?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Dashboard') ?> - <?= e(APP_NAME) ?></title>
    <link href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <!-- Sidebar (dinamis dari tabel menus + role_permissions) -->
    <?php
    // Semua menu sudah terimplementasi penuh (Tahap 2-15 selesai). Dikelompokkan per kategori
    // agar lebih mudah dipindai secara visual daripada satu daftar panjang 15 item.
    $menuGroups = [
        'Utama'          => ['dashboard.index'],
        'Master Data'    => ['products.index', 'categories.index', 'brands.index', 'suppliers.index', 'customers.index'],
        'Transaksi'      => ['purchases.index', 'sales.index', 'returns.index'],
        'Inventori & Laporan' => ['stock.index', 'reports.index', 'expenses.index'],
        'Administrasi'   => ['settings.index', 'audit_logs.index', 'users.index'],
    ];

    $availableMenus = sidebar_menus();
    $menusByKey = [];
    foreach ($availableMenus as $m) {
        $menusByKey[$m['route_key']] = $m;
    }

    $currentRouteKey = $currentRouteKey ?? '';
    $initials = strtoupper(substr($user['name'], 0, 1));
    ?>
    <nav class="sidebar no-print" id="appSidebar">
        <div class="sidebar-brand">
            <span class="sidebar-brand-icon">👟</span>
            <span class="sidebar-brand-text"><?= e(APP_NAME) ?></span>
            <button type="button" class="sidebar-close d-lg-none" id="sidebarClose" aria-label="Tutup menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="sidebar-scroll">
            <?php foreach ($menuGroups as $groupLabel => $routeKeys): ?>
                <?php
                    // Lewati grup yang tidak punya satupun menu yang boleh dilihat role ini
                    $visibleInGroup = array_filter($routeKeys, fn($rk) => isset($menusByKey[$rk]));
                    if (empty($visibleInGroup)) continue;
                ?>
                <div class="sidebar-group-label"><?= e($groupLabel) ?></div>
                <ul class="sidebar-nav">
                    <?php foreach ($visibleInGroup as $routeKey): ?>
                        <?php $menu = $menusByKey[$routeKey]; ?>
                        <li>
                            <a href="<?= menu_url($routeKey) ?>"
                               class="sidebar-link <?= $currentRouteKey === $routeKey ? 'active' : '' ?>">
                                <i class="bi <?= e($menu['icon'] ?: 'bi-dot') ?>"></i>
                                <span><?= e($menu['label']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </div>

        <div class="sidebar-user">
            <div class="sidebar-user-avatar"><?= e($initials) ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= e($user['name']) ?></div>
                <div class="sidebar-user-role"><?= e($user['role_name']) ?></div>
            </div>
        </div>
    </nav>

    <div class="sidebar-backdrop d-lg-none" id="sidebarBackdrop"></div>

    <!-- Main content -->
    <div class="main-area">
        <nav class="navbar navbar-expand bg-body-tertiary border-bottom px-3 no-print">
            <button type="button" class="btn btn-sm btn-outline-secondary d-lg-none me-2" id="sidebarToggle" aria-label="Buka menu">
                <i class="bi bi-list"></i>
            </button>
            <span class="navbar-text fw-semibold"><?= e($title ?? 'Dashboard') ?></span>

            <div class="ms-auto d-flex align-items-center gap-3">
                <?php $notifCount = NotificationController::totalCount(); ?>
                <a href="<?= url('/notifications') ?>" class="btn btn-sm btn-outline-secondary position-relative">
                    <i class="bi bi-bell"></i>
                    <?php if ($notifCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $notifCount ?>
                        </span>
                    <?php endif; ?>
                </a>

                <button id="themeToggle" class="btn btn-sm btn-outline-secondary"><i class="bi bi-circle-half"></i></button>

                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <?= e($user['name']) ?> <span class="badge bg-primary ms-1"><?= e($user['role_name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= url('/change-password') ?>">Ganti Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="<?= url('/logout') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item">Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <main class="p-4">
            <?php if ($msg = flash('success')): ?>
                <div class="alert alert-success"><?= $msg ?></div>
            <?php endif; ?>
            <?php if ($msg = flash('errors')): ?>
                <div class="alert alert-danger"><?= $msg ?></div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>
</div>

<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>

