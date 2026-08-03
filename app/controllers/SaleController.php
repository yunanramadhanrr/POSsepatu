<?php
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/ProductVariant.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../models/Voucher.php';
require_once __DIR__ . '/../models/PaymentMethod.php';
require_once __DIR__ . '/../models/Setting.php';
require_once __DIR__ . '/../models/AuditLog.php';

class SaleController
{
    /** GET /sales — halaman utama Kasir/POS */
    public function index(): void
    {
        RoleMiddleware::handle('sales.index', 'view');

        $paymentMethods = PaymentMethod::active();
        $heldCount = count(Sale::heldSales());
        $taxPercentDefault = Setting::get('store_tax_percent', '0');

        // Jika datang dari "Recall", muat data transaksi held ke keranjang
        $recallSale = null;
        if (!empty($_GET['recall_id'])) {
            $recallSale = Sale::findFull((int) $_GET['recall_id']);
            if ($recallSale && $recallSale['status'] !== 'held') {
                $recallSale = null;
            }
        }

        require __DIR__ . '/../views/sales/index.php';
    }

    /** GET /sales/held — daftar transaksi yang di-hold */
    public function held(): void
    {
        RoleMiddleware::handle('sales.index', 'view');
        $heldSales = Sale::heldSales();
        require __DIR__ . '/../views/sales/held.php';
    }

    /** POST /sales/{id}/cancel-hold */
    public function cancelHold(string $id): void
    {
        RoleMiddleware::handle('sales.index', 'edit');

        try {
            Sale::cancelHeld((int) $id);
            AuditLog::record(current_user()['id'], 'cancel_hold', 'sales', (int) $id, null, null);
            flash('success', 'Transaksi held berhasil dibatalkan.');
        } catch (RuntimeException $e) {
            flash('errors', $e->getMessage());
        }

        redirect('/sales/held');
    }

    /** GET /sales/search-product?q=... — AJAX: cari produk by barcode/nama untuk ditambahkan ke keranjang */
    public function searchProduct(): void
    {
        RoleMiddleware::handle('sales.index', 'view');

        $query = trim($_GET['q'] ?? '');
        header('Content-Type: application/json');

        if ($query === '') {
            echo json_encode([]);
            return;
        }

        // Coba exact match barcode dulu (skenario scan barcode)
        $exact = ProductVariant::findByBarcode($query);
        if ($exact) {
            $product = Product::find((int) $exact['product_id']);
            echo json_encode([self::formatVariantForCart($exact, $product['name'] ?? '')]);
            return;
        }

        $results = ProductVariant::raw(
            "SELECT v.*, p.name AS product_name
             FROM product_variants v
             JOIN products p ON p.id = v.product_id
             WHERE p.status = 'active'
               AND (p.name LIKE :q1 OR v.barcode LIKE :q2 OR p.product_code LIKE :q3)
             ORDER BY p.name ASC
             LIMIT 15",
            [':q1' => '%' . $query . '%', ':q2' => '%' . $query . '%', ':q3' => '%' . $query . '%']
        );

        $formatted = array_map(fn($v) => self::formatVariantForCart($v, $v['product_name']), $results);
        echo json_encode($formatted);
    }

    /** GET /sales/search-customer?q=... — AJAX: cari pelanggan by nama/no HP/kode member */
    public function searchCustomer(): void
    {
        RoleMiddleware::handle('sales.index', 'view');

        $query = trim($_GET['q'] ?? '');
        header('Content-Type: application/json');

        if ($query === '') {
            echo json_encode([]);
            return;
        }

        $results = Customer::allWithLevel($query);
        $formatted = array_map(fn($c) => [
            'id'               => (int) $c['id'],
            'member_code'      => $c['member_code'],
            'name'             => $c['name'],
            'points'           => (int) $c['points'],
            'level_name'       => $c['level_name'],
            'discount_percent' => (float) ($c['discount_percent'] ?? 0),
        ], array_slice($results, 0, 10));

        echo json_encode($formatted);
    }

    /** POST /sales/validate-voucher — AJAX: cek validitas kode voucher */
    public function validateVoucher(): void
    {
        RoleMiddleware::handle('sales.index', 'view');

        header('Content-Type: application/json');
        $code = trim($_POST['code'] ?? '');
        $voucher = Voucher::findByCode($code);

        if (!$voucher) {
            echo json_encode(['valid' => false, 'message' => 'Kode voucher tidak ditemukan.']);
            return;
        }
        if ($voucher['status'] !== 'active') {
            echo json_encode(['valid' => false, 'message' => 'Voucher sudah tidak aktif/terpakai.']);
            return;
        }
        if ($voucher['expired_at'] && strtotime($voucher['expired_at']) < strtotime(date('Y-m-d'))) {
            echo json_encode(['valid' => false, 'message' => 'Voucher sudah kedaluwarsa.']);
            return;
        }

        echo json_encode([
            'valid'      => true,
            'id'         => (int) $voucher['id'],
            'code'       => $voucher['code'],
            'value'      => (float) $voucher['value'],
            'value_type' => $voucher['value_type'],
        ]);
    }

