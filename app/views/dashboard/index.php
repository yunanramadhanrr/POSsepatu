<?php
$title = 'Dashboard';
$currentRouteKey = 'dashboard.index';
ob_start();
?>

<div class="row g-3 mb-2">
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Penjualan Hari Ini</div>
                <div class="fs-5 fw-bold"><?= format_rupiah($todaySales['total']) ?></div>
                <div class="text-muted small"><?= (int) $todaySales['jumlah_transaksi'] ?> transaksi</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Omzet Bulan Ini</div>
                <div class="fs-5 fw-bold"><?= format_rupiah($monthSales['total']) ?></div>
                <div class="text-muted small"><?= (int) $monthSales['jumlah_transaksi'] ?> transaksi</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Estimasi Profit Bulan Ini</div>
                <div class="fs-5 fw-bold text-success"><?= format_rupiah($monthProfit) ?></div>
                <div class="text-muted small">Harga jual &minus; harga modal</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="text-muted small">Total Produk Aktif</div>
                <div class="fs-5 fw-bold"><?= (int) $totalProdukAktif ?></div>
                <div class="text-muted small"><?= count($lowStockVariants) ?> varian hampir/habis stok</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-2">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="card-title">Grafik Penjualan &amp; Estimasi Profit (14 Hari Terakhir)</h6>
                <canvas id="salesChart" height="90"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="card-title">Produk Hampir/Sudah Habis</h6>
                <?php if (empty($lowStockVariants)): ?>
                    <p class="text-muted small mb-0">Tidak ada produk dengan stok rendah saat ini. 🎉</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach (array_slice($lowStockVariants, 0, 6) as $v): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>
                                    <?= e($v['product_name']) ?>
                                    <span class="text-muted small">(<?= e($v['size']) ?>/<?= e($v['color']) ?>)</span>
                                </span>
                                <span class="badge <?= $v['stock'] == 0 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                    <?= (int) $v['stock'] ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="card-title">Produk Terlaris (30 Hari Terakhir)</h6>
                <?php if (empty($topSellingProducts)): ?>
                    <p class="text-muted small mb-0">Belum ada data penjualan. Data akan muncul otomatis setelah modul Kasir/Penjualan (Tahap 7) digunakan.</p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Produk</th><th>Varian</th><th class="text-end">Terjual</th></tr></thead>
                        <tbody>
                        <?php foreach ($topSellingProducts as $t): ?>
                            <tr>
                                <td><?= e($t['product_name']) ?></td>
                                <td class="text-muted small"><?= e($t['size']) ?>/<?= e($t['color']) ?></td>
                                <td class="text-end fw-semibold"><?= (int) $t['total_qty'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <h6 class="card-title">Aktivitas Terbaru</h6>
                <?php if (empty($recentActivities)): ?>
                    <p class="text-muted small mb-0">Belum ada aktivitas.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentActivities as $act): ?>
                            <li class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <span>
                                        <strong><?= e($act['user_name'] ?? 'Sistem') ?></strong>
                                        <?= e($act['action']) ?>
                                        <?php if ($act['table_name']): ?>
                                            pada <code><?= e($act['table_name']) ?></code>
                                        <?php endif; ?>
                                    </span>
                                    <span class="text-muted small"><?= format_tanggal($act['created_at']) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="<?= asset('vendor/chartjs/chart.umd.js') ?>"></script>
<script>
new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
            {
                label: 'Penjualan',
                data: <?= json_encode($revenueSeries) ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.1)',
                tension: 0.3,
                fill: true,
            },
            {
                label: 'Estimasi Profit',
                data: <?= json_encode($profitSeries) ?>,
                borderColor: '#198754',
                backgroundColor: 'rgba(25,135,84,0.1)',
                tension: 0.3,
                fill: true,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
