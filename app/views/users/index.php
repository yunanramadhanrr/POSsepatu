<?php
$title = 'Manajemen User';
$currentRouteKey = 'users.index';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Manajemen User</h5>
    <a href="<?= url('/users/create') ?>" class="btn btn-primary btn-sm">+ Tambah User</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Login Terakhir</th>
                    <th style="width: 220px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <?= e($u['name']) ?>
                            <?php if ((int) $u['id'] === (int) current_user()['id']): ?>
                                <span class="badge bg-info text-dark">Anda</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="badge bg-primary"><?= e($u['role_name']) ?></span></td>
                        <td>
                            <span class="badge <?= $u['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $u['status'] === 'active' ? 'Aktif' : 'Tidak Aktif' ?>
                            </span>
                        </td>
                        <td class="small text-muted"><?= $u['last_login_at'] ? format_tanggal($u['last_login_at']) : '-' ?></td>
                        <td class="text-end">
                            <a href="<?= url('/users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <?php if ((int) $u['id'] !== (int) current_user()['id']): ?>
                                <form method="POST" action="<?= url('/users/' . $u['id'] . '/toggle-status') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                        <?= $u['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                    </button>
                                </form>
                                <form method="POST" action="<?= url('/users/' . $u['id'] . '/delete') ?>" class="d-inline"
                                      onsubmit="return confirm('Hapus user ini secara permanen? Aksi ini hanya berhasil jika user belum pernah bertransaksi.');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-muted small mt-3">
    Catatan: "Hapus" hanya berhasil untuk user yang belum pernah membuat transaksi penjualan/pembelian.
    Untuk user yang sudah pernah bertransaksi, gunakan "Nonaktifkan" agar riwayat data tetap utuh namun
    user tidak bisa login lagi.
</p>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
