<?php
$title = 'Retur Pembelian';
$currentRouteKey = 'returns.index';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="<?= url('/returns') ?>" class="btn btn-sm btn-outline-secondary">&larr; Kembali</a>
    <?php if (user_can('returns.index', 'create')): ?>
        <a href="<?= url('/returns/purchases/create') ?>" class="btn btn-primary btn-sm">+ Retur Pembelian Baru</a>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h6 class="card-title">Daftar Retur Pembelian</h6>
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>No. Retur</th>
                    <th>Invoice Pembelian</th>
                    <th>Tanggal</th>
                    <th>Supplier</th>
                    <th>Diproses oleh</th>
                    <th>Total</th>
                    <th style="width: 100px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($returns)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada retur pembelian.</td></tr>
                <?php endif; ?>
                <?php foreach ($returns as $r): ?>
                    <tr>
                        <td><code><?= e($r['return_number']) ?></code></td>
                        <td><code><?= e($r['invoice_number']) ?></code></td>
                        <td><?= format_tanggal($r['return_date'], 'd-m-Y') ?></td>
                        <td><?= e($r['supplier_name']) ?></td>
                        <td><?= e($r['user_name']) ?></td>
                        <td><?= format_rupiah($r['total']) ?></td>
                        <td class="text-end">
                            <a href="<?= url('/returns/purchases/' . $r['id']) ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
