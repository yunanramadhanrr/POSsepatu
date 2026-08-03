<?php
$title = 'Pembelian Baru';
$currentRouteKey = 'purchases.index';
ob_start();
?>

<form method="POST" action="<?= url('/purchases') ?>" id="purchaseForm">
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">Informasi Pembelian</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">No. Invoice (otomatis)</label>
                            <input type="text" class="form-control" value="<?= e($generatedInvoice) ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-select" required>
                                <option value="">- Pilih Supplier -</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0">Daftar Produk</h6>
                        <button type="button" id="btnAddItem" class="btn btn-sm btn-outline-primary">+ Tambah Produk</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="itemTable">
                            <thead>
                                <tr>
                                    <th style="min-width: 260px;">Produk (Ukuran/Warna)</th>
                                    <th style="width: 100px;">Qty</th>
                                    <th style="width: 150px;">Harga Satuan</th>
                                    <th style="width: 150px;">Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="itemTbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span id="displaySubtotal">Rp 0</span>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">PPN (Rp)</label>
                        <input type="number" step="0.01" min="0" name="tax" id="inputTax" class="form-control" value="0">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Diskon (Rp)</label>
                        <input type="number" step="0.01" min="0" name="discount" id="inputDiscount" class="form-control" value="0">
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Grand Total</strong>
                        <strong id="displayGrandTotal">Rp 0</strong>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-2">Simpan Pembelian</button>
                    <a href="<?= url('/purchases') ?>" class="btn btn-outline-secondary w-100">Batal</a>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="itemRowTemplate">
    <tr>
        <td>
            <select name="item_variant_id[]" class="form-select form-select-sm variant-select" required>
                <option value="">- Pilih Produk -</option>
                <?php foreach ($variants as $v): ?>
                    <option value="<?= $v['id'] ?>" data-price="<?= e($v['cost_price']) ?>">
                        <?= e($v['product_name']) ?> (<?= e($v['size']) ?>/<?= e($v['color']) ?>)
                        <?= $v['barcode'] ? '- ' . e($v['barcode']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" min="1" name="item_qty[]" class="form-control form-control-sm item-qty" value="1"></td>
        <td><input type="number" step="0.01" min="0" name="item_price[]" class="form-control form-control-sm item-price" value="0"></td>
        <td><span class="item-subtotal">Rp 0</span></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btnRemoveItem">&times;</button></td>
    </tr>
</template>

<script>
(function () {
    const tbody = document.getElementById('itemTbody');
    const template = document.getElementById('itemRowTemplate');

    function formatRupiah(n) {
        return 'Rp ' + Math.round(n).toLocaleString('id-ID');
    }

    function recalcRow(row) {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const subtotal = qty * price;
        row.querySelector('.item-subtotal').textContent = formatRupiah(subtotal);
        return subtotal;
    }

    function recalcAll() {
        let subtotal = 0;
        tbody.querySelectorAll('tr').forEach(function (row) {
            subtotal += recalcRow(row);
        });
        const tax = parseFloat(document.getElementById('inputTax').value) || 0;
        const discount = parseFloat(document.getElementById('inputDiscount').value) || 0;
        document.getElementById('displaySubtotal').textContent = formatRupiah(subtotal);
        document.getElementById('displayGrandTotal').textContent = formatRupiah(subtotal + tax - discount);
    }

    function addRow() {
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('tr');

        row.querySelector('.btnRemoveItem').addEventListener('click', function () {
            row.remove();
            recalcAll();
        });
        row.querySelector('.variant-select').addEventListener('change', function (e) {
            const opt = e.target.options[e.target.selectedIndex];
            const price = opt ? opt.getAttribute('data-price') : 0;
            row.querySelector('.item-price').value = price || 0;
            recalcAll();
        });
        row.querySelector('.item-qty').addEventListener('input', recalcAll);
        row.querySelector('.item-price').addEventListener('input', recalcAll);

        tbody.appendChild(clone);
    }

    document.getElementById('btnAddItem').addEventListener('click', addRow);
    document.getElementById('inputTax').addEventListener('input', recalcAll);
    document.getElementById('inputDiscount').addEventListener('input', recalcAll);

    addRow(); // mulai dengan 1 baris
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
