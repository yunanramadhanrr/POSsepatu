<?php
$title = 'Detail Stock Opname';
$currentRouteKey = 'stock.index';
ob_start();
?>

<a href="<?= url('/stock/opname') ?>" class="btn btn-sm btn-outline-secondary mb-3">&larr; Kembali</a>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
            <div>
                <p class="mb-0"><strong>No. Opname:</strong> <?= e($opname['opname_number']) ?></p>
                <p class="mb-0"><strong>Tanggal:</strong> <?= format_tanggal($opname['opname_date'], 'd-m-Y') ?></p>
            </div>
            <div class="text-end">
                <p class="mb-0"><strong>Dilakukan oleh:</strong> <?= e($opname['user_name']) ?></p>
            </div>
        </div>

        <?php if ($opname['note']): ?>
            <p class="text-muted small">Catatan: <?= e($opname['note']) ?></p>
        <?php endif; ?>

        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Varian</th>
                    <th class="text-end">Qty Sistem</th>
                    <th class="text-end">Qty Fisik</th>
                    <th class="text-end">Selisih</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($opname['items'] as $item): ?>
                    <tr>
                        <td><?= e($item['product_name']) ?></td>
                        <td><?= e($item['size']) ?>/<?= e($item['color']) ?></td>
                        <td class="text-end"><?= (int) $item['system_qty'] ?></td>
                        <td class="text-end"><?= (int) $item['physical_qty'] ?></td>
                        <td class="text-end fw-semibold <?= $item['difference'] == 0 ? 'text-muted' : ($item['difference'] > 0 ? 'text-success' : 'text-danger') ?>">
                            <?= $item['difference'] > 0 ? '+' : '' ?><?= (int) $item['difference'] ?>
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
