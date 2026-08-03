<?php
require_once __DIR__ . '/Model.php';

class Supplier extends Model
{
    protected static string $table = 'suppliers';

    public static function countProducts(int $supplierId): int
    {
        $rows = self::raw('SELECT COUNT(*) AS total FROM products WHERE supplier_id = :id', [':id' => $supplierId]);
        return (int) ($rows[0]['total'] ?? 0);
    }
}
