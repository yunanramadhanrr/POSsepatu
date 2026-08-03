<?php
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/ProductVariant.php';
require_once __DIR__ . '/Customer.php';

class SaleReturn extends Model
{
    protected static string $table = 'sale_returns';

    public static function generateReturnNumber(): string
    {
        do {
            $number = generate_code('RTN');
            $exists = self::findOneWhere(['return_number' => $number]);
        } while ($exists);

        return $number;
    }

    public static function allWithSale(): array
    {
        return self::raw(
            "SELECT sr.*, s.invoice_number, s.customer_id, c.name AS customer_name, u.name AS user_name
             FROM sale_returns sr
             JOIN sales s ON s.id = sr.sale_id
             LEFT JOIN customers c ON c.id = s.customer_id
             JOIN users u ON u.id = sr.user_id
             ORDER BY sr.return_date DESC, sr.id DESC"
        );
    }

    public static function findWithItems(int $id): ?array
    {
        $rows = self::raw(
            "SELECT sr.*, s.invoice_number, s.customer_id, c.name AS customer_name, u.name AS user_name
             FROM sale_returns sr
             JOIN sales s ON s.id = sr.sale_id
             LEFT JOIN customers c ON c.id = s.customer_id
             JOIN users u ON u.id = sr.user_id
             WHERE sr.id = :id LIMIT 1",
            [':id' => $id]
        );
        $return = $rows[0] ?? null;
        if (!$return) {
            return null;
        }

        $return['items'] = self::raw(
            "SELECT sri.*, v.size, v.color, v.barcode, p.name AS product_name
             FROM sale_return_items sri
             JOIN product_variants v ON v.id = sri.product_variant_id
             JOIN products p ON p.id = v.product_id
             WHERE sri.sale_return_id = :id",
            [':id' => $id]
        );

        return $return;
    }

    /**
     * Untuk sebuah sale_id, hitung berapa qty tiap product_variant yang SUDAH pernah diretur sebelumnya
     * (menjumlahkan seluruh sale_return_items lintas retur), agar retur berikutnya tidak melebihi sisa.
     */
    public static function alreadyReturnedQtyBySale(int $saleId): array
    {
        $rows = self::raw(
            "SELECT sri.product_variant_id, SUM(sri.qty) AS total_returned
             FROM sale_return_items sri
             JOIN sale_returns sr ON sr.id = sri.sale_return_id
             WHERE sr.sale_id = :sale_id
             GROUP BY sri.product_variant_id",
            [':sale_id' => $saleId]
        );

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['product_variant_id']] = (int) $row['total_returned'];
        }
        return $map;
    }

    /**
     * Proses retur: simpan sale_returns + sale_return_items, kembalikan stok, dan (jika ada pelanggan)
     * kurangi poin secara proporsional terhadap nilai refund. Semua dalam satu database transaction.
     *
     * $items: array of ['product_variant_id', 'qty', 'price', 'subtotal']
     */
    public static function process(int $saleId, string $reason, array $items, int $userId, ?int $customerId): int
    {
        $db = self::connection();
        $db->beginTransaction();

        try {
            $total = array_sum(array_column($items, 'subtotal'));

            $returnId = self::insert([
                'sale_id'       => $saleId,
                'return_number' => self::generateReturnNumber(),
                'return_date'   => date('Y-m-d'),
                'reason'        => $reason,
                'total'         => $total,
                'user_id'       => $userId,
            ]);

            $stmt = $db->prepare(
                'INSERT INTO sale_return_items (sale_return_id, product_variant_id, qty, price, subtotal)
                 VALUES (:return_id, :variant_id, :qty, :price, :subtotal)'
            );

            foreach ($items as $item) {
                $stmt->execute([
                    ':return_id'  => $returnId,
                    ':variant_id' => $item['product_variant_id'],
                    ':qty'        => $item['qty'],
                    ':price'      => $item['price'],
                    ':subtotal'   => $item['subtotal'],
                ]);

                ProductVariant::adjustStock(
                    (int) $item['product_variant_id'],
                    (int) $item['qty'], // positif = stok kembali masuk
                    'in',
                    'sale_return',
                    $returnId,
                    'Retur penjualan ' . $returnId,
                    $userId
                );
            }

            if ($customerId) {
                $pointsToReverse = (int) floor($total / RUPIAH_PER_POINT_EARNED);
                if ($pointsToReverse > 0) {
                    $db->prepare('UPDATE customers SET points = GREATEST(0, points - :p) WHERE id = :id')
                       ->execute([':p' => $pointsToReverse, ':id' => $customerId]);
                    $db->prepare(
                        'INSERT INTO point_histories (customer_id, sale_id, points_change, note)
                         VALUES (:cid, :sid, :p, :note)'
                    )->execute([
                        ':cid'  => $customerId,
                        ':sid'  => $saleId,
                        ':p'    => -$pointsToReverse,
                        ':note' => 'Poin dikurangi akibat retur penjualan',
                    ]);
                }
            }

            $db->commit();

            if ($customerId) {
                Customer::recalculateLevel($customerId);
            }

            return $returnId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
