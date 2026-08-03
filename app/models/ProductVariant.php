<?php
require_once __DIR__ . '/Model.php';

class ProductVariant extends Model
{
    protected static string $table = 'product_variants';

    public static function forProduct(int $productId): array
    {
        return self::where(['product_id' => $productId], 'size ASC, color ASC');
    }

    public static function deleteAllForProduct(int $productId): bool
    {
        $stmt = self::connection()->prepare('DELETE FROM product_variants WHERE product_id = :product_id');
        return $stmt->execute([':product_id' => $productId]);
    }

    public static function findByBarcode(string $barcode): ?array
    {
        return self::findOneWhere(['barcode' => $barcode]);
    }

    /**
     * Cek apakah varian ini pernah dipakai di transaksi apa pun (penjualan, pembelian, retur, stock
     * movement, atau opname). Dipakai sebelum menghapus varian saat edit produk, agar tidak menabrak
     * foreign key constraint (yang sebelumnya menyebabkan fatal error saat produk yang sudah pernah
     * terjual coba diedit).
     */
    public static function hasReferences(int $variantId): bool
    {
        $rows = self::raw(
            "SELECT
                (SELECT COUNT(*) FROM sale_items WHERE product_variant_id = :id1) +
                (SELECT COUNT(*) FROM purchase_items WHERE product_variant_id = :id2) +
                (SELECT COUNT(*) FROM stock_movements WHERE product_variant_id = :id3) +
                (SELECT COUNT(*) FROM sale_return_items WHERE product_variant_id = :id4) +
                (SELECT COUNT(*) FROM purchase_return_items WHERE product_variant_id = :id5) +
                (SELECT COUNT(*) FROM stock_opname_items WHERE product_variant_id = :id6)
                AS total",
            [
                ':id1' => $variantId, ':id2' => $variantId, ':id3' => $variantId,
                ':id4' => $variantId, ':id5' => $variantId, ':id6' => $variantId,
            ]
        );
        return (int) ($rows[0]['total'] ?? 0) > 0;
    }

    /** Varian dengan stok <= minimum stok (untuk notifikasi & dashboard produk hampir habis). */
    public static function lowStock(): array
    {
        return self::raw(
            'SELECT v.*, p.name AS product_name
             FROM product_variants v
             JOIN products p ON p.id = v.product_id
             WHERE v.stock <= v.min_stock
             ORDER BY v.stock ASC'
        );
    }

    /** Varian dengan stok benar-benar 0 (terpisah dari "hampir habis" yang stoknya masih >0). */
    public static function outOfStock(): array
    {
        return self::raw(
            'SELECT v.*, p.name AS product_name
             FROM product_variants v
             JOIN products p ON p.id = v.product_id
             WHERE v.stock = 0
             ORDER BY p.name ASC'
        );
    }

    /** Semua varian + nama produk, dipakai untuk dropdown pemilihan produk (mis. form Pembelian). */
    public static function allWithProductName(): array
    {
        return self::raw(
            "SELECT v.*, p.name AS product_name
             FROM product_variants v
             JOIN products p ON p.id = v.product_id
             WHERE p.status = 'active'
             ORDER BY p.name ASC, v.size ASC"
        );
    }

    /** Tambah/kurangi stok varian dan catat ke stock_movements dalam satu operasi (dipanggil dari dalam transaction pemanggil). */
    public static function adjustStock(int $variantId, int $qtyChange, string $type, ?string $referenceType, ?int $referenceId, string $note, int $userId): void
    {
        $db = self::connection();

        $stmt = $db->prepare('UPDATE product_variants SET stock = stock + :qty WHERE id = :id');
        $stmt->execute([':qty' => $qtyChange, ':id' => $variantId]);

        $stmt2 = $db->prepare(
            'INSERT INTO stock_movements (product_variant_id, type, qty, reference_type, reference_id, note, user_id)
             VALUES (:variant_id, :type, :qty, :reference_type, :reference_id, :note, :user_id)'
        );
        $stmt2->execute([
            ':variant_id'     => $variantId,
            ':type'           => $type,
            ':qty'            => abs($qtyChange),
            ':reference_type' => $referenceType,
            ':reference_id'   => $referenceId,
            ':note'           => $note,
            ':user_id'        => $userId,
        ]);
    }
}
