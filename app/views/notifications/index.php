<?php
$title = 'Notifikasi';
$currentRouteKey = '';
ob_start();
?>

<h5 class="mb-3">Notifikasi</h5>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-title">⚠️ Produk Hampir Habis (<?= count($lowStock) ?>)</h6>
                <?php if (empty($lowStock)): ?>
                    <p class="text-muted small mb-0">Tidak ada produk yang hampir habis. 🎉</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($lowStock as $v): ?>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span><?= e($v['product_name']) ?> <span class="text-muted small">(<?= e($v['size']) ?>/<?= e($v['color']) ?>)</span></span>
                                <span class="badge bg-warning text-dark">Sisa <?= (int) $v['stock'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-title">🚫 Produk Habis (<?= count($outOfStock) ?>)</h6>
                <?php if (empty($outOfStock)): ?>
                    <p class="text-muted small mb-0">Tidak ada produk yang habis total. 🎉</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($outOfStock as $v): ?>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span><?= e($v['product_name']) ?> <span class="text-muted small">(<?= e($v['size']) ?>/<?= e($v['color']) ?>)</span></span>
                                <span class="badge bg-danger">Habis</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-title">🎂 Member Ulang Tahun Hari Ini (<?= count($birthdays) ?>)</h6>
                <?php if (empty($birthdays)): ?>
                    <p class="text-muted small mb-0">Tidak ada member yang berulang tahun hari ini.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($birthdays as $c): ?>
                            <li class="list-group-item px-0">
                                <?= e($c['name']) ?> <span class="text-muted small">(<?= e($c['member_code']) ?>)</span>
                                — <?= e($c['phone']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="text-muted small mt-2 mb-0">Pertimbangkan kirim ucapan atau voucher spesial ulang tahun. 🎁</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-title">💾 Status Backup Database</h6>
                <?php if (!$lastBackupAt): ?>
                    <p class="text-danger mb-0">Belum pernah melakukan backup database sama sekali!</p>
                <?php elseif ($backupReminder): ?>
                    <p class="text-warning mb-0">
                        Backup terakhir <?= (int) $daysSinceBackup ?> hari yang lalu (<?= format_tanggal($lastBackupAt) ?>).
                        Disarankan backup rutin minimal seminggu sekali.
                    </p>
                <?php else: ?>
                    <p class="text-success mb-0">
                        Backup terakhir <?= (int) $daysSinceBackup ?> hari yang lalu (<?= format_tanggal($lastBackupAt) ?>). Masih aman. ✅
                    </p>
                <?php endif; ?>
                <a href="<?= url('/settings') ?>" class="btn btn-sm btn-outline-primary mt-2">Buka Halaman Backup</a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
