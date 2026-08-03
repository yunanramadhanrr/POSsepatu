<?php
require_once __DIR__ . '/Model.php';

class Brand extends Model
{
    protected static string $table = 'brands';

    public static function countProducts(int $brandId): int
    {
        $rows = self::raw('SELECT COUNT(*) AS total FROM products WHERE brand_id = :id', [':id' => $brandId]);
        return (int) ($rows[0]['total'] ?? 0);
    }
}
