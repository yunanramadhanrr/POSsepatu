<?php
require_once __DIR__ . '/Model.php';

class PointHistory extends Model
{
    protected static string $table = 'point_histories';

    public static function forCustomer(int $customerId): array
    {
        return self::where(['customer_id' => $customerId], 'created_at DESC');
    }

    public static function log(int $customerId, int $pointsChange, string $note, ?int $saleId = null): int
    {
        return self::insert([
            'customer_id'   => $customerId,
            'sale_id'       => $saleId,
            'points_change' => $pointsChange,
            'note'          => $note,
        ]);
    }
}
