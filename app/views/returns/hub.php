<?php
$title = 'Retur';
$currentRouteKey = 'returns.index';
ob_start();
?>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">↩️ Retur Penjualan</h5>
                <p class="text-muted small">Proses pengembalian barang dari pelanggan berdasarkan nomor invoice penjualan. Stok akan otomatis dikembalikan.</p>
                <a href="<?= url('/returns/sales') ?>" class="btn btn-outline-primary btn-sm">Lihat Daftar</a>
                <?php if (user_can('returns.index', 'create')): ?>
                    <a href="<?= url('/returns/sales/create') ?>" class="btn btn-primary btn-sm">+ Retur Baru</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">📦 Retur Pembelian</h5>
                <p class="text-muted small">Kembalikan barang ke supplier berdasarkan nomor invoice pembelian. Stok akan otomatis dikurangi.</p>
                <a href="<?= url('/returns/purchases') ?>" class="btn btn-outline-primary btn-sm">Lihat Daftar</a>
                <?php if (user_can('returns.index', 'create')): ?>
                    <a href="<?= url('/returns/purchases/create') ?>" class="btn btn-primary btn-sm">+ Retur Baru</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
