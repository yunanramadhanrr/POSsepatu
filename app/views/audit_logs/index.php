<?php
$title = 'Audit Log';
$currentRouteKey = 'audit_logs.index';
ob_start();
?>

<h5 class="mb-3">Audit Log</h5>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="<?= url('/audit-logs') ?>" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small">User</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (string) ($_GET['user_id'] ?? '') === (string) $u['id'] ? 'selected' : '' ?>>
                            <?= e($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Aksi</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach ($actions as $a): ?>
                        <option value="<?= e($a) ?>" <?= ($_GET['action'] ?? '') === $a ? 'selected' : '' ?>><?= e($a) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Tabel</label>
                <select name="table_name" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach ($tables as $t): ?>
                        <option value="<?= e($t) ?>" <?= ($_GET['table_name'] ?? '') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($_GET['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($_GET['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>User</th>
                    <th>Aksi</th>
                    <th>Tabel</th>
                    <th>Record ID</th>
                    <th>Detail</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada aktivitas yang cocok dengan filter.</td></tr>
                <?php endif; ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="small"><?= format_tanggal($log['created_at']) ?></td>
                        <td><?= e($log['user_name'] ?? 'Sistem') ?></td>
                        <td><span class="badge bg-secondary"><?= e($log['action']) ?></span></td>
                        <td class="small text-muted"><?= e($log['table_name'] ?? '-') ?></td>
                        <td class="small text-muted"><?= e($log['record_id'] ?? '-') ?></td>
                        <td class="small">
                            <?php if ($log['old_value'] || $log['new_value']): ?>
                                <?php if ($log['old_value']): ?><span class="text-danger">- <?= e($log['old_value']) ?></span><br><?php endif; ?>
                                <?php if ($log['new_value']): ?><span class="text-success">+ <?= e($log['new_value']) ?></span><?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= e($log['ip_address'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="text-muted small">Menampilkan maksimal 300 aktivitas terbaru yang sesuai filter.</p>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
