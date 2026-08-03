<?php
require_once __DIR__ . '/Model.php';

class MembershipLevel extends Model
{
    protected static string $table = 'membership_levels';

    public static function allOrderedByMinPoints(): array
    {
        return self::all('min_points ASC');
    }

    /**
     * Tentukan level yang sesuai untuk sejumlah poin tertentu, berdasarkan threshold min_points
     * tertinggi yang masih terpenuhi. Dipakai untuk auto-upgrade level pelanggan (dipanggil dari
     * Customer::recalculateLevel(), juga akan dipakai modul Penjualan di Tahap 7 setiap poin bertambah).
     */
    public static function findLevelForPoints(int $points): ?array
    {
        $rows = self::raw(
            'SELECT * FROM membership_levels WHERE min_points <= :points ORDER BY min_points DESC LIMIT 1',
            [':points' => $points]
        );
        return $rows[0] ?? null;
    }
}
