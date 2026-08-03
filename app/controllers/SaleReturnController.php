<?php
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/SaleReturn.php';
require_once __DIR__ . '/../models/AuditLog.php';

class SaleReturnController
{
    /** GET /returns/sales */
    public function index(): void
    {
        RoleMiddleware::handle('returns.index', 'view');
        $returns = SaleReturn::allWithSale();
        require __DIR__ . '/../views/returns/sales_index.php';
    }

    /** GET /returns/sales/create?invoice=INV-xxx */
    public function create(): void
    {
        RoleMiddleware::handle('returns.index', 'create');

        $invoice = trim($_GET['invoice'] ?? '');
        $sale = null;
        $returnableItems = [];

        if ($invoice !== '') {
            $sale = Sale::findByInvoiceNumber($invoice);
            if (!$sale) {
                flash('errors', 'Invoice "' . $invoice . '" tidak ditemukan atau belum berstatus completed.');
            } else {
                $full = Sale::findFull((int) $sale['id']);
                $alreadyReturned = SaleReturn::alreadyReturnedQtyBySale((int) $sale['id']);

                foreach ($full['items'] as $item) {
                    $returned = $alreadyReturned[(int) $item['product_variant_id']] ?? 0;
                    $remaining = (int) $item['qty'] - $returned;
                    if ($remaining > 0) {
                        $unitPrice = $item['qty'] > 0 ? $item['subtotal'] / $item['qty'] : 0;
                        $returnableItems[] = [
                            'product_variant_id' => $item['product_variant_id'],
                            'product_name'       => $item['product_name'],
                            'size'               => $item['size'],
                            'color'              => $item['color'],
                            'unit_price'         => $unitPrice,
                            'remaining_qty'       => $remaining,
                        ];
                    }
                }
            }
        }

        require __DIR__ . '/../views/returns/sales_create.php';
    }

    /** POST /returns/sales */
    public function store(): void
    {
        RoleMiddleware::handle('returns.index', 'create');

        $saleId = (int) ($_POST['sale_id'] ?? 0);
        $sale = Sale::find($saleId);
        if (!$sale || $sale['status'] !== 'completed') {
            flash('errors', 'Transaksi tidak ditemukan atau tidak bisa diretur.');
            redirect('/returns/sales/create');
        }

        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') {
            flash('errors', 'Alasan retur wajib diisi.');
            redirect('/returns/sales/create?invoice=' . urlencode($sale['invoice_number']));
        }

        $items = $this->extractReturnItems($saleId);
        if (empty($items)) {
            flash('errors', 'Pilih minimal 1 produk dengan qty retur lebih dari 0.');
            redirect('/returns/sales/create?invoice=' . urlencode($sale['invoice_number']));
        }

        try {
            $returnId = SaleReturn::process($saleId, $reason, $items, current_user()['id'], $sale['customer_id']);
        } catch (RuntimeException $e) {
            flash('errors', $e->getMessage());
            redirect('/returns/sales/create?invoice=' . urlencode($sale['invoice_number']));
        }

        AuditLog::record(current_user()['id'], 'create', 'sale_returns', $returnId, null, $sale['invoice_number']);

        flash('success', 'Retur penjualan berhasil diproses. Stok telah dikembalikan.');
        redirect('/returns/sales/' . $returnId);
    }

    /** GET /returns/sales/{id} */
    public function show(string $id): void
    {
        RoleMiddleware::handle('returns.index', 'view');

        $return = SaleReturn::findWithItems((int) $id);
        if (!$return) {
            abort(404, 'Data retur tidak ditemukan.');
        }

        require __DIR__ . '/../views/returns/sales_show.php';
    }

    /** Ambil item retur dari form: return_variant_id[], return_qty[], return_price[] (index sejajar). */
    private function extractReturnItems(int $saleId): array
    {
        $variantIds = $_POST['return_variant_id'] ?? [];
        $qtys = $_POST['return_qty'] ?? [];
        $prices = $_POST['return_price'] ?? [];

        $alreadyReturned = SaleReturn::alreadyReturnedQtyBySale($saleId);
        $fullSale = Sale::findFull($saleId);
        $originalQtyMap = [];
        foreach ($fullSale['items'] as $item) {
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
                $qty = max(0, $maxReturnable); // batasi otomatis, cegah retur melebihi sisa
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
