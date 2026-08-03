<?php
require_once __DIR__ . '/../models/Purchase.php';
require_once __DIR__ . '/../models/PurchaseReturn.php';
require_once __DIR__ . '/../models/AuditLog.php';

class PurchaseReturnController
{
    /** GET /returns/purchases */
    public function index(): void
    {
        RoleMiddleware::handle('returns.index', 'view');
        $returns = PurchaseReturn::allWithPurchase();
        require __DIR__ . '/../views/returns/purchases_index.php';
    }

    /** GET /returns/purchases/create?invoice=PO-xxx */
    public function create(): void
    {
        RoleMiddleware::handle('returns.index', 'create');

        $invoice = trim($_GET['invoice'] ?? '');
        $purchase = null;
        $returnableItems = [];

        if ($invoice !== '') {
            $purchase = Purchase::findByInvoiceNumber($invoice);
            if (!$purchase) {
                flash('errors', 'Invoice "' . $invoice . '" tidak ditemukan atau belum berstatus completed.');
            } else {
                $purchaseItems = Purchase::itemsWithProduct((int) $purchase['id']);
                $alreadyReturned = PurchaseReturn::alreadyReturnedQtyByPurchase((int) $purchase['id']);

                foreach ($purchaseItems as $item) {
                    $returned = $alreadyReturned[(int) $item['product_variant_id']] ?? 0;
                    $remaining = (int) $item['qty'] - $returned;
                    if ($remaining > 0) {
                        $returnableItems[] = [
                            'product_variant_id' => $item['product_variant_id'],
                            'product_name'       => $item['product_name'],
                            'size'               => $item['size'],
                            'color'              => $item['color'],
                            'unit_price'         => $item['price'],
                            'remaining_qty'       => $remaining,
                        ];
                    }
                }
            }
        }

        require __DIR__ . '/../views/returns/purchases_create.php';
    }

    /** POST /returns/purchases */
    public function store(): void
    {
        RoleMiddleware::handle('returns.index', 'create');

        $purchaseId = (int) ($_POST['purchase_id'] ?? 0);
        $purchase = Purchase::find($purchaseId);
        if (!$purchase || $purchase['status'] !== 'completed') {
            flash('errors', 'Data pembelian tidak ditemukan atau tidak bisa diretur.');
            redirect('/returns/purchases/create');
        }

        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') {
            flash('errors', 'Alasan retur wajib diisi.');
            redirect('/returns/purchases/create?invoice=' . urlencode($purchase['invoice_number']));
        }

        $items = $this->extractReturnItems($purchaseId);
        if (empty($items)) {
            flash('errors', 'Pilih minimal 1 produk dengan qty retur lebih dari 0.');
            redirect('/returns/purchases/create?invoice=' . urlencode($purchase['invoice_number']));
        }

        try {
            $returnId = PurchaseReturn::process($purchaseId, $reason, $items, current_user()['id']);
        } catch (RuntimeException $e) {
            flash('errors', $e->getMessage());
            redirect('/returns/purchases/create?invoice=' . urlencode($purchase['invoice_number']));
        }

        AuditLog::record(current_user()['id'], 'create', 'purchase_returns', $returnId, null, $purchase['invoice_number']);

        flash('success', 'Retur pembelian berhasil diproses. Stok telah dikurangi.');
        redirect('/returns/purchases/' . $returnId);
    }

    /** GET /returns/purchases/{id} */
    public function show(string $id): void
    {
        RoleMiddleware::handle('returns.index', 'view');

        $return = PurchaseReturn::findWithItems((int) $id);
        if (!$return) {
            abort(404, 'Data retur tidak ditemukan.');
        }

        require __DIR__ . '/../views/returns/purchases_show.php';
    }

    private function extractReturnItems(int $purchaseId): array
    {
        $variantIds = $_POST['return_variant_id'] ?? [];
        $qtys = $_POST['return_qty'] ?? [];
        $prices = $_POST['return_price'] ?? [];

        $alreadyReturned = PurchaseReturn::alreadyReturnedQtyByPurchase($purchaseId);
        $purchaseItems = Purchase::itemsWithProduct($purchaseId);
        $originalQtyMap = [];
        foreach ($purchaseItems as $item) {
            $originalQtyMap[(int) $item['product_variant_id']] = (int) $item['qty'];
        }

        $items = [];
        for ($i = 0; $i < count($variantIds); $i++) {
            $qty = (int) ($qtys[$i] ?? 0);
            if ($qty <= 0 || empty($variantIds[$i])) {
                continue;
            }

            $variantId = (int) $variantIds[$i];
            $originalQty = $originalQtyMap[$variantId] ?? 0;
            $returned = $alreadyReturned[$variantId] ?? 0;
            $maxReturnable = $originalQty - $returned;

            if ($qty > $maxReturnable) {
                $qty = max(0, $maxReturnable);
            }
            if ($qty <= 0) {
                continue;
            }

            $price = (float) ($prices[$i] ?? 0);

            $items[] = [
                'product_variant_id' => $variantId,
                'qty'                => $qty,
                'price'              => $price,
                'subtotal'           => $qty * $price,
            ];
        }

        return $items;
    }
}
