<?php
require_once __DIR__ . '/Model.php';

class StockMovement extends Model
{
    protected static string $table = 'stock_movements';

    /**
     * Riwayat pergerakan stok dengan filter opsional: product_variant_id, type, tanggal_dari, tanggal_sampai.
     */
    public static function history(array $filters = [], int $limit = 100): array
    {
        $sql = "SELECT sm.*, v.size, v.color, v.barcode, p.name AS product_name, u.name AS user_name
                FROM stock_movements sm
                JOIN product_variants v ON v.id = sm.product_variant_id
                JOIN products p ON p.id = v.product_id
                JOIN users u ON u.id = sm.user_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['product_variant_id'])) {
            $sql .= ' AND sm.product_variant_id = :variant_id';
            $params[':variant_id'] = $filters['product_variant_id'];
        }
        if (!empty($filters['type'])) {
            $sql .= ' AND sm.type = :type';
            $params[':type'] = $filters['type'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= ' AND DATE(sm.created_at) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND DATE(sm.created_at) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND (p.name LIKE :search1 OR v.barcode LIKE :search2)';
            $params[':search1'] = '%' . $filters['search'] . '%';
            $params[':search2'] = '%' . $filters['search'] . '%';
        }

        $safeLimit = (int) $limit;
        $sql .= " ORDER BY sm.created_at DESC LIMIT {$safeLimit}";

        return self::raw($sql, $params);
    }
}
