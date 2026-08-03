<?php
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/ProductVariant.php';
require_once __DIR__ . '/Customer.php';

class Sale extends Model
{
    protected static string $table = 'sales';

    /** Total & jumlah transaksi penjualan hari ini (status completed saja). */
    public static function todaySummary(): array
    {
        $rows = self::raw(
            "SELECT COALESCE(SUM(grand_total), 0) AS total, COUNT(*) AS jumlah_transaksi
             FROM sales
             WHERE status = 'completed' AND DATE(sale_date) = CURDATE()"
        );
        return $rows[0];
    }

    /** Total penjualan (omzet) bulan berjalan. */
    public static function monthSummary(): array
    {
        $rows = self::raw(
            "SELECT COALESCE(SUM(grand_total), 0) AS total, COUNT(*) AS jumlah_transaksi
             FROM sales
             WHERE status = 'completed'
               AND MONTH(sale_date) = MONTH(CURDATE())
               AND YEAR(sale_date) = YEAR(CURDATE())"
        );
        return $rows[0];
    }

    /**
     * Estimasi profit bulan berjalan = total harga jual - total harga modal (COGS)
     * dihitung dari sale_items x cost_price varian terkait, untuk transaksi bulan ini.
     */
    public static function monthProfit(): float
    {
        $rows = self::raw(
            "SELECT COALESCE(SUM(si.subtotal - (si.qty * v.cost_price)), 0) AS profit
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             JOIN product_variants v ON v.id = si.product_variant_id
             WHERE s.status = 'completed'
               AND MONTH(s.sale_date) = MONTH(CURDATE())
               AND YEAR(s.sale_date) = YEAR(CURDATE())"
        );
        return (float) $rows[0]['profit'];
    }

    /** Data grafik: total penjualan per hari, N hari terakhir (termasuk hari ini). */
    public static function revenueByDay(int $days = 14): array
    {
        return self::raw(
            "SELECT DATE(sale_date) AS tanggal, COALESCE(SUM(grand_total), 0) AS total
             FROM sales
             WHERE status = 'completed'
               AND sale_date >= (CURDATE() - INTERVAL :days DAY)
             GROUP BY DATE(sale_date)
             ORDER BY tanggal ASC",
            [':days' => $days]
        );
    }

    /** Data grafik: estimasi profit (pendapatan bersih) per hari, N hari terakhir. */
    public static function profitByDay(int $days = 14): array
    {
        return self::raw(
            "SELECT DATE(s.sale_date) AS tanggal,
                    COALESCE(SUM(si.subtotal - (si.qty * v.cost_price)), 0) AS total
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             JOIN product_variants v ON v.id = si.product_variant_id
             WHERE s.status = 'completed'
               AND s.sale_date >= (CURDATE() - INTERVAL :days DAY)
             GROUP BY DATE(s.sale_date)
             ORDER BY tanggal ASC",
            [':days' => $days]
        );
    }

    /** Produk terlaris berdasarkan total qty terjual (default 30 hari terakhir). */
    public static function topSellingProducts(int $limit = 5, int $days = 30): array
    {
        // Catatan: LIMIT tidak bisa dipakai sebagai bound parameter di prepared statement MySQL native,
        // sehingga di-inline langsung setelah dipaksa jadi integer (aman, bukan input user mentah).
        $safeLimit = (int) $limit;

        return self::raw(
            "SELECT p.name AS product_name, v.size, v.color, SUM(si.qty) AS total_qty
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             JOIN product_variants v ON v.id = si.product_variant_id
             JOIN products p ON p.id = v.product_id
             WHERE s.status = 'completed'
               AND s.sale_date >= (CURDATE() - INTERVAL :days DAY)
             GROUP BY v.id
             ORDER BY total_qty DESC
             LIMIT {$safeLimit}",
            [':days' => $days]
        );
    }

    // ================================================================
    // MODUL KASIR / POS (Tahap 7)
    // ================================================================

