<?php
$title = 'Pelanggan';
$currentRouteKey = 'customers.index';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Daftar Pelanggan</h5>
    <?php if (user_can('customers.index', 'create')): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddCustomer">
            + Tambah Pelanggan
        </button>
    <?php endif; ?>
</div>

<form method="GET" action="<?= url('/customers') ?>" class="mb-3">
    <div class="input-group" style="max-width: 400px;">
        <input type="text" name="search" class="form-control" placeholder="Cari nama, no HP, atau kode member..."
               value="<?= e($_GET['search'] ?? '') ?>">
        <button class="btn btn-outline-secondary" type="submit">Cari</button>
    </div>
</form>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Kode Member</th>
                    <th>Nama</th>
                    <th>No. HP</th>
                    <th>Level</th>
                    <th>Poin</th>
                    <th style="width: 200px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada pelanggan.</td></tr>
                <?php endif; ?>
                <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><code><?= e($c['member_code']) ?></code></td>
                        <td><?= e($c['name']) ?></td>
                        <td><?= e($c['phone']) ?></td>
                        <td>
                            <span class="badge bg-info text-dark"><?= e($c['level_name'] ?? '-') ?></span>
                        </td>
                        <td><?= (int) $c['points'] ?></td>
                        <td class="text-end">
                            <a href="<?= url('/customers/' . $c['id']) ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                            <?php if (user_can('customers.index', 'edit')): ?>
                                <button class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditCustomer<?= $c['id'] ?>">
                                    Edit
                                </button>
                            <?php endif; ?>
                            <?php if (user_can('customers.index', 'delete')): ?>
                                <form method="POST" action="<?= url('/customers/' . $c['id'] . '/delete') ?>"
                                      class="d-inline" onsubmit="return confirm('Hapus pelanggan ini?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalEditCustomer<?= $c['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="<?= url('/customers/' . $c['id'] . '/update') ?>">
                                    <?= csrf_field() ?>
                                    <div class="modal-header">
                                        <h6 class="modal-title">Edit Pelanggan</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-2">
                                            <label class="form-label">Nama</label>
                                            <input type="text" name="name" class="form-control" value="<?= e($c['name']) ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">No. HP</label>
                                            <input type="text" name="phone" class="form-control" value="<?= e($c['phone']) ?>">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" value="<?= e($c['email']) ?>">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Tanggal Lahir</label>
                                            <input type="date" name="birth_date" class="form-control" value="<?= e($c['birth_date']) ?>">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Alamat</label>
                                            <textarea name="address" class="form-control" rows="2"><?= e($c['address']) ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (user_can('customers.index', 'edit')): ?>
<div class="card shadow-sm">
    <div class="card-body">
        <h6 class="card-title">Pengaturan Level Membership</h6>
        <p class="text-muted small">Atur ambang batas poin & persentase diskon otomatis untuk tiap level.</p>
        <table class="table table-sm align-middle">
            <thead><tr><th>Level</th><th>Minimal Poin</th><th>Diskon (%)</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($levels as $lvl): ?>
                    <tr>
                        <td class="fw-semibold align-middle"><?= e($lvl['name']) ?></td>
                        <td>
                            <input type="number" min="0" name="min_points" form="levelForm<?= $lvl['id'] ?>"
                                   class="form-control form-control-sm" value="<?= (int) $lvl['min_points'] ?>">
                        </td>
                        <td>
                            <input type="number" min="0" step="0.1" name="discount_percent" form="levelForm<?= $lvl['id'] ?>"
                                   class="form-control form-control-sm" value="<?= e($lvl['discount_percent']) ?>">
                        </td>
                        <td>
                            <button type="submit" form="levelForm<?= $lvl['id'] ?>" class="btn btn-sm btn-outline-primary">Simpan</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php foreach ($levels as $lvl): ?>
            <form id="levelForm<?= $lvl['id'] ?>" method="POST" action="<?= url('/membership-levels/' . $lvl['id'] . '/update') ?>">
                <?= csrf_field() ?>
            </form>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="modalAddCustomer" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('/customers') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h6 class="modal-title">Tambah Pelanggan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="birth_date" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <p class="text-muted small mb-0">Kode member akan digenerate otomatis, level awal Silver (0 poin).</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
