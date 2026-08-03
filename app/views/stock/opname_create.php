<?php
$title = 'Stock Opname Baru';
$currentRouteKey = 'stock.index';
ob_start();
?>

<a href="<?= url('/stock/opname') ?>" class="btn btn-sm btn-outline-secondary mb-3">&larr; Kembali</a>

<div class="card shadow-sm">
    <div class="card-body">
        <h6 class="card-title">Stock Opname — Hitung Fisik</h6>
        <p class="text-muted small">
            Isi kolom "Qty Fisik" hanya untuk produk yang benar-benar dihitung. Produk yang dikosongkan
            akan dilewati (tidak dianggap 0). Selisih dihitung otomatis: <strong>Qty Fisik − Qty Sistem</strong>,
            dan stok sistem akan disesuaikan mengikuti hasil hitung fisik setelah disimpan.
        </p>

        <form method="POST" action="<?= url('/stock/opname') ?>">
            <?= csrf_field() ?>

            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-sm align-middle">
                    <thead class="sticky-top bg-white">
                        <tr>
                            <th>Produk</th>
                            <th>Varian</th>
                            <th>Qty Sistem</th>
                            <th style="width: 130px;">Qty Fisik</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($variants as $v): ?>
                            <tr>
                                <td>
                                    <?= e($v['product_name']) ?>
                                    <input type="hidden" name="variant_id[]" value="<?= $v['id'] ?>">
                                </td>
                                <td><?= e($v['size']) ?>/<?= e($v['color']) ?></td>
                                <td><?= (int) $v['stock'] ?></td>
                                <td><input type="number" min="0" name="physical_qty[]" class="form-control form-control-sm" placeholder="-"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mb-3 mt-3">
                <label class="form-label">Catatan Sesi</label>
                <textarea name="note" class="form-control" rows="2" placeholder="Contoh: Opname rutin akhir bulan Juli 2026"></textarea>
            </div>

            <button type="submit" class="btn btn-warning">Proses Stock Opname</button>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
