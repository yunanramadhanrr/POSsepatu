<?php
$title = 'Kasir (POS)';
$currentRouteKey = 'sales.index';
$canEditPrice = in_array(current_user()['role_name'], ['Owner', 'Admin'], true);
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Kasir / Point of Sale</h5>
    <a href="<?= url('/sales/held') ?>" class="btn btn-sm btn-outline-warning">
        ⏸ Transaksi Held <span class="badge bg-warning text-dark"><?= (int) $heldCount ?></span>
    </a>
</div>

<?php if ($recallSale): ?>
<div class="alert alert-info py-2">
    Melanjutkan transaksi held: <strong><?= e($recallSale['invoice_number']) ?></strong>
</div>
<?php endif; ?>

<form id="posForm" method="POST" action="<?= url('/sales/checkout') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="customer_id" id="hiddenCustomerId" value="<?= e($recallSale['customer_id'] ?? '') ?>">
    <input type="hidden" name="voucher_id" id="hiddenVoucherId" value="<?= e($recallSale['voucher_id'] ?? '') ?>">
    <input type="hidden" name="existing_sale_id" id="hiddenExistingSaleId" value="<?= e($recallSale['id'] ?? '') ?>">

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <label class="form-label">Scan Barcode / Cari Produk</label>
                    <div class="position-relative">
                        <input type="text" id="productSearchInput" class="form-control" placeholder="Scan barcode atau ketik nama produk...">
                        <div id="productSearchResults" class="list-group position-absolute w-100 shadow-sm" style="z-index: 50; max-height: 320px; overflow-y: auto;"></div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Keranjang</h6>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="splitBillToggle">
                        <label class="form-check-label small" for="splitBillToggle">
                            Aktifkan Split Bill (pisah sebagian item ke transaksi terpisah)
                        </label>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle" id="cartTable">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th style="width: 80px;">Qty</th>
                                    <th style="width: 120px;">Harga</th>
                                    <th style="width: 110px;">Diskon</th>
                                    <th style="width: 120px;">Subtotal</th>
                                    <th class="split-col" style="width: 110px; display:none;">Bagian</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="cartTbody">
                                <tr id="emptyCartRow"><td colspan="7" class="text-center text-muted py-3">Keranjang kosong. Scan/cari produk di atas.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <label class="form-label">Pelanggan (opsional)</label>
                    <div class="position-relative">
                        <input type="text" id="customerSearchInput" class="form-control" placeholder="Cari nama/no HP/kode member...">
                        <div id="customerSearchResults" class="list-group position-absolute w-100 shadow-sm" style="z-index: 50;"></div>
                    </div>
                    <div id="selectedCustomerBox" class="mt-2 small"></div>

                    <label class="form-label mt-3">Kode Voucher (opsional)</label>
                    <div class="input-group">
                        <input type="text" id="voucherCodeInput" class="form-control" placeholder="VCR-XXXXXXXX">
                        <button type="button" id="btnApplyVoucher" class="btn btn-outline-secondary">Terapkan</button>
                    </div>
                    <div id="voucherResultBox" class="small mt-1"></div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <label class="form-label">Diskon Total Tambahan (Rp)</label>
                    <input type="number" min="0" step="0.01" name="discount_total" id="inputDiscountTotal" class="form-control mb-2" value="0">

                    <label class="form-label">Pajak (%)</label>
                    <input type="number" min="0" step="0.01" name="tax_percent" id="inputTaxPercent" class="form-control mb-2" value="<?= e($taxPercentDefault) ?>">

                    <label class="form-label">Catatan</label>
                    <textarea name="note" id="inputNote" class="form-control mb-2" rows="2"></textarea>

                    <hr>
                    <div class="d-flex justify-content-between"><span>Subtotal</span><span id="dispSubtotal">Rp 0</span></div>
                    <div class="d-flex justify-content-between"><span>Diskon (manual+member+voucher)</span><span id="dispDiscount">Rp 0</span></div>
                    <div class="d-flex justify-content-between"><span>Pajak</span><span id="dispTax">Rp 0</span></div>
                    <div class="d-flex justify-content-between fw-bold fs-5"><span>Grand Total</span><span id="dispGrandTotal">Rp 0</span></div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="card-title mb-0">Pembayaran</h6>
                        <button type="button" id="btnAddPayment" class="btn btn-sm btn-outline-primary">+ Metode</button>
                    </div>
                    <table class="table table-sm" id="paymentTable">
                        <tbody id="paymentTbody"></tbody>
                    </table>
                    <div class="d-flex justify-content-between"><span>Total Dibayar</span><span id="dispPaid">Rp 0</span></div>
                    <div class="d-flex justify-content-between fw-bold" id="dispStatusRow">
                        <span id="dispStatusLabel">Kekurangan</span><span id="dispStatusValue">Rp 0</span>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <button type="button" id="btnHold" class="btn btn-outline-secondary">💾 Hold Transaksi</button>
                        <button type="button" id="btnCheckout" class="btn btn-primary">💳 Bayar / Checkout</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
