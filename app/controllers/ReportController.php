<?php
require_once __DIR__ . '/../models/Report.php';

class ReportController
{
    /** GET /reports — hub navigasi ke seluruh jenis laporan */
    public function index(): void
    {
        RoleMiddleware::handle('reports.index', 'view');
        require __DIR__ . '/../views/reports/index.php';
    }

    /** GET /reports/sales */
    public function sales(): void
    {
        RoleMiddleware::handle('reports.index', 'view');
        [$from, $to] = $this->range();

        $summary = Report::salesSummary($from, $to);
        $detail = Report::salesDetail($from, $to);

        if (($_GET['export'] ?? '') === 'csv') {
            $rows = array_map(fn($r) => [
                $r['invoice_number'], $r['sale_date'], $r['cashier_name'], $r['customer_name'] ?? '-',
                $r['subtotal'], $r['discount_total'], $r['tax'], $r['grand_total'],
            ], $detail);
            download_csv('laporan-penjualan-' . $from . '_' . $to . '.csv',
                ['Invoice', 'Tanggal', 'Kasir', 'Pelanggan', 'Subtotal', 'Diskon', 'Pajak', 'Grand Total'], $rows);
        }

        require __DIR__ . '/../views/reports/sales.php';
    }

    /** GET /reports/purchases */
    public function purchases(): void
    {
        RoleMiddleware::handle('reports.index', 'view');
        [$from, $to] = $this->range();

        $summary = Report::purchasesSummary($from, $to);
        $detail = Report::purchasesDetail($from, $to);

        if (($_GET['export'] ?? '') === 'csv') {
            $rows = array_map(fn($r) => [
                $r['invoice_number'], $r['purchase_date'], $r['supplier_name'], $r['user_name'],
                $r['subtotal'], $r['tax'], $r['discount'], $r['grand_total'],
            ], $detail);
            download_csv('laporan-pembelian-' . $from . '_' . $to . '.csv',
                ['Invoice', 'Tanggal', 'Supplier', 'Dibuat Oleh', 'Subtotal', 'Pajak', 'Diskon', 'Grand Total'], $rows);
        }

        require __DIR__ . '/../views/reports/purchases.php';
    }

    /** GET /reports/profit */
    public function profit(): void
    {
        RoleMiddleware::handle('reports.index', 'view');
        [$from, $to] = $this->range();

        $detail = Report::profitDetail($from, $to);
        $totalOmzet = array_sum(array_column($detail, 'omzet'));
        $totalModal = array_sum(array_column($detail, 'modal'));
        $totalProfit = array_sum(array_column($detail, 'profit'));
        $totalPengeluaran = array_sum(array_column($detail, 'pengeluaran'));
        $totalLabaBersih = $totalProfit - $totalPengeluaran;

        if (($_GET['export'] ?? '') === 'csv') {
            $rows = array_map(fn($r) => [
                $r['tanggal'], $r['omzet'], $r['modal'], $r['profit'], $r['pengeluaran'], $r['laba_bersih'],
            ], $detail);
            download_csv('laporan-profit-' . $from . '_' . $to . '.csv',
                ['Tanggal', 'Omzet', 'Modal', 'Profit Kotor', 'Pengeluaran', 'Laba Bersih'], $rows);
        }

        require __DIR__ . '/../views/reports/profit.php';
    }

    /** GET /reports/stock */
    public function stock(): void
    {
        RoleMiddleware::handle('reports.index', 'view');
        $detail = Report::stockReport();
        $totalNilai = array_sum(array_column($detail, 'nilai_stok'));

        if (($_GET['export'] ?? '') === 'csv') {
            $rows = array_map(fn($r) => [
                $r['product_name'], $r['size'], $r['color'], $r['barcode'], $r['stock'], $r['min_stock'], $r['nilai_stok'],
            ], $detail);
            download_csv('laporan-stok-' . date('Y-m-d') . '.csv',
                ['Produk', 'Ukuran', 'Warna', 'Barcode', 'Stok', 'Min. Stok', 'Nilai Stok'], $rows);
        }

        require __DIR__ . '/../views/reports/stock.php';
    }

