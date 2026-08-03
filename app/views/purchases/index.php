<?php
$title = 'Pembelian Barang';
$currentRouteKey = 'purchases.index';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Daftar Pembelian</h5>
    <?php if (user_can('purchases.index', 'create')): ?>
        <a href="<?= url('/purchases/create') ?>" class="btn btn-primary btn-sm">+ Pembelian Baru</a>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Tanggal</th>
                    <th>Supplier</th>
                    <th>Dibuat oleh</th>
                    <th>Grand Total</th>
                    <th>Status</th>
                    <th style="width: 100px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($purchases)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada transaksi pembelian.</td></tr>
                <?php endif; ?>
                <?php foreach ($purchases as $p): ?>
                    <tr>
                        <td><code><?= e($p['invoice_number']) ?></code></td>
                        <td><?= format_tanggal($p['purchase_date'], 'd-m-Y') ?></td>
                        <td><?= e($p['supplier_name']) ?></td>
                        <td><?= e($p['user_name']) ?></td>
                        <td><?= format_rupiah($p['grand_total']) ?></td>
                        <td>
                            <span class="badge <?= $p['status'] === 'completed' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= e($p['status']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('/purchases/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary">Detail</a>
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