const CAN_EDIT_PRICE = <?= $canEditPrice ? 'true' : 'false' ?>;
const PAYMENT_METHODS = <?= json_encode($paymentMethods) ?>;
const SEARCH_PRODUCT_URL = <?= json_encode(url('/sales/search-product')) ?>;
const SEARCH_CUSTOMER_URL = <?= json_encode(url('/sales/search-customer')) ?>;
const VALIDATE_VOUCHER_URL = <?= json_encode(url('/sales/validate-voucher')) ?>;
const HOLD_URL = <?= json_encode(url('/sales/hold')) ?>;
const CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
const RECALL_DATA = <?= $recallSale ? json_encode($recallSale) : 'null' ?>;

let cart = [];       // {variant_id, product_name, size, color, price, qty, discount, stock, bill}
let selectedCustomer = null;
let appliedVoucher = null;
let cartRowSeq = 0;

function formatRupiah(n) {
    n = isFinite(n) ? n : 0;
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}

function debounce(fn, delay) {
    let t;
    return function (...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), delay);
    };
}

// ---------- Cart rendering ----------
function renderCart() {
    const tbody = document.getElementById('cartTbody');
    tbody.innerHTML = '';

    if (cart.length === 0) {
        tbody.innerHTML = '<tr id="emptyCartRow"><td colspan="7" class="text-center text-muted py-3">Keranjang kosong. Scan/cari produk di atas.</td></tr>';
        recalcTotals();
        return;
    }

    const splitMode = document.getElementById('splitBillToggle').checked;

    cart.forEach(function (item, idx) {
        const tr = document.createElement('tr');

        const priceAttr = CAN_EDIT_PRICE ? '' : 'readonly';

        tr.innerHTML = `
            <td>
                <input type="hidden" name="cart_variant_id[]" value="${item.variant_id}">
                <div class="fw-semibold">${item.product_name}</div>
                <div class="text-muted small">${item.size || ''}/${item.color || ''} ${item.barcode ? '- ' + item.barcode : ''}</div>
            </td>
            <td><input type="number" min="1" max="${item.stock}" name="cart_qty[]" class="form-control form-control-sm cart-qty" value="${item.qty}"></td>
            <td><input type="number" step="0.01" min="0" name="cart_price[]" class="form-control form-control-sm cart-price" value="${item.price}" ${priceAttr}></td>
            <td><input type="number" step="0.01" min="0" name="cart_discount[]" class="form-control form-control-sm cart-discount" value="${item.discount}"></td>
            <td class="cart-subtotal">${formatRupiah((item.qty * item.price) - item.discount)}</td>
            <td class="split-col" style="display:${splitMode ? '' : 'none'};">
                <select class="form-select form-select-sm cart-bill">
                    <option value="1" ${item.bill === 1 ? 'selected' : ''}>Bagian 1</option>
                    <option value="2" ${item.bill === 2 ? 'selected' : ''}>Bagian 2</option>
                </select>
            </td>
            <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">&times;</button></td>
        `;

        tr.querySelector('.cart-qty').addEventListener('input', function (e) {
            let v = parseInt(e.target.value) || 1;
            if (v > item.stock) { v = item.stock; e.target.value = v; }
            if (v < 1) { v = 1; e.target.value = v; }
            item.qty = v;
            updateRowSubtotal(tr, item);
            recalcTotals();
        });
        tr.querySelector('.cart-price').addEventListener('input', function (e) {
            item.price = parseFloat(e.target.value) || 0;
            updateRowSubtotal(tr, item);
            recalcTotals();
        });
        tr.querySelector('.cart-discount').addEventListener('input', function (e) {
            item.discount = parseFloat(e.target.value) || 0;
            updateRowSubtotal(tr, item);
            recalcTotals();
        });
        const billSelect = tr.querySelector('.cart-bill');
        if (billSelect) {
            billSelect.addEventListener('change', function (e) {
                item.bill = parseInt(e.target.value);
            });
        }
        tr.querySelector('.btn-remove-row').addEventListener('click', function () {
            cart.splice(idx, 1);
            renderCart();
        });

        tbody.appendChild(tr);
    });

    recalcTotals();
}

