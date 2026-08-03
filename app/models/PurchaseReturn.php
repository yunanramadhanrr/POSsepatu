<?php
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/ProductVariant.php';

class PurchaseReturn extends Model
{
    protected static string $table = 'purchase_returns';

    public static function generateReturnNumber(): string
    {
        do {
            $number = generate_code('RTP');
            $exists = self::findOneWhere(['return_number' => $number]);
        } while ($exists);

        return $number;
    }

    public static function allWithPurchase(): array
    {
        return self::raw(
            "SELECT pr.*, p.invoice_number, s.name AS supplier_name, u.name AS user_name
             FROM purchase_returns pr
             JOIN purchases p ON p.id = pr.purchase_id
             JOIN suppliers s ON s.id = p.supplier_id
             JOIN users u ON u.id = pr.user_id
             ORDER BY pr.return_date DESC, pr.id DESC"
        );
    }

    public static function findWithItems(int $id): ?array
    {
        $rows = self::raw(
            "SELECT pr.*, p.invoice_number, s.name AS supplier_name, u.name AS user_name
             FROM purchase_returns pr
             JOIN purchases p ON p.id = pr.purchase_id
             JOIN suppliers s ON s.id = p.supplier_id
             JOIN users u ON u.id = pr.user_id
             WHERE pr.id = :id LIMIT 1",
            [':id' => $id]
        );
        $return = $rows[0] ?? null;
        if (!$return) {
            return null;
        }

        $return['items'] = self::raw(
            "SELECT pri.*, v.size, v.color, v.barcode, prod.name AS product_name
             FROM purchase_return_items pri
             JOIN product_variants v ON v.id = pri.product_variant_id
             JOIN products prod ON prod.id = v.product_id
             WHERE pri.purchase_return_id = :id",
            [':id' => $id]
        );

        return $return;
    }

    /** Qty yang sudah pernah diretur per varian untuk sebuah purchase_id (mencegah retur melebihi sisa). */
    public static function alreadyReturnedQtyByPurchase(int $purchaseId): array
    {
        $rows = self::raw(
            "SELECT pri.product_variant_id, SUM(pri.qty) AS total_returned
             FROM purchase_return_items pri
             JOIN purchase_returns pr ON pr.id = pri.purchase_return_id
             WHERE pr.purchase_id = :purchase_id
             GROUP BY pri.product_variant_id",
            [':purchase_id' => $purchaseId]
        );

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['product_variant_id']] = (int) $row['total_returned'];
        }
        return $map;
    }

    /**
     * Proses retur pembelian: simpan purchase_returns + item, KURANGI stok (barang dikembalikan ke supplier).
     * $items: array of ['product_variant_id', 'qty', 'price', 'subtotal']
     */
    public static function process(int $purchaseId, string $reason, array $items, int $userId): int
    {
        $db = self::connection();
        $db->beginTransaction();

        try {
            $total = array_sum(array_column($items, 'subtotal'));

            $returnId = self::insert([
                'purchase_id'   => $purchaseId,
                'return_number' => self::generateReturnNumber(),
                'return_date'   => date('Y-m-d'),
                'reason'        => $reason,
                'total'         => $total,
                'user_id'       => $userId,
            ]);

            $stmt = $db->prepare(
                'INSERT INTO purchase_return_items (purchase_return_id, product_variant_id, qty, price, subtotal)
                 VALUES (:return_id, :variant_id, :qty, :price, :subtotal)'
            );

            foreach ($items as $item) {
                // Validasi stok cukup untuk dikembalikan ke supplier (tidak boleh sampai minus)
                $variant = ProductVariant::find((int) $item['product_variant_id']);
                if (!$variant || (int) $variant['stock'] < (int) $item['qty']) {
                    throw new RuntimeException(
                        'Stok tidak mencukupi untuk retur produk (sisa stok: ' . ($variant['stock'] ?? 0) . ').'
                    );
                }

                $stmt->execute([
                    ':return_id'  => $returnId,
                    ':variant_id' => $item['product_variant_id'],
                    ':qty'        => $item['qty'],
                    ':price'      => $item['price'],
                    ':subtotal'   => $item['subtotal'],
                ]);

                ProductVariant::adjustStock(
                    (int) $item['product_variant_id'],
                    -1 * (int) $item['qty'], // negatif = stok keluar (dikembalikan ke supplier)
                    'out',
                    'purchase_return',
                    $returnId,
                    'Retur pembelian ' . $returnId,
                    $userId
                );
            }

            $db->commit();
            return $returnId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