    /** GET /reports/products?type=best|worst */
    public function products(): void
    {
        RoleMiddleware::handle('reports.index', 'view');
        [$from, $to] = $this->range();
        $type = ($_GET['type'] ?? 'best') === 'worst' ? 'worst' : 'best';

        $detail = $type === 'best'
            ? Report::bestSellingProducts($from, $to, 50)
            : Report::slowMovingProducts($from, $to, 50);

        if (($_GET['export'] ?? '') === 'csv') {
            if ($type === 'best') {
                $rows = array_map(fn($r) => [$r['product_name'], $r['size'], $r['color'], $r['total_qty'], $r['total_omzet']], $detail);
                download_csv('produk-terlaris-' . $from . '_' . $to . '.csv', ['Produk', 'Ukuran', 'Warna', 'Qty Terjual', 'Omzet'], $rows);
            } else {
                $rows = array_map(fn($r) => [$r['product_name'], $r['size'], $r['color'], $r['stock']], $detail);
                download_csv('produk-tidak-laku-' . $from . '_' . $to . '.csv', ['Produk', 'Ukuran', 'Warna', 'Stok Saat Ini'], $rows);
            }
        }

        require __DIR__ . '/../views/reports/products.php';
    }

    /** GET /reports/cashier */
    public function cashier(): void
    {
        RoleMiddleware::handle('reports.index', 'view');
        [$from, $to] = $this->range();
        $detail = Report::byCashier($from, $to);

        if (($_GET['export'] ?? '') === 'csv') {
            $rows = array_map(fn($r) => [$r['cashier_name'], $r['total_transaksi'], $r['total_penjualan']], $detail);
            download_csv('laporan-kasir-' . $from . '_' . $to . '.csv', ['Kasir', 'Total Transaksi', 'Total Penjualan'], $rows);
        }

        require __DIR__ . '/../views/reports/cashier.php';
    }

    /** GET /reports/supplier */
    public function supplier(): void
    {
        RoleMiddleware::handle('reports.index', 'view');
        [$from, $to] = $this->range();
        $detail = Report::bySupplier($from, $to);

        if (($_GET['export'] ?? '') === 'csv') {
            $rows = array_map(fn($r) => [$r['supplier_name'], $r['total_transaksi'], $r['total_pembelian']], $detail);
            download_csv('laporan-supplier-' . $from . '_' . $to . '.csv', ['Supplier', 'Total Transaksi', 'Total Pembelian'], $rows);
        }

        require __DIR__ . '/../views/reports/supplier.php';
    }

    /** GET /reports/member */
    public function member(): void
    {
        RoleMiddleware::handle('reports.index', 'view');
        [$from, $to] = $this->range();
        $detail = Report::byMember($from, $to);

        if (($_GET['export'] ?? '') === 'csv') {
            $rows = array_map(fn($r) => [$r['customer_name'], $r['member_code'], $r['total_transaksi'], $r['total_belanja']], $detail);
            download_csv('laporan-member-' . $from . '_' . $to . '.csv', ['Nama', 'Kode Member', 'Total Transaksi', 'Total Belanja'], $rows);
        }

        require __DIR__ . '/../views/reports/member.php';
    }

    /** Ambil rentang tanggal dari query string, dengan preset cepat: today/week/month/year. */
    private function range(): array
    {
        $preset = $_GET['preset'] ?? '';
        switch ($preset) {
            case 'today':
                return [date('Y-m-d'), date('Y-m-d')];
            case 'week':
                return [date('Y-m-d', strtotime('monday this week')), date('Y-m-d')];
            case 'year':
                return [date('Y-01-01'), date('Y-m-d')];
            case 'month':
                return [date('Y-m-01'), date('Y-m-d')];
        }

        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        return [$from, $to];
    }
}