function updateRowSubtotal(tr, item) {
    tr.querySelector('.cart-subtotal').textContent = formatRupiah((item.qty * item.price) - item.discount);
}

function addToCart(product) {
    const existing = cart.find(c => c.variant_id === product.variant_id);
    if (existing) {
        if (existing.qty < existing.stock) existing.qty += 1;
    } else {
        cart.push({
            variant_id: product.variant_id,
            product_name: product.product_name,
            size: product.size,
            color: product.color,
            barcode: product.barcode,
            price: product.sell_price,
            qty: 1,
            discount: 0,
            stock: product.stock,
            bill: 1,
        });
    }
    renderCart();
}

// ---------- Product search ----------
const productSearchInput = document.getElementById('productSearchInput');
const productSearchResults = document.getElementById('productSearchResults');

const doProductSearch = debounce(function () {
    const q = productSearchInput.value.trim();
    productSearchResults.innerHTML = '';
    if (q === '') return;

    fetch(SEARCH_PRODUCT_URL + '?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(function (results) {
            productSearchResults.innerHTML = '';
            if (results.length === 0) {
                productSearchResults.innerHTML = '<div class="list-group-item text-muted small">Produk tidak ditemukan.</div>';
                return;
            }
            results.forEach(function (p) {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action';
                item.innerHTML = `<strong>${p.product_name}</strong> (${p.size || '-'}/${p.color || '-'})
                    <span class="text-muted small d-block">Stok: ${p.stock} — ${formatRupiah(p.sell_price)}</span>`;
                item.addEventListener('click', function () {
                    if (p.stock <= 0) { alert('Stok produk ini habis.'); return; }
                    addToCart(p);
                    productSearchInput.value = '';
                    productSearchResults.innerHTML = '';
                    productSearchInput.focus();
                });
                productSearchResults.appendChild(item);
            });
        });
}, 300);

productSearchInput.addEventListener('input', doProductSearch);
productSearchInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        // Skenario scan barcode: exact match langsung ditambahkan (server mengembalikan 1 hasil exact bila barcode cocok)
        const q = productSearchInput.value.trim();
        if (q === '') return;
        fetch(SEARCH_PRODUCT_URL + '?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(function (results) {
                if (results.length >= 1) {
                    if (results[0].stock <= 0) { alert('Stok produk ini habis.'); return; }
                    addToCart(results[0]);
                    productSearchInput.value = '';
                    productSearchResults.innerHTML = '';
                }
            });
    }
});
document.addEventListener('click', function (e) {
    if (!productSearchResults.contains(e.target) && e.target !== productSearchInput) {
        productSearchResults.innerHTML = '';
    }
});

// ---------- Customer search ----------
const customerSearchInput = document.getElementById('customerSearchInput');
const customerSearchResults = document.getElementById('customerSearchResults');

const doCustomerSearch = debounce(function () {
    const q = customerSearchInput.value.trim();
    customerSearchResults.innerHTML = '';
    if (q === '') return;

    fetch(SEARCH_CUSTOMER_URL + '?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(function (results) {
            customerSearchResults.innerHTML = '';
            results.forEach(function (c) {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action';
                item.innerHTML = `<strong>${c.name}</strong> <span class="text-muted small">(${c.member_code})</span>
                    <span class="text-muted small d-block">${c.level_name} — ${c.points} poin — diskon ${c.discount_percent}%</span>`;
                item.addEventListener('click', function () {
                    selectCustomer(c);
                    customerSearchInput.value = '';
                    customerSearchResults.innerHTML = '';
                });
                customerSearchResults.appendChild(item);
            });
        });
}, 300);
customerSearchInput.addEventListener('input', doCustomerSearch);

