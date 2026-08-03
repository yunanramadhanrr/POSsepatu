<?php
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/ProductVariant.php';

class StockOpname extends Model
{
    protected static string $table = 'stock_opnames';

    public static function generateOpnameNumber(): string
    {
        do {
            $number = generate_code('SO');
            $exists = self::findOneWhere(['opname_number' => $number]);
        } while ($exists);

        return $number;
    }

    public static function allWithUser(): array
    {
        return self::raw(
            "SELECT so.*, u.name AS user_name,
                    (SELECT COUNT(*) FROM stock_opname_items soi WHERE soi.stock_opname_id = so.id) AS total_items,
                    (SELECT COALESCE(SUM(ABS(soi.difference)), 0) FROM stock_opname_items soi WHERE soi.stock_opname_id = so.id) AS total_selisih
             FROM stock_opnames so
             JOIN users u ON u.id = so.user_id
             ORDER BY so.opname_date DESC, so.id DESC"
        );
    }

    public static function findWithItems(int $id): ?array
    {
        $rows = self::raw(
            "SELECT so.*, u.name AS user_name
             FROM stock_opnames so
             JOIN users u ON u.id = so.user_id
             WHERE so.id = :id LIMIT 1",
            [':id' => $id]
        );
        $opname = $rows[0] ?? null;
        if (!$opname) {
            return null;
        }

        $opname['items'] = self::raw(
            "SELECT soi.*, v.size, v.color, v.barcode, p.name AS product_name
             FROM stock_opname_items soi
             JOIN product_variants v ON v.id = soi.product_variant_id
             JOIN products p ON p.id = v.product_id
             WHERE soi.stock_opname_id = :id",
            [':id' => $id]
        );

        return $opname;
    }

    /**
     * Proses stock opname: simpan sesi + item per varian (qty sistem vs qty fisik), lalu SESUAIKAN
     * stok sistem agar sama dengan hasil hitung fisik, mencatat selisihnya sebagai stock_movements
     * type='opname'. Item dengan selisih 0 tetap dicatat untuk dokumentasi lengkap, tapi tidak
     * menghasilkan stock_movements (tidak ada perubahan stok riil).
     *
     * $counts: array of ['product_variant_id' => int, 'physical_qty' => int]
     */
    public static function process(string $note, array $counts, int $userId): int
    {
        $db = self::connection();
        $db->beginTransaction();

        try {
            $opnameId = self::insert([
                'opname_number' => self::generateOpnameNumber(),
                'opname_date'   => date('Y-m-d'),
                'user_id'       => $userId,
                'note'          => $note,
            ]);

            $itemStmt = $db->prepare(
                'INSERT INTO stock_opname_items (stock_opname_id, product_variant_id, system_qty, physical_qty, difference)
                 VALUES (:opname_id, :variant_id, :system_qty, :physical_qty, :difference)'
            );

            foreach ($counts as $count) {
                $variant = ProductVariant::find((int) $count['product_variant_id']);
                if (!$variant) {
                    continue;
                }

                $systemQty = (int) $variant['stock'];
                $physicalQty = (int) $count['physical_qty'];
                $difference = $physicalQty - $systemQty;

                $itemStmt->execute([
                    ':opname_id'    => $opnameId,
                    ':variant_id'   => $variant['id'],
                    ':system_qty'   => $systemQty,
                    ':physical_qty' => $physicalQty,
                    ':difference'   => $difference,
                ]);

                if ($difference !== 0) {
                    ProductVariant::adjustStock(
                        (int) $variant['id'],
                        $difference, // langsung set selisihnya (positif/negatif)
                        'opname',
                        'stock_opname',
                        $opnameId,
                        'Stock opname: sistem ' . $systemQty . ' -> fisik ' . $physicalQty,
                        $userId
                    );
                }
            }

            $db->commit();
            return $opnameId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