    public static function generateInvoiceNumber(): string
    {
        do {
            $number = generate_code('INV');
            $exists = self::findOneWhere(['invoice_number' => $number]);
        } while ($exists);

        return $number;
    }

    /** Cari transaksi (completed) berdasarkan nomor invoice, dipakai fitur Retur Penjualan. */
    public static function findByInvoiceNumber(string $invoiceNumber): ?array
    {
        $rows = self::raw(
            "SELECT s.*, u.name AS user_name, c.name AS customer_name
             FROM sales s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN customers c ON c.id = s.customer_id
             WHERE s.invoice_number = :inv AND s.status = 'completed'
             LIMIT 1",
            [':inv' => $invoiceNumber]
        );
        return $rows[0] ?? null;
    }

    /** Daftar transaksi yang sedang di-hold (belum dibayar), untuk fitur Recall Transaksi. */
    public static function heldSales(): array
    {
        return self::raw(
            "SELECT s.*, u.name AS user_name, c.name AS customer_name,
                    (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.id) AS total_items
             FROM sales s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN customers c ON c.id = s.customer_id
             WHERE s.status = 'held'
             ORDER BY s.held_at DESC"
        );
    }

    /** Ambil satu transaksi (apa pun statusnya) + item-itemnya + pembayarannya, untuk detail/recall/struk. */
    public static function findFull(int $id): ?array
    {
        $rows = self::raw(
            "SELECT s.*, u.name AS user_name, c.name AS customer_name, c.member_code, c.points AS customer_points
             FROM sales s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN customers c ON c.id = s.customer_id
             WHERE s.id = :id LIMIT 1",
            [':id' => $id]
        );
        $sale = $rows[0] ?? null;
        if (!$sale) {
            return null;
        }

        $sale['items'] = self::raw(
            "SELECT si.*, v.size, v.color, v.barcode, p.name AS product_name
             FROM sale_items si
             JOIN product_variants v ON v.id = si.product_variant_id
             JOIN products p ON p.id = v.product_id
             WHERE si.sale_id = :id",
            [':id' => $id]
        );

        $sale['payments'] = self::raw(
            "SELECT sp.*, pm.name AS payment_method_name
             FROM sale_payments sp
             JOIN payment_methods pm ON pm.id = sp.payment_method_id
             WHERE sp.sale_id = :id",
            [':id' => $id]
        );

        return $sale;
    }

    /** Buat baris item penjualan untuk sebuah sale_id (dipakai hold maupun checkout). */
    private static function insertItems(int $saleId, array $items): void
    {
        $stmt = self::connection()->prepare(
            'INSERT INTO sale_items (sale_id, product_variant_id, qty, price, discount, subtotal)
             VALUES (:sale_id, :variant_id, :qty, :price, :discount, :subtotal)'
        );

        foreach ($items as $item) {
            $stmt->execute([
                ':sale_id'    => $saleId,
                ':variant_id' => $item['product_variant_id'],
                ':qty'        => $item['qty'],
                ':price'      => $item['price'],
                ':discount'   => $item['discount'],
                ':subtotal'   => $item['subtotal'],
            ]);
        }
    }

