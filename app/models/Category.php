<?php
require_once __DIR__ . '/Model.php';

class Category extends Model
{
    protected static string $table = 'categories';

    /** Hitung berapa produk memakai kategori ini, dipakai untuk mencegah hapus kategori yang masih dipakai. */
    public static function countProducts(int $categoryId): int
    {
        $rows = self::raw('SELECT COUNT(*) AS total FROM products WHERE category_id = :id', [':id' => $categoryId]);
        return (int) ($rows[0]['total'] ?? 0);
    }
}
