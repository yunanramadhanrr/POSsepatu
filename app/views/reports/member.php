<?php
$title = 'Laporan per Member';
$currentRouteKey = 'reports.index';
$reportPath = '/reports/member';
ob_start();
?>

<a href="<?= url('/reports') ?>" class="btn btn-sm btn-outline-secondary mb-3 no-print">&larr; Kembali</a>
<h5 class="mb-3">Laporan per Member</h5>

<?php require __DIR__ . '/_filter.php'; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-sm table-hover">
            <thead><tr><th>Nama</th><th>Kode Member</th><th class="text-end">Total Transaksi</th><th class="text-end">Total Belanja</th></tr></thead>
            <tbody>
                <?php if (empty($detail)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada data pada periode ini.</td></tr>
                <?php endif; ?>
                <?php foreach ($detail as $r): ?>
                    <tr>
                        <td><?= e($r['customer_name']) ?></td>
                        <td><code><?= e($r['member_code']) ?></code></td>
                        <td class="text-end"><?= (int) $r['total_transaksi'] ?></td>
                        <td class="text-end fw-semibold"><?= format_rupiah($r['total_belanja']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
