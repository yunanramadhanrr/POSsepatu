<?php
/**
 * Membatasi akses route berdasarkan permission RBAC (tabel role_permissions).
 * Dipanggil dari routes/web.php dengan menyertakan menu route_key & ability yang dibutuhkan.
 */
class RoleMiddleware
{
    public static function handle(string $menuRouteKey, string $ability = 'view'): void
    {
        if (!user_can($menuRouteKey, $ability)) {
            abort(403, 'Anda tidak memiliki izin untuk melakukan aksi ini.');
        }
    }
}