    /** POST /sales/hold */
    public function hold(): void
    {
        RoleMiddleware::handle('sales.index', 'create');

        $items = $this->extractItemsFromPost();
        if (empty($items)) {
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Keranjang bagian ini masih kosong.']);
                return;
            }
            flash('errors', 'Keranjang masih kosong.');
            redirect('/sales');
        }

        $totals = $this->computeTotals($items, $_POST);

        $saleData = [
            'invoice_number' => Sale::generateInvoiceNumber(),
            'user_id'        => current_user()['id'],
            'customer_id'    => ($_POST['customer_id'] ?? '') ?: null,
            'voucher_id'     => ($_POST['voucher_id'] ?? '') ?: null,
            'sale_date'      => date('Y-m-d H:i:s'),
            'subtotal'       => $totals['subtotal'],
            'discount_total' => $totals['discount_total'],
            'tax'            => $totals['tax'],
            'grand_total'    => $totals['grand_total'],
            'paid_amount'    => 0,
            'change_amount'  => 0,
            'note'           => trim($_POST['note'] ?? ''),
        ];

        $saleId = Sale::hold($saleData, $items);

        AuditLog::record(current_user()['id'], 'hold', 'sales', $saleId, null, $saleData['invoice_number']);

        // Jika dipanggil via AJAX (dipakai fitur Split Bill untuk hold bagian ke-2 di background), balas JSON
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'sale_id' => $saleId, 'invoice_number' => $saleData['invoice_number']]);
            return;
        }

        flash('success', 'Transaksi berhasil di-hold: ' . $saleData['invoice_number']);
        redirect('/sales');
    }

    /** POST /sales/checkout — finalisasi pembayaran (transaksi baru atau lanjutan dari recall) */
    public function checkout(): void
    {
        RoleMiddleware::handle('sales.index', 'create');

        $items = $this->extractItemsFromPost();
        if (empty($items)) {
            flash('errors', 'Keranjang masih kosong.');
            redirect('/sales');
        }

        $totals = $this->computeTotals($items, $_POST);

        $payments = $this->extractPaymentsFromPost();
        $totalPaid = array_sum(array_column($payments, 'amount'));

        if ($totalPaid < $totals['grand_total']) {
            flash('errors', 'Kekurangan pembayaran: ' . format_rupiah($totals['grand_total'] - $totalPaid));
            redirect('/sales' . (!empty($_POST['existing_sale_id']) ? '?recall_id=' . $_POST['existing_sale_id'] : ''));
        }

        $changeAmount = $totalPaid - $totals['grand_total'];
        $existingSaleId = !empty($_POST['existing_sale_id']) ? (int) $_POST['existing_sale_id'] : null;

        $saleData = [
            'invoice_number' => $existingSaleId ? Sale::find($existingSaleId)['invoice_number'] : Sale::generateInvoiceNumber(),
            'user_id'        => current_user()['id'],
            'customer_id'    => ($_POST['customer_id'] ?? '') ?: null,
            'voucher_id'     => ($_POST['voucher_id'] ?? '') ?: null,
            'sale_date'      => date('Y-m-d H:i:s'),
            'subtotal'       => $totals['subtotal'],
            'discount_total' => $totals['discount_total'],
            'tax'            => $totals['tax'],
            'grand_total'    => $totals['grand_total'],
            'paid_amount'    => $totalPaid,
            'change_amount'  => $changeAmount,
            'note'           => trim($_POST['note'] ?? ''),
        ];

        try {
            $saleId = Sale::checkout($saleData, $items, $payments, current_user()['id'], $existingSaleId);
        } catch (RuntimeException $e) {
            flash('errors', $e->getMessage());
            redirect('/sales' . ($existingSaleId ? '?recall_id=' . $existingSaleId : ''));
        }

        AuditLog::record(current_user()['id'], 'checkout', 'sales', $saleId, null, $saleData['invoice_number']);

        flash('success', 'Transaksi berhasil! Kembalian: ' . format_rupiah($changeAmount));
        redirect('/sales/' . $saleId);
    }

    /** GET /sales/{id} — detail transaksi (dasar untuk struk, disempurnakan di Tahap 8) */
    public function show(string $id): void
    {
        RoleMiddleware::handle('sales.index', 'view');

        $sale = Sale::findFull((int) $id);
        if (!$sale) {
            abort(404, 'Transaksi tidak ditemukan.');
        }

        require __DIR__ . '/../views/sales/show.php';
    }

    /** GET /sales/{id}/receipt?width=58|80 — struk thermal siap cetak (58mm/80mm) */
    public function receipt(string $id): void
    {
        RoleMiddleware::handle('sales.index', 'view');

        $sale = Sale::findFull((int) $id);
        if (!$sale) {
            abort(404, 'Transaksi tidak ditemukan.');
        }

        $width = (int) ($_GET['width'] ?? Setting::get('receipt_printer_width', '80'));
        $width = in_array($width, [58, 80], true) ? $width : 80;

        $storeName = Setting::get('store_name', APP_NAME);
        $storeAddress = Setting::get('store_address', '');
        $storeLogo = Setting::get('store_logo', '');
        $receiptUrl = url('/sales/' . $sale['id'] . '/receipt');

        require __DIR__ . '/../views/sales/receipt.php';
    }

    /**
     * Ambil array item keranjang dari input form:
     * cart_variant_id[], cart_qty[], cart_price[], cart_discount[] (index sejajar).
     */
    private function extractItemsFromPost(): array
    {
        $variantIds = $_POST['cart_variant_id'] ?? [];
        $qtys = $_POST['cart_qty'] ?? [];
        $prices = $_POST['cart_price'] ?? [];
        $discounts = $_POST['cart_discount'] ?? [];

        $canEditPrice = in_array(current_user()['role_name'], ['Owner', 'Admin'], true);

        $items = [];
        for ($i = 0; $i < count($variantIds); $i++) {
            if (empty($variantIds[$i]) || (int) ($qtys[$i] ?? 0) <= 0) {
                continue;
            }

            $variant = ProductVariant::find((int) $variantIds[$i]);
            if (!$variant) {
                continue;
            }

            $qty = (int) $qtys[$i];
            // Keamanan: Kasir TIDAK boleh mengubah harga jual; harga selalu diambil ulang dari database
            // di sisi server, mengabaikan nilai yang dikirim klien meski request POST dimanipulasi langsung.
            $price = $canEditPrice ? (float) ($prices[$i] ?? $variant['sell_price']) : (float) $variant['sell_price'];
            $discount = (float) ($discounts[$i] ?? 0);

            $items[] = [
                'product_variant_id' => (int) $variantIds[$i],
                'qty'                => $qty,
                'price'              => $price,
                'discount'           => $discount,
                'subtotal'           => ($qty * $price) - $discount,
            ];
        }

        return $items;
    }

    /** Ambil array pembayaran (mendukung multi-payment) dari input form: pay_method_id[], pay_amount[]. */
    private function extractPaymentsFromPost(): array
    {
        $methodIds = $_POST['pay_method_id'] ?? [];
        $amounts = $_POST['pay_amount'] ?? [];

        $payments = [];
        for ($i = 0; $i < count($methodIds); $i++) {
            $amount = (float) ($amounts[$i] ?? 0);
            if (empty($methodIds[$i]) || $amount <= 0) {
                continue;
            }
            $payments[] = [
                'payment_method_id' => (int) $methodIds[$i],
                'amount'            => $amount,
                'reference_number'  => trim($_POST['pay_reference'][$i] ?? ''),
            ];
        }

        return $payments;
    }

    /** Hitung subtotal, diskon total (manual + voucher + member), pajak, grand total dari keranjang. */
    private function computeTotals(array $items, array $post): array
    {
        $subtotal = array_sum(array_column($items, 'subtotal'));

        $manualDiscount = (float) ($post['discount_total'] ?? 0);

        $voucherDiscount = 0;
        if (!empty($post['voucher_id'])) {
            $voucher = Voucher::find((int) $post['voucher_id']);
            if ($voucher && $voucher['status'] === 'active') {
                $voucherDiscount = $voucher['value_type'] === 'percent'
                    ? $subtotal * ((float) $voucher['value'] / 100)
                    : (float) $voucher['value'];
            }
        }

        $memberDiscount = 0;
        if (!empty($post['customer_id'])) {
            $customer = Customer::findWithLevel((int) $post['customer_id']);
            if ($customer && !empty($customer['discount_percent'])) {
                $memberDiscount = $subtotal * ((float) $customer['discount_percent'] / 100);
            }
        }

        $discountTotal = $manualDiscount + $voucherDiscount + $memberDiscount;
        $taxableAmount = max(0, $subtotal - $discountTotal);

        $taxPercent = (float) ($post['tax_percent'] ?? 0);
        $tax = $taxableAmount * ($taxPercent / 100);

        $grandTotal = $taxableAmount + $tax;

        return [
            'subtotal'       => $subtotal,
            'discount_total' => $discountTotal,
            'tax'            => $tax,
            'grand_total'    => $grandTotal,
        ];
    }

    private static function formatVariantForCart(array $variant, string $productName): array
    {
        return [
            'variant_id'   => (int) $variant['id'],
            'product_name' => $productName,
            'size'         => $variant['size'],
            'color'        => $variant['color'],
            'barcode'      => $variant['barcode'],
            'sell_price'   => (float) $variant['sell_price'],
            'stock'        => (int) $variant['stock'],
        ];
    }

}
