<?php
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/MembershipLevel.php';
require_once __DIR__ . '/PointHistory.php';
require_once __DIR__ . '/Voucher.php';

class Customer extends Model
{
    protected static string $table = 'customers';

    /** Daftar pelanggan + nama level membership, dengan pencarian opsional (nama/no HP/kode member). */
    public static function allWithLevel(string $search = ''): array
    {
        $sql = 'SELECT c.*, ml.name AS level_name, ml.discount_percent
                FROM customers c
                LEFT JOIN membership_levels ml ON ml.id = c.membership_level_id';
        $params = [];

        if ($search !== '') {
            $sql .= ' WHERE c.name LIKE :search1 OR c.phone LIKE :search2 OR c.member_code LIKE :search3';
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY c.name ASC';

        return self::raw($sql, $params);
    }

    public static function findWithLevel(int $id): ?array
    {
        $rows = self::raw(
            'SELECT c.*, ml.name AS level_name, ml.discount_percent
             FROM customers c
             LEFT JOIN membership_levels ml ON ml.id = c.membership_level_id
             WHERE c.id = :id LIMIT 1',
            [':id' => $id]
        );
        return $rows[0] ?? null;
    }

    /** Riwayat transaksi penjualan pelanggan (dipakai di halaman detail; kosong sampai modul Kasir Tahap 7 aktif). */
    public static function purchaseHistory(int $customerId): array
    {
        return self::raw(
            'SELECT id, invoice_number, sale_date, grand_total, status
             FROM sales
             WHERE customer_id = :id
             ORDER BY sale_date DESC',
            [':id' => $customerId]
        );
    }

    /** Pelanggan yang tanggal lahirnya (bulan & hari) sama dengan hari ini, untuk notifikasi ulang tahun. */
    public static function birthdaysToday(): array
    {
        return self::raw(
            "SELECT * FROM customers
             WHERE birth_date IS NOT NULL
               AND DATE_FORMAT(birth_date, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')"
        );
    }

    public static function generateUniqueMemberCode(): string
    {
        do {
            $code = generate_code('MBR');
            $exists = self::findOneWhere(['member_code' => $code]);
        } while ($exists);

        return $code;
    }

    /**
     * Sesuaikan level membership pelanggan berdasarkan jumlah poin saat ini.
     * Dipanggil setiap kali poin berubah (tukar poin di Tahap 5, dan transaksi penjualan di Tahap 7).
     */
    public static function recalculateLevel(int $customerId): void
    {
        $customer = self::find($customerId);
        if (!$customer) {
            return;
        }

        $level = MembershipLevel::findLevelForPoints((int) $customer['points']);
        if ($level && (int) $level['id'] !== (int) $customer['membership_level_id']) {
            self::update($customerId, ['membership_level_id' => $level['id']]);
        }
    }

    /**
     * Tukar sejumlah poin pelanggan menjadi voucher toko. Dilakukan dalam satu transaction:
     * kurangi poin, catat ke point_histories (nilai negatif), buat voucher baru.
     * Return kode voucher yang dihasilkan.
     */
    public static function redeemPointsToVoucher(int $customerId, int $pointsToRedeem): string
    {
        $db = self::connection();
        $db->beginTransaction();

        try {
            $customer = self::find($customerId);
            if (!$customer) {
                throw new RuntimeException('Pelanggan tidak ditemukan.');
            }
            if ($pointsToRedeem < MIN_POINTS_REDEEM) {
                throw new RuntimeException('Minimal penukaran adalah ' . MIN_POINTS_REDEEM . ' poin.');
            }
            if ($pointsToRedeem > (int) $customer['points']) {
                throw new RuntimeException('Poin pelanggan tidak mencukupi.');
            }

            $voucherValue = $pointsToRedeem * POINTS_TO_RUPIAH_RATE;
            $voucherCode = Voucher::generateUniqueCode();

            Voucher::insert([
                'code'       => $voucherCode,
                'value'      => $voucherValue,
                'value_type' => 'nominal',
                'expired_at' => date('Y-m-d', strtotime('+' . VOUCHER_VALID_DAYS . ' days')),
                'status'     => 'active',
            ]);

            self::update($customerId, ['points' => (int) $customer['points'] - $pointsToRedeem]);

            PointHistory::log(
                $customerId,
                -$pointsToRedeem,
                'Ditukar menjadi voucher ' . $voucherCode . ' (Rp ' . number_format($voucherValue, 0, ',', '.') . ')'
            );

            $db->commit();

            self::recalculateLevel($customerId);

            return $voucherCode;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
