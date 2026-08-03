<?php
require_once __DIR__ . '/Model.php';

class RolePermission extends Model
{
    protected static string $table = 'role_permissions';

    public static function findByRoleAndMenu(int $roleId, string $menuRouteKey): ?array
    {
        $rows = self::raw(
            'SELECT rp.*
             FROM role_permissions rp
             JOIN menus m ON m.id = rp.menu_id
             WHERE rp.role_id = :role_id AND m.route_key = :route_key
             LIMIT 1',
            [':role_id' => $roleId, ':route_key' => $menuRouteKey]
        );
        return $rows[0] ?? null;
    }

    /** Ambil seluruh menu yang boleh dilihat (can_view = 1) oleh sebuah role, untuk membangun sidebar. */
    public static function visibleMenusForRole(int $roleId): array
    {
        return self::raw(
            'SELECT m.*
             FROM menus m
             JOIN role_permissions rp ON rp.menu_id = m.id
             WHERE rp.role_id = :role_id AND rp.can_view = 1
             ORDER BY m.sort_order ASC',
            [':role_id' => $roleId]
        );
    }
}
