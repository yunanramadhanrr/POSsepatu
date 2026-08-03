<?php
/**
 * Helper terkait autentikasi & otorisasi (RBAC berbasis role_permissions).
 */

require_once __DIR__ . '/../models/RolePermission.php';
require_once __DIR__ . '/../models/Menu.php';

/**
 * Ambil daftar menu yang boleh dilihat user yang sedang login, untuk membangun sidebar.
 * Owner selalu melihat semua menu.
 */
function sidebar_menus(): array
{
    $user = current_user();
    if (!$user) {
        return [];
    }

    if ($user['role_name'] === 'Owner') {
        return Menu::allOrdered();
    }

    return RolePermission::visibleMenusForRole($user['role_id']);
}

/** Peta route_key menu -> URL path aplikasi, dipakai untuk merender <a href> sidebar. */
function menu_url(string $routeKey): string
{
    $map = [
        'dashboard.index'  => '/dashboard',
        'products.index'   => '/products',
        'categories.index' => '/categories',
        'brands.index'     => '/brands',
        'suppliers.index'  => '/suppliers',
        'customers.index'  => '/customers',
        'purchases.index'  => '/purchases',
        'sales.index'      => '/sales',
        'returns.index'    => '/returns',
        'stock.index'      => '/stock',
        'reports.index'    => '/reports',
        'expenses.index'   => '/expenses',
        'settings.index'   => '/settings',
        'audit_logs.index' => '/audit-logs',
        'users.index'      => '/users',
    ];

    return url($map[$routeKey] ?? '#');
}

/**
 * Cek apakah user yang sedang login punya izin tertentu pada suatu menu.
 * $ability salah satu dari: 'view', 'create', 'edit', 'delete'
 */
function user_can(string $menuRouteKey, string $ability = 'view'): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }

    // Owner selalu diberi akses penuh tanpa perlu dicek ke tabel permission
    if ($user['role_name'] === 'Owner') {
        return true;
    }

    static $permissionCache = [];
    $cacheKey = $user['role_id'] . ':' . $menuRouteKey;

    if (!isset($permissionCache[$cacheKey])) {
        $permissionCache[$cacheKey] = RolePermission::findByRoleAndMenu($user['role_id'], $menuRouteKey);
    }

    $permission = $permissionCache[$cacheKey];

    if (!$permission) {
        return false;
    }

    $column = 'can_' . $ability;
    return !empty($permission[$column]);
}

/** Wajib login, jika tidak redirect ke halaman login. Dipanggil oleh AuthMiddleware. */
function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Silakan login terlebih dahulu.');
        redirect('/login');
    }
}

/** Wajib punya salah satu role tertentu, jika tidak tampilkan 403. */
function require_role(array $allowedRoles): void
{
    $user = current_user();
    if (!$user || !in_array($user['role_name'], $allowedRoles, true)) {
        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
