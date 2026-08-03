<?php
$title = 'Laporan Profit';
$currentRouteKey = 'reports.index';
$reportPath = '/reports/profit';
ob_start();
?>

<a href="<?= url('/reports') ?>" class="btn btn-sm btn-outline-secondary mb-3 no-print">&larr; Kembali</a>
<h5 class="mb-3">Laporan Profit</h5>

<?php require __DIR__ . '/_filter.php'; ?>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
        <div class="text-muted small">Total Omzet</div><div class="fs-6 fw-bold"><?= format_rupiah($totalOmzet) ?></div>
    </div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
        <div class="text-muted small">Total Modal (COGS)</div><div class="fs-6 fw-bold"><?= format_rupiah($totalModal) ?></div>
    </div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
        <div class="text-muted small">Profit Kotor</div><div class="fs-6 fw-bold text-success"><?= format_rupiah($totalProfit) ?></div>
    </div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body">
        <div class="text-muted small">Laba Bersih (setelah Pengeluaran)</div>
        <div class="fs-6 fw-bold <?= $totalLabaBersih >= 0 ? 'text-success' : 'text-danger' ?>"><?= format_rupiah($totalLabaBersih) ?></div>
    </div></div></div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-sm table-hover">
            <thead><tr><th>Tanggal</th><th class="text-end">Omzet</th><th class="text-end">Modal</th><th class="text-end">Profit Kotor</th><th class="text-end">Pengeluaran</th><th class="text-end">Laba Bersih</th></tr></thead>
            <tbody>
                <?php if (empty($detail)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data pada periode ini.</td></tr>
                <?php endif; ?>
                <?php foreach ($detail as $r): ?>
                    <tr>
                        <td><?= format_tanggal($r['tanggal'], 'd-m-Y') ?></td>
                        <td class="text-end"><?= format_rupiah($r['omzet']) ?></td>
                        <td class="text-end"><?= format_rupiah($r['modal']) ?></td>
                        <td class="text-end text-success"><?= format_rupiah($r['profit']) ?></td>
                        <td class="text-end text-danger"><?= format_rupiah($r['pengeluaran']) ?></td>
                        <td class="text-end fw-semibold <?= $r['laba_bersih'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= format_rupiah($r['laba_bersih']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-muted small mt-2">
    Profit Kotor = harga jual &minus; harga modal. Laba Bersih = Profit Kotor &minus; Pengeluaran operasional pada tanggal yang sama.
</p>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