    /** Hold keranjang: simpan sale + item dengan status 'held', TANPA memotong stok / mencatat pembayaran. */
    public static function hold(array $saleData, array $items): int
    {
        $db = self::connection();
        $db->beginTransaction();

        try {
            $saleData['status'] = 'held';
            $saleData['held_at'] = date('Y-m-d H:i:s');
            $saleId = self::insert($saleData);
            self::insertItems($saleId, $items);

            $db->commit();
            return $saleId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** Batalkan transaksi yang sedang di-hold (belum bayar, jadi aman dihapus tanpa mempengaruhi stok). */
    public static function cancelHeld(int $saleId): void
    {
        $sale = self::find($saleId);
        if (!$sale || $sale['status'] !== 'held') {
            throw new RuntimeException('Transaksi tidak ditemukan atau sudah bukan status held.');
        }

        self::delete($saleId); // sale_items ikut terhapus via ON DELETE CASCADE
    }

    /**
     * Finalisasi transaksi (checkout): membuat transaksi BARU, atau menuntaskan transaksi yang di-recall
     * dari held (jika $existingSaleId diisi). Dalam satu database transaction:
     *  - insert/replace sale + sale_items
     *  - potong stok tiap varian (ProductVariant::adjustStock, keluar/out)
     *  - insert sale_payments (mendukung multi-payment, array of ['payment_method_id','amount','reference_number'])
     *  - tambah poin pelanggan (jika ada) & sinkronkan level membership
     *  - tandai voucher sebagai 'used' (jika dipakai)
     */
    public static function checkout(array $saleData, array $items, array $payments, int $userId, ?int $existingSaleId = null): int
    {
        $db = self::connection();
        $db->beginTransaction();

        try {
            $saleData['status'] = 'completed';

            if ($existingSaleId) {
                self::update($existingSaleId, $saleData);
                $saleId = $existingSaleId;
                // Strategi replace-all: hapus item lama (belum ada stok terpotong saat held), lalu tulis ulang
                $db->prepare('DELETE FROM sale_items WHERE sale_id = :id')->execute([':id' => $saleId]);
            } else {
                $saleId = self::insert($saleData);
            }

            self::insertItems($saleId, $items);

            // Validasi stok mencukupi SEBELUM memotong (mencegah stok minus akibat race condition/oversell)
            foreach ($items as $item) {
                $variant = ProductVariant::find((int) $item['product_variant_id']);
                if (!$variant || (int) $variant['stock'] < (int) $item['qty']) {
                    throw new RuntimeException(
                        'Stok tidak mencukupi untuk salah satu produk (sisa stok: ' . ($variant['stock'] ?? 0) . ').'
                    );
                }
            }

            foreach ($items as $item) {
                ProductVariant::adjustStock(
                    (int) $item['product_variant_id'],
                    -1 * (int) $item['qty'], // negatif = mengurangi stok
                    'out',
                    'sale',
                    $saleId,
                    'Penjualan ' . $saleData['invoice_number'],
                    $userId
                );
            }

            $paymentStmt = $db->prepare(
                'INSERT INTO sale_payments (sale_id, payment_method_id, amount, reference_number)
                 VALUES (:sale_id, :method_id, :amount, :reference)'
            );
            foreach ($payments as $payment) {
                $paymentStmt->execute([
                    ':sale_id'    => $saleId,
                    ':method_id'  => $payment['payment_method_id'],
                    ':amount'     => $payment['amount'],
                    ':reference'  => $payment['reference_number'] ?? null,
                ]);
            }

            if (!empty($saleData['voucher_id'])) {
                $db->prepare("UPDATE vouchers SET status = 'used' WHERE id = :id")
                   ->execute([':id' => $saleData['voucher_id']]);
            }

            if (!empty($saleData['customer_id'])) {
                $pointsEarned = (int) floor($saleData['grand_total'] / RUPIAH_PER_POINT_EARNED);
                if ($pointsEarned > 0) {
                    $db->prepare('UPDATE customers SET points = points + :p WHERE id = :id')
                       ->execute([':p' => $pointsEarned, ':id' => $saleData['customer_id']]);
                    $db->prepare(
                        'INSERT INTO point_histories (customer_id, sale_id, points_change, note)
                         VALUES (:cid, :sid, :p, :note)'
                    )->execute([
                        ':cid'  => $saleData['customer_id'],
                        ':sid'  => $saleId,
                        ':p'    => $pointsEarned,
                        ':note' => 'Poin dari transaksi ' . $saleData['invoice_number'],
                    ]);
                }
            }

            $db->commit();

            if (!empty($saleData['customer_id'])) {
                Customer::recalculateLevel((int) $saleData['customer_id']);
            }

            return $saleId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
