<?php
require_once __DIR__ . '/Model.php';

class Report extends Model
{
    protected static string $table = 'sales'; // default, sebagian besar method di sini query lintas tabel

    /** Rentang tanggal default: bulan berjalan, jika $from/$to kosong. */
    private static function normalizeRange(?string $from, ?string $to): array
    {
        $from = $from ?: date('Y-m-01');
        $to = $to ?: date('Y-m-d');
        return [$from, $to];
    }

    // ---------------- Laporan Penjualan ----------------

    public static function salesSummary(?string $from, ?string $to): array
    {
        [$from, $to] = self::normalizeRange($from, $to);
        $rows = self::raw(
            "SELECT COUNT(*) AS total_transaksi, COALESCE(SUM(grand_total),0) AS total_penjualan,
                    COALESCE(SUM(discount_total),0) AS total_diskon, COALESCE(SUM(tax),0) AS total_pajak
             FROM sales
             WHERE status = 'completed' AND DATE(sale_date) BETWEEN :from AND :to",
            [':from' => $from, ':to' => $to]
        );
        return $rows[0];
    }

    public static function salesDetail(?string $from, ?string $to): array
    {
        [$from, $to] = self::normalizeRange($from, $to);
        return self::raw(
            "SELECT s.invoice_number, s.sale_date, u.name AS cashier_name, c.name AS customer_name,
                    s.subtotal, s.discount_total, s.tax, s.grand_total
             FROM sales s
             JOIN users u ON u.id = s.user_id
             LEFT JOIN customers c ON c.id = s.customer_id
             WHERE s.status = 'completed' AND DATE(s.sale_date) BETWEEN :from AND :to
             ORDER BY s.sale_date ASC",
            [':from' => $from, ':to' => $to]
        );
    }

    // ---------------- Laporan Pembelian ----------------

    public static function purchasesSummary(?string $from, ?string $to): array
    {
        [$from, $to] = self::normalizeRange($from, $to);
        $rows = self::raw(
            "SELECT COUNT(*) AS total_transaksi, COALESCE(SUM(grand_total),0) AS total_pembelian
             FROM purchases
             WHERE status = 'completed' AND purchase_date BETWEEN :from AND :to",
            [':from' => $from, ':to' => $to]
        );
        return $rows[0];
    }

    public static function purchasesDetail(?string $from, ?string $to): array
    {
        [$from, $to] = self::normalizeRange($from, $to);
        return self::raw(
            "SELECT pu.invoice_number, pu.purchase_date, s.name AS supplier_name, u.name AS user_name,
                    pu.subtotal, pu.tax, pu.discount, pu.grand_total
             FROM purchases pu
             JOIN suppliers s ON s.id = pu.supplier_id
             JOIN users u ON u.id = pu.user_id
             WHERE pu.status = 'completed' AND pu.purchase_date BETWEEN :from AND :to
             ORDER BY pu.purchase_date ASC",
            [':from' => $from, ':to' => $to]
        );
    }

    // ---------------- Laporan Profit ----------------

    public static function profitDetail(?string $from, ?string $to): array
    {
        [$from, $to] = self::normalizeRange($from, $to);

        $rows = self::raw(
            "SELECT DATE(s.sale_date) AS tanggal,
                    COALESCE(SUM(si.subtotal),0) AS omzet,
                    COALESCE(SUM(si.qty * v.cost_price),0) AS modal,
                    COALESCE(SUM(si.subtotal - (si.qty * v.cost_price)),0) AS profit
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             JOIN product_variants v ON v.id = si.product_variant_id
             WHERE s.status = 'completed' AND DATE(s.sale_date) BETWEEN :from AND :to
             GROUP BY DATE(s.sale_date)
             ORDER BY tanggal ASC",
            [':from' => $from, ':to' => $to]
        );

        $expenseRows = self::raw(
            "SELECT expense_date AS tanggal, SUM(amount) AS total_pengeluaran
             FROM expenses
             WHERE expense_date BETWEEN :from AND :to
             GROUP BY expense_date",
            [':from' => $from, ':to' => $to]
        );
        $expenseMap = array_column($expenseRows, 'total_pengeluaran', 'tanggal');

        foreach ($rows as &$row) {
            $row['pengeluaran'] = (float) ($expenseMap[$row['tanggal']] ?? 0);
            $row['laba_bersih'] = $row['profit'] - $row['pengeluaran'];
        }

        return $rows;
    }

