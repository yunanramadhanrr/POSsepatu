<?php
$title = 'Pengeluaran';
$currentRouteKey = 'expenses.index';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Pengeluaran Operasional</h5>
    <?php if (user_can('expenses.index', 'create')): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddExpense">
            + Catat Pengeluaran
        </button>
    <?php endif; ?>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="<?= url('/expenses') ?>" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Dari Tanggal</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Sampai Tanggal</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="text-muted small">Total Pengeluaran Periode Ini</div>
        <div class="fs-4 fw-bold text-danger"><?= format_rupiah($total) ?></div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Catatan</th>
                    <th class="text-end">Jumlah</th>
                    <th>Dicatat oleh</th>
                    <th style="width: 140px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expenses)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada pengeluaran pada periode ini.</td></tr>
                <?php endif; ?>
                <?php foreach ($expenses as $ex): ?>
                    <tr>
                        <td><?= format_tanggal($ex['expense_date'], 'd-m-Y') ?></td>
                        <td><span class="badge bg-secondary"><?= e($ex['category_name']) ?></span></td>
                        <td><?= e($ex['note']) ?></td>
                        <td class="text-end fw-semibold text-danger"><?= format_rupiah($ex['amount']) ?></td>
                        <td class="small text-muted"><?= e($ex['user_name']) ?></td>
                        <td class="text-end">
                            <?php if (user_can('expenses.index', 'edit')): ?>
                                <button class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#modalEditExpense<?= $ex['id'] ?>">Edit</button>
                            <?php endif; ?>
                            <?php if (user_can('expenses.index', 'delete')): ?>
                                <form method="POST" action="<?= url('/expenses/' . $ex['id'] . '/delete') ?>"
                                      class="d-inline" onsubmit="return confirm('Hapus data pengeluaran ini?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <div class="modal fade" id="modalEditExpense<?= $ex['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="<?= url('/expenses/' . $ex['id'] . '/update') ?>">
                                    <?= csrf_field() ?>
                                    <div class="modal-header">
                                        <h6 class="modal-title">Edit Pengeluaran</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-2">
                                            <label class="form-label">Kategori</label>
                                            <select name="expense_category_id" class="form-select" required>
                                                <?php foreach ($categories as $c): ?>
                                                    <option value="<?= $c['id'] ?>" <?= (int) $ex['expense_category_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                                                        <?= e($c['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Tanggal</label>
                                            <input type="date" name="expense_date" class="form-control" value="<?= e($ex['expense_date']) ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Jumlah (Rp)</label>
                                            <input type="number" step="0.01" min="0" name="amount" class="form-control" value="<?= e($ex['amount']) ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Catatan</label>
                                            <textarea name="note" class="form-control" rows="2"><?= e($ex['note']) ?></textarea>
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

<div class="modal fade" id="modalAddExpense" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('/expenses') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h6 class="modal-title">Catat Pengeluaran</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Kategori</label>
                        <select name="expense_category_id" class="form-select" required>
                            <option value="">- Pilih Kategori -</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Jumlah (Rp)</label>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Catatan</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Contoh: tagihan listrik bulan Juli"></textarea>
                    </div>
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
