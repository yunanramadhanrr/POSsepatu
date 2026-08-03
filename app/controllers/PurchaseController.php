<?php
require_once __DIR__ . '/../models/Purchase.php';
require_once __DIR__ . '/../models/Supplier.php';
require_once __DIR__ . '/../models/ProductVariant.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Validation.php';

class PurchaseController
{
    /** GET /purchases */
    public function index(): void
    {
        RoleMiddleware::handle('purchases.index', 'view');
        $purchases = Purchase::allWithSupplier();
        require __DIR__ . '/../views/purchases/index.php';
    }

    /** GET /purchases/create */
    public function create(): void
    {
        RoleMiddleware::handle('purchases.index', 'create');

        $suppliers = Supplier::all('name ASC');
        $variants = ProductVariant::allWithProductName();
        $generatedInvoice = Purchase::generateInvoiceNumber();

        require __DIR__ . '/../views/purchases/create.php';
    }

    /** POST /purchases */
    public function store(): void
    {
        RoleMiddleware::handle('purchases.index', 'create');

        $validator = new Validation($_POST);
        $validator->required('supplier_id', 'Supplier wajib dipilih')
                  ->required('purchase_date', 'Tanggal pembelian wajib diisi');

        $items = $this->extractItemsFromPost();
        if (empty($items)) {
            flash('errors', 'Minimal harus ada 1 produk dalam pembelian ini.');
            redirect('/purchases/create');
        }

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/purchases/create');
        }

        $subtotal = array_sum(array_column($items, 'subtotal'));
        $tax = (float) ($_POST['tax'] ?? 0);
        $discount = (float) ($_POST['discount'] ?? 0);
        $grandTotal = $subtotal + $tax - $discount;

        $purchaseData = [
            'invoice_number' => Purchase::generateInvoiceNumber(),
            'supplier_id'    => (int) $_POST['supplier_id'],
            'user_id'        => current_user()['id'],
            'purchase_date'  => $_POST['purchase_date'],
            'subtotal'       => $subtotal,
            'tax'            => $tax,
            'discount'       => $discount,
            'grand_total'    => $grandTotal,
            'status'         => 'completed',
        ];

        $purchaseId = Purchase::createWithItems($purchaseData, $items, current_user()['id']);

        AuditLog::record(current_user()['id'], 'create', 'purchases', $purchaseId, null, $purchaseData['invoice_number']);

        flash('success', 'Pembelian berhasil disimpan. Stok produk telah diperbarui.');
        redirect('/purchases/' . $purchaseId);
    }

    /** GET /purchases/{id} — detail sekaligus halaman cetak invoice */
    public function show(string $id): void
    {
        RoleMiddleware::handle('purchases.index', 'view');

        $purchase = Purchase::findWithSupplier((int) $id);
        if (!$purchase) {
            abort(404, 'Data pembelian tidak ditemukan.');
        }

        $items = Purchase::itemsWithProduct((int) $id);

        require __DIR__ . '/../views/purchases/show.php';
    }

    /** Ambil array item dari input form dinamis: item_variant_id[], item_qty[], item_price[] (index sejajar). */
    private function extractItemsFromPost(): array
    {
        $variantIds = $_POST['item_variant_id'] ?? [];
        $qtys = $_POST['item_qty'] ?? [];
        $prices = $_POST['item_price'] ?? [];

        $items = [];
        $count = count($variantIds);

        for ($i = 0; $i < $count; $i++) {
            if (empty($variantIds[$i]) || (int) ($qtys[$i] ?? 0) <= 0) {
                continue; // lewati baris kosong/tidak valid
            }

            $qty = (int) $qtys[$i];
            $price = (float) ($prices[$i] ?? 0);

            $items[] = [
                'product_variant_id' => (int) $variantIds[$i],
                'qty'                => $qty,
                'price'              => $price,
                'subtotal'           => $qty * $price,
            ];
        }

        return $items;
    }
}
