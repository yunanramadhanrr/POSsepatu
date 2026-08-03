<?php
$title = 'Detail Pelanggan';
$currentRouteKey = 'customers.index';
ob_start();
?>

<a href="<?= url('/customers') ?>" class="btn btn-sm btn-outline-secondary mb-3">&larr; Kembali</a>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-title"><?= e($customer['name']) ?></h6>
                <p class="mb-1"><span class="text-muted">Kode Member:</span> <code><?= e($customer['member_code']) ?></code></p>
                <p class="mb-1"><span class="text-muted">No. HP:</span> <?= e($customer['phone'] ?: '-') ?></p>
                <p class="mb-1"><span class="text-muted">Email:</span> <?= e($customer['email'] ?: '-') ?></p>
                <p class="mb-1"><span class="text-muted">Tanggal Lahir:</span> <?= $customer['birth_date'] ? format_tanggal($customer['birth_date'], 'd-m-Y') : '-' ?></p>
                <p class="mb-1"><span class="text-muted">Alamat:</span> <?= e($customer['address'] ?: '-') ?></p>
                <hr>
                <p class="mb-1">
                    <span class="badge bg-info text-dark fs-6"><?= e($customer['level_name'] ?? '-') ?></span>
                    <span class="text-muted small">(diskon otomatis <?= e($customer['discount_percent'] ?? 0) ?>%)</span>
                </p>
                <p class="fs-4 fw-bold mb-0"><?= (int) $customer['points'] ?> poin</p>
            </div>
        </div>

        <?php if (user_can('customers.index', 'edit')): ?>
        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <h6 class="card-title">Tukar Poin ke Voucher</h6>
                <p class="text-muted small">
                    1 poin = <?= format_rupiah(POINTS_TO_RUPIAH_RATE) ?>, minimal <?= MIN_POINTS_REDEEM ?> poin.
                    Voucher berlaku <?= VOUCHER_VALID_DAYS ?> hari.
                </p>
                <form method="POST" action="<?= url('/customers/' . $customer['id'] . '/redeem-points') ?>">
                    <?= csrf_field() ?>
                    <div class="input-group">
                        <input type="number" name="points" class="form-control" min="<?= MIN_POINTS_REDEEM ?>"
                               max="<?= (int) $customer['points'] ?>" step="<?= MIN_POINTS_REDEEM ?>"
                               placeholder="Jumlah poin" required>
                        <button type="submit" class="btn btn-primary">Tukar</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6 class="card-title">Riwayat Poin</h6>
                <?php if (empty($pointHistories)): ?>
                    <p class="text-muted small mb-0">Belum ada riwayat poin.</p>
                <?php else: ?>
                    <table class="table table-sm">
                        <thead><tr><th>Tanggal</th><th>Perubahan</th><th>Catatan</th></tr></thead>
                        <tbody>
                        <?php foreach ($pointHistories as $ph): ?>
                            <tr>
                                <td><?= format_tanggal($ph['created_at']) ?></td>
                                <td class="<?= $ph['points_change'] >= 0 ? 'text-success' : 'text-danger' ?> fw-semibold">
                                    <?= $ph['points_change'] >= 0 ? '+' : '' ?><?= (int) $ph['points_change'] ?>
                                </td>
                                <td><?= e($ph['note']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Riwayat Pembelian</h6>
                <?php if (empty($purchaseHistory)): ?>
                    <p class="text-muted small mb-0">
                        Belum ada transaksi. Data akan muncul otomatis setelah modul Kasir/Penjualan (Tahap 7) digunakan.
                    </p>
                <?php else: ?>
                    <table class="table table-sm">
                        <thead><tr><th>No. Invoice</th><th>Tanggal</th><th>Total</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($purchaseHistory as $sale): ?>
                            <tr>
                                <td><code><?= e($sale['invoice_number']) ?></code></td>
                                <td><?= format_tanggal($sale['sale_date']) ?></td>
                                <td><?= format_rupiah($sale['grand_total']) ?></td>
                                <td><?= e($sale['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