function selectCustomer(c) {
    selectedCustomer = c;
    document.getElementById('hiddenCustomerId').value = c.id;
    document.getElementById('selectedCustomerBox').innerHTML = `
        <div class="alert alert-info py-1 px-2 mb-0 d-flex justify-content-between align-items-center">
            <span>${c.name} (${c.level_name}, diskon ${c.discount_percent}%)</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRemoveCustomer">&times;</button>
        </div>`;
    document.getElementById('btnRemoveCustomer').addEventListener('click', function () {
        selectedCustomer = null;
        document.getElementById('hiddenCustomerId').value = '';
        document.getElementById('selectedCustomerBox').innerHTML = '';
        recalcTotals();
    });
    recalcTotals();
}

// ---------- Voucher ----------
document.getElementById('btnApplyVoucher').addEventListener('click', function () {
    const code = document.getElementById('voucherCodeInput').value.trim();
    const box = document.getElementById('voucherResultBox');
    if (code === '') return;

    const form = new FormData();
    form.append('csrf_token', CSRF_TOKEN);
    form.append('code', code);

    fetch(VALIDATE_VOUCHER_URL, { method: 'POST', body: form })
        .then(r => r.json())
        .then(function (res) {
            if (!res.valid) {
                appliedVoucher = null;
                document.getElementById('hiddenVoucherId').value = '';
                box.innerHTML = `<span class="text-danger">${res.message}</span>`;
                recalcTotals();
                return;
            }
            appliedVoucher = res;
            document.getElementById('hiddenVoucherId').value = res.id;
            const label = res.value_type === 'percent' ? res.value + '%' : formatRupiah(res.value);
            box.innerHTML = `<span class="text-success">Voucher ${res.code} diterapkan (${label})</span>`;
            recalcTotals();
        });
});

// ---------- Split bill toggle ----------
document.getElementById('splitBillToggle').addEventListener('change', function () {
    document.querySelectorAll('.split-col').forEach(el => el.style.display = this.checked ? '' : 'none');
    renderCart();
    document.getElementById('btnCheckout').textContent = this.checked
        ? '💳 Proses Split Bill (Bagian 1 bayar, Bagian 2 di-hold)'
        : '💳 Bayar / Checkout';
});

// ---------- Payment rows ----------
function addPaymentRow() {
    const tbody = document.getElementById('paymentTbody');
    const tr = document.createElement('tr');
    const options = PAYMENT_METHODS.map(pm => `<option value="${pm.id}">${pm.name}</option>`).join('');
    tr.innerHTML = `
        <td>
            <select name="pay_method_id[]" class="form-select form-select-sm">${options}</select>
        </td>
        <td><input type="number" step="0.01" min="0" name="pay_amount[]" class="form-control form-control-sm pay-amount" value="0"></td>
        <td><input type="text" name="pay_reference[]" class="form-control form-control-sm" placeholder="No. Ref (opsional)"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-payment">&times;</button></td>
    `;
    tr.querySelector('.pay-amount').addEventListener('input', recalcTotals);
    tr.querySelector('.btn-remove-payment').addEventListener('click', function () {
        tr.remove();
        recalcTotals();
    });
    tbody.appendChild(tr);
}
document.getElementById('btnAddPayment').addEventListener('click', addPaymentRow);

// ---------- Totals ----------
function recalcTotals() {
    let subtotal = 0;
    cart.forEach(item => subtotal += (item.qty * item.price) - item.discount);

    const manualDiscount = parseFloat(document.getElementById('inputDiscountTotal').value) || 0;

    let voucherDiscount = 0;
    if (appliedVoucher) {
        voucherDiscount = appliedVoucher.value_type === 'percent'
            ? subtotal * (appliedVoucher.value / 100)
            : appliedVoucher.value;
    }

    let memberDiscount = 0;
    if (selectedCustomer && selectedCustomer.discount_percent) {
        memberDiscount = subtotal * (selectedCustomer.discount_percent / 100);
    }

    const discountTotal = manualDiscount + voucherDiscount + memberDiscount;
    const taxable = Math.max(0, subtotal - discountTotal);
    const taxPercent = parseFloat(document.getElementById('inputTaxPercent').value) || 0;
    const tax = taxable * (taxPercent / 100);
    const grandTotal = taxable + tax;

    document.getElementById('dispSubtotal').textContent = formatRupiah(subtotal);
    document.getElementById('dispDiscount').textContent = formatRupiah(discountTotal);
    document.getElementById('dispTax').textContent = formatRupiah(tax);
    document.getElementById('dispGrandTotal').textContent = formatRupiah(grandTotal);

    let totalPaid = 0;
    document.querySelectorAll('.pay-amount').forEach(inp => totalPaid += parseFloat(inp.value) || 0);
    document.getElementById('dispPaid').textContent = formatRupiah(totalPaid);

    const diff = totalPaid - grandTotal;
    const statusLabel = document.getElementById('dispStatusLabel');
    const statusValue = document.getElementById('dispStatusValue');
    if (diff < 0) {
        statusLabel.textContent = 'Kekurangan Pembayaran';
        statusValue.textContent = formatRupiah(Math.abs(diff));
        statusValue.className = 'text-danger';
    } else {
        statusLabel.textContent = 'Kembalian';
        statusValue.textContent = formatRupiah(diff);
        statusValue.className = 'text-success';
    }
}

