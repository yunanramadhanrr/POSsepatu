<?php
$title = 'Transaksi Held';
$currentRouteKey = 'sales.index';
ob_start();
?>

<a href="<?= url('/sales') ?>" class="btn btn-sm btn-outline-secondary mb-3">&larr; Kembali ke Kasir</a>

<div class="card shadow-sm">
    <div class="card-body">
        <h6 class="card-title">Daftar Transaksi Held</h6>
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Waktu Hold</th>
                    <th>Kasir</th>
                    <th>Pelanggan</th>
                    <th>Item</th>
                    <th>Grand Total</th>
                    <th style="width: 220px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($heldSales)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada transaksi yang di-hold.</td></tr>
                <?php endif; ?>
                <?php foreach ($heldSales as $s): ?>
                    <tr>
                        <td><code><?= e($s['invoice_number']) ?></code></td>
                        <td><?= format_tanggal($s['held_at']) ?></td>
                        <td><?= e($s['user_name']) ?></td>
                        <td><?= e($s['customer_name'] ?? '-') ?></td>
                        <td><?= (int) $s['total_items'] ?> item</td>
                        <td><?= format_rupiah($s['grand_total']) ?></td>
                        <td class="text-end">
                            <a href="<?= url('/sales?recall_id=' . $s['id']) ?>" class="btn btn-sm btn-primary">Lanjutkan</a>
                            <form method="POST" action="<?= url('/sales/' . $s['id'] . '/cancel-hold') ?>"
                                  class="d-inline" onsubmit="return confirm('Batalkan transaksi held ini?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">Batalkan</button>
                            </form>
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
