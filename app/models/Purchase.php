<?php
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/ProductVariant.php';

class Purchase extends Model
{
    protected static string $table = 'purchases';

    public static function allWithSupplier(): array
    {
        return self::raw(
            'SELECT pu.*, s.name AS supplier_name, u.name AS user_name
             FROM purchases pu
             JOIN suppliers s ON s.id = pu.supplier_id
             JOIN users u ON u.id = pu.user_id
             ORDER BY pu.purchase_date DESC, pu.id DESC'
        );
    }

    public static function findWithSupplier(int $id): ?array
    {
        $rows = self::raw(
            'SELECT pu.*, s.name AS supplier_name, s.address AS supplier_address, s.phone AS supplier_phone,
                    u.name AS user_name
             FROM purchases pu
             JOIN suppliers s ON s.id = pu.supplier_id
             JOIN users u ON u.id = pu.user_id
             WHERE pu.id = :id
             LIMIT 1',
            [':id' => $id]
        );
        return $rows[0] ?? null;
    }

    public static function itemsWithProduct(int $purchaseId): array
    {
        return self::raw(
            'SELECT pi.*, v.size, v.color, v.barcode, p.name AS product_name
             FROM purchase_items pi
             JOIN product_variants v ON v.id = pi.product_variant_id
             JOIN products p ON p.id = v.product_id
             WHERE pi.purchase_id = :purchase_id',
            [':purchase_id' => $purchaseId]
        );
    }

    public static function generateInvoiceNumber(): string
    {
        do {
            $number = generate_code('PO');
            $exists = self::findOneWhere(['invoice_number' => $number]);
        } while ($exists);

        return $number;
    }

    /** Cari pembelian berdasarkan nomor invoice, dipakai fitur Retur Pembelian. */
    public static function findByInvoiceNumber(string $invoiceNumber): ?array
    {
        $rows = self::raw(
            "SELECT pu.*, s.name AS supplier_name, u.name AS user_name
             FROM purchases pu
             JOIN suppliers s ON s.id = pu.supplier_id
             JOIN users u ON u.id = pu.user_id
             WHERE pu.invoice_number = :inv AND pu.status = 'completed'
             LIMIT 1",
            [':inv' => $invoiceNumber]
        );
        return $rows[0] ?? null;
    }

    /**
     * Simpan pembelian + item-itemnya dalam satu database transaction, sekaligus menambah stok
     * varian terkait dan mencatat pergerakan stok (stock_movements type='in').
     */
    public static function createWithItems(array $purchaseData, array $items, int $userId): int
    {
        $db = self::connection();
        $db->beginTransaction();

        try {
            $purchaseId = self::insert($purchaseData);

            foreach ($items as $item) {
                $itemId = self::connection()->prepare(
                    'INSERT INTO purchase_items (purchase_id, product_variant_id, qty, price, subtotal)
                     VALUES (:purchase_id, :variant_id, :qty, :price, :subtotal)'
                );
                $itemId->execute([
                    ':purchase_id' => $purchaseId,
                    ':variant_id'  => $item['product_variant_id'],
                    ':qty'         => $item['qty'],
                    ':price'       => $item['price'],
                    ':subtotal'    => $item['subtotal'],
                ]);

                ProductVariant::adjustStock(
                    (int) $item['product_variant_id'],
                    (int) $item['qty'], // positif = menambah stok
                    'in',
                    'purchase',
                    $purchaseId,
                    'Pembelian ' . $purchaseData['invoice_number'],
                    $userId
                );
            }

            $db->commit();
            return $purchaseId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