    // ---------------- Laporan Stok ----------------

    public static function stockReport(): array
    {
        return self::raw(
            "SELECT p.name AS product_name, v.size, v.color, v.barcode, v.stock, v.min_stock,
                    v.cost_price, (v.stock * v.cost_price) AS nilai_stok
             FROM product_variants v
             JOIN products p ON p.id = v.product_id
             ORDER BY p.name ASC, v.size ASC"
        );
    }

    // ---------------- Produk Terlaris / Tidak Laku ----------------

    public static function bestSellingProducts(?string $from, ?string $to, int $limit = 20): array
    {
        [$from, $to] = self::normalizeRange($from, $to);
        $safeLimit = (int) $limit;
        return self::raw(
            "SELECT p.name AS product_name, v.size, v.color, SUM(si.qty) AS total_qty,
                    SUM(si.subtotal) AS total_omzet
             FROM sale_items si
             JOIN sales s ON s.id = si.sale_id
             JOIN product_variants v ON v.id = si.product_variant_id
             JOIN products p ON p.id = v.product_id
             WHERE s.status = 'completed' AND DATE(s.sale_date) BETWEEN :from AND :to
             GROUP BY v.id
             ORDER BY total_qty DESC
             LIMIT {$safeLimit}",
            [':from' => $from, ':to' => $to]
        );
    }

    /** Produk yang TIDAK PERNAH terjual dalam rentang tanggal (kandidat "tidak laku"). */
    public static function slowMovingProducts(?string $from, ?string $to, int $limit = 20): array
    {
        [$from, $to] = self::normalizeRange($from, $to);
        $safeLimit = (int) $limit;
        return self::raw(
            "SELECT p.name AS product_name, v.size, v.color, v.stock
             FROM product_variants v
             JOIN products p ON p.id = v.product_id
             WHERE v.id NOT IN (
                 SELECT si.product_variant_id
                 FROM sale_items si
                 JOIN sales s ON s.id = si.sale_id
                 WHERE s.status = 'completed' AND DATE(s.sale_date) BETWEEN :from AND :to
             )
             AND p.status = 'active'
             ORDER BY v.stock DESC
             LIMIT {$safeLimit}",
            [':from' => $from, ':to' => $to]
        );
    }

    // ---------------- Laporan per Kasir ----------------

    public static function byCashier(?string $from, ?string $to): array
    {
        [$from, $to] = self::normalizeRange($from, $to);
        return self::raw(
            "SELECT u.name AS cashier_name, COUNT(s.id) AS total_transaksi,
                    COALESCE(SUM(s.grand_total),0) AS total_penjualan
             FROM sales s
             JOIN users u ON u.id = s.user_id
             WHERE s.status = 'completed' AND DATE(s.sale_date) BETWEEN :from AND :to
             GROUP BY s.user_id
             ORDER BY total_penjualan DESC",
            [':from' => $from, ':to' => $to]
        );
    }

    // ---------------- Laporan per Supplier ----------------

    public static function bySupplier(?string $from, ?string $to): array
    {
        [$from, $to] = self::normalizeRange($from, $to);
        return self::raw(
            "SELECT s.name AS supplier_name, COUNT(pu.id) AS total_transaksi,
                    COALESCE(SUM(pu.grand_total),0) AS total_pembelian
             FROM purchases pu
             JOIN suppliers s ON s.id = pu.supplier_id
             WHERE pu.status = 'completed' AND pu.purchase_date BETWEEN :from AND :to
             GROUP BY pu.supplier_id
             ORDER BY total_pembelian DESC",
            [':from' => $from, ':to' => $to]
        );
    }

    // ---------------- Laporan per Member ----------------

    public static function byMember(?string $from, ?string $to): array
    {
        [$from, $to] = self::normalizeRange($from, $to);
        return self::raw(
            "SELECT c.name AS customer_name, c.member_code, COUNT(s.id) AS total_transaksi,
                    COALESCE(SUM(s.grand_total),0) AS total_belanja
             FROM sales s
             JOIN customers c ON c.id = s.customer_id
             WHERE s.status = 'completed' AND DATE(s.sale_date) BETWEEN :from AND :to
             GROUP BY s.customer_id
             ORDER BY total_belanja DESC",
            [':from' => $from, ':to' => $to]
        );
    }
}