document.getElementById('inputDiscountTotal').addEventListener('input', recalcTotals);
document.getElementById('inputTaxPercent').addEventListener('input', recalcTotals);

// ---------- Hold ----------
document.getElementById('btnHold').addEventListener('click', function () {
    if (cart.length === 0) { alert('Keranjang masih kosong.'); return; }
    const form = document.getElementById('posForm');
    form.action = HOLD_URL;
    form.submit();
});

// ---------- Checkout / Split Bill ----------
document.getElementById('btnCheckout').addEventListener('click', function () {
    if (cart.length === 0) { alert('Keranjang masih kosong.'); return; }

    const splitMode = document.getElementById('splitBillToggle').checked;
    const form = document.getElementById('posForm');

    if (!splitMode) {
        form.action = <?= json_encode(url('/sales/checkout')) ?>;
        form.submit();
        return;
    }

    // Mode Split Bill: bagian 2 di-hold via AJAX terlebih dahulu, baru bagian 1 diproses normal (full submit)
    const bagian2 = cart.filter(c => c.bill === 2);
    const bagian1 = cart.filter(c => c.bill !== 2);

    if (bagian1.length === 0) { alert('Bagian 1 tidak boleh kosong.'); return; }

    if (bagian2.length === 0) {
        // Tidak ada yang dipisah, proses seperti biasa
        form.action = <?= json_encode(url('/sales/checkout')) ?>;
        form.submit();
        return;
    }

    const fd = new FormData();
    fd.append('csrf_token', CSRF_TOKEN);
    bagian2.forEach(function (item) {
        fd.append('cart_variant_id[]', item.variant_id);
        fd.append('cart_qty[]', item.qty);
        fd.append('cart_price[]', item.price);
        fd.append('cart_discount[]', item.discount);
    });
    fd.append('discount_total', '0');
    fd.append('tax_percent', document.getElementById('inputTaxPercent').value);
    fd.append('note', 'Split bill - bagian 2 dari transaksi ini');

    fetch(HOLD_URL, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(function (res) {
            if (!res.success) {
                alert('Gagal memisah bagian 2: ' + (res.message || 'Terjadi kesalahan.'));
                return;
            }
            alert('Bagian 2 berhasil di-hold sebagai transaksi ' + res.invoice_number + '. Silakan lanjutkan pembayaran bagian 1.');
            // Hapus baris bagian 2 dari form sebelum submit bagian 1
            cart = bagian1;
            renderCart();
            form.action = <?= json_encode(url('/sales/checkout')) ?>;
            form.submit();
        })
        .catch(function () {
            alert('Gagal menghubungi server untuk memisah bagian 2.');
        });
});

// ---------- Muat data recall (jika ada) ----------
if (RECALL_DATA) {
    RECALL_DATA.items.forEach(function (it) {
        cart.push({
            variant_id: it.product_variant_id,
            product_name: it.product_name,
            size: it.size,
            color: it.color,
            barcode: it.barcode,
            price: parseFloat(it.price),
            qty: parseInt(it.qty),
            discount: parseFloat(it.discount),
            stock: 999999, // batas stok riil akan tetap divalidasi ulang di server saat checkout
            bill: 1,
        });
    });
    document.getElementById('inputDiscountTotal').value = 0; // voucher/member dihitung ulang otomatis
    document.getElementById('inputNote').value = RECALL_DATA.note || '';
    if (RECALL_DATA.customer_id) {
        selectCustomer({
            id: RECALL_DATA.customer_id,
            name: RECALL_DATA.customer_name,
            member_code: RECALL_DATA.member_code,
            points: RECALL_DATA.customer_points,
            level_name: '',
            discount_percent: 0,
        });
    }
    renderCart();
}

// Mulai dengan 1 baris metode pembayaran default
addPaymentRow();
recalcTotals();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
