<?php
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/ProductVariant.php';

class Product extends Model
{
    protected static string $table = 'products';

    /** Daftar produk + nama kategori/brand/supplier + total stok seluruh varian, dengan pencarian opsional. */
    public static function allWithRelations(string $search = ''): array
    {
        $sql = 'SELECT p.*,
                       c.name AS category_name,
                       b.name AS brand_name,
                       s.name AS supplier_name,
                       COALESCE(SUM(v.stock), 0) AS total_stock,
                       COUNT(v.id) AS total_variants
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN suppliers s ON s.id = p.supplier_id
                LEFT JOIN product_variants v ON v.product_id = p.id';

        $params = [];
        if ($search !== '') {
            $sql .= ' WHERE p.name LIKE :search1 OR p.product_code LIKE :search2';
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
        }

        $sql .= ' GROUP BY p.id ORDER BY p.name ASC';

        return self::raw($sql, $params);
    }

    /** Ambil satu produk + relasi (untuk halaman edit), varian diambil terpisah lewat ProductVariant::forProduct(). */
    public static function findWithRelations(int $id): ?array
    {
        $rows = self::raw(
            'SELECT p.*, c.name AS category_name, b.name AS brand_name, s.name AS supplier_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id
             LEFT JOIN suppliers s ON s.id = p.supplier_id
             WHERE p.id = :id
             LIMIT 1',
            [':id' => $id]
        );
        return $rows[0] ?? null;
    }

    /**
     * Simpan produk baru beserta varian-variannya dalam SATU database transaction,
     * agar tidak ada produk "yatim" tanpa varian jika terjadi error di tengah proses.
     */
    public static function createWithVariants(array $productData, array $variants): int
    {
        $db = self::connection();
        $db->beginTransaction();

        try {
            $productId = self::insert($productData);

            foreach ($variants as $variant) {
                unset($variant['id']); // produk baru: semua varian pasti baru, tidak ada id lama
                $variant['product_id'] = $productId;
                ProductVariant::insert($variant);
            }

            $db->commit();
            return $productId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Update produk + sinkronisasi varian dengan AMAN terhadap riwayat transaksi:
     *  - Varian yang sudah ada (dikenali dari 'id') di-UPDATE di tempat (bukan dihapus+buat ulang),
     *    supaya ID-nya tetap sama dan tidak menabrak foreign key di sale_items/purchase_items/dll.
     *  - Varian baru (tanpa 'id') di-INSERT seperti biasa.
     *  - Varian lama yang tidak lagi ada di form (dihapus user dari UI) HANYA benar-benar dihapus dari
     *    database jika belum pernah dipakai di transaksi apa pun. Jika sudah pernah dipakai (ada di
     *    sale_items/purchase_items/stock_movements/dll), varian tersebut TIDAK dihapus — dibiarkan tetap
     *    ada agar riwayat transaksi lama tidak rusak — dan namanya dikembalikan lewat return value supaya
     *    controller bisa memberi tahu user dengan pesan yang jelas (bukan crash fatal error).
     *
     * Return: array berisi label varian (ukuran/warna) yang GAGAL dihapus karena masih punya riwayat transaksi.
     */
    public static function updateWithVariants(int $productId, array $productData, array $variants): array
    {
        $db = self::connection();
        $db->beginTransaction();
        $skipped = [];

        try {
            self::update($productId, $productData);

            $existingVariants = ProductVariant::forProduct($productId);
            $existingIds = array_column($existingVariants, 'id');
            $submittedIds = [];

            foreach ($variants as $variant) {
                $variantId = !empty($variant['id']) ? (int) $variant['id'] : null;
                unset($variant['id']);
                $variant['product_id'] = $productId;

                if ($variantId && in_array($variantId, $existingIds, true)) {
                    ProductVariant::update($variantId, $variant);
                    $submittedIds[] = $variantId;
                } else {
                    $submittedIds[] = ProductVariant::insert($variant);
                }
            }

            // Varian lama yang tidak lagi disertakan di form = dianggap ingin dihapus user
            foreach (array_diff($existingIds, $submittedIds) as $variantId) {
                if (ProductVariant::hasReferences($variantId)) {
                    $old = ProductVariant::find($variantId);
                    $skipped[] = trim(($old['size'] ?? '') . '/' . ($old['color'] ?? ''), '/');
                    continue; // jangan dihapus, biarkan riwayat transaksi lama tetap utuh
                }
                ProductVariant::delete($variantId);
            }

            $db->commit();
            return $skipped;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** Generate kode produk otomatis & unik, contoh: SPT-20260727-4821 */
    public static function generateUniqueCode(): string
    {
        do {
            $code = generate_code('SPT');
            $exists = self::findOneWhere(['product_code' => $code]);
        } while ($exists);

        return $code;
    }
}
