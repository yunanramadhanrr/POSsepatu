<?php
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ProductVariant.php';
require_once __DIR__ . '/../models/AuditLog.php';

class DashboardController
{
    /** GET /dashboard */
    public function index(): void
    {
        $user = current_user();

        $todaySales   = Sale::todaySummary();
        $monthSales   = Sale::monthSummary();
        $monthProfit  = Sale::monthProfit();

        $totalProdukAktif = count(array_filter(Product::all(), fn($p) => $p['status'] === 'active'));

        $lowStockVariants    = ProductVariant::lowStock();
        $topSellingProducts  = Sale::topSellingProducts(5, 30);

        $revenueChartRaw = Sale::revenueByDay(14);
        $profitChartRaw  = Sale::profitByDay(14);
        $chartLabels = [];
        $revenueSeries = [];
        $profitSeries = [];

        $revenueMap = array_column($revenueChartRaw, 'total', 'tanggal');
        $profitMap  = array_column($profitChartRaw, 'total', 'tanggal');

        for ($i = 13; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $chartLabels[]  = date('d/m', strtotime($date));
            $revenueSeries[] = (float) ($revenueMap[$date] ?? 0);
            $profitSeries[]  = (float) ($profitMap[$date] ?? 0);
        }

        $recentActivities = AuditLog::recent(10);

        require __DIR__ . '/../views/dashboard/index.php';
    }
}
