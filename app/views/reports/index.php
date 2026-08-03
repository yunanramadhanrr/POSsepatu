<?php
$title = 'Laporan';
$currentRouteKey = 'reports.index';
ob_start();

$reportLinks = [
    ['url' => '/reports/sales',     'icon' => '🧾', 'label' => 'Penjualan',           'desc' => 'Rincian transaksi penjualan per periode'],
    ['url' => '/reports/purchases', 'icon' => '📦', 'label' => 'Pembelian',           'desc' => 'Rincian transaksi pembelian dari supplier'],
    ['url' => '/reports/profit',    'icon' => '💰', 'label' => 'Profit',              'desc' => 'Omzet, modal, dan estimasi profit per hari'],
    ['url' => '/reports/stock',     'icon' => '📊', 'label' => 'Stok',                'desc' => 'Kondisi stok & nilai stok saat ini'],
    ['url' => '/reports/products?type=best',  'icon' => '⭐', 'label' => 'Produk Terlaris',     'desc' => 'Produk dengan penjualan tertinggi'],
    ['url' => '/reports/products?type=worst', 'icon' => '📉', 'label' => 'Produk Tidak Laku',   'desc' => 'Produk yang belum terjual pada periode ini'],
    ['url' => '/reports/cashier',   'icon' => '👤', 'label' => 'Per Kasir',           'desc' => 'Rekap penjualan per kasir'],
    ['url' => '/reports/supplier',  'icon' => '🚚', 'label' => 'Per Supplier',        'desc' => 'Rekap pembelian per supplier'],
    ['url' => '/reports/member',    'icon' => '👥', 'label' => 'Per Member',          'desc' => 'Rekap belanja per pelanggan'],
];
?>

<h5 class="mb-3">Laporan</h5>

<div class="row g-3">
    <?php foreach ($reportLinks as $r): ?>
        <div class="col-md-4">
            <a href="<?= url($r['url']) ?>" class="text-decoration-none text-dark">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="fs-3"><?= $r['icon'] ?></div>
                        <h6 class="card-title mt-2 mb-1"><?= e($r['label']) ?></h6>
                        <p class="text-muted small mb-0"><?= e($r['desc']) ?></p>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
