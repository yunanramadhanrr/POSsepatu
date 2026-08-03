<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ProductVariant.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Brand.php';
require_once __DIR__ . '/../models/Supplier.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Validation.php';

class ProductController
{
    /** GET /products */
    public function index(): void
    {
        RoleMiddleware::handle('products.index', 'view');

        $search = trim($_GET['search'] ?? '');
        $products = Product::allWithRelations($search);

        require __DIR__ . '/../views/products/index.php';
    }

    /** GET /products/create */
    public function create(): void
    {
        RoleMiddleware::handle('products.index', 'create');

        $categories = Category::all('name ASC');
        $brands = Brand::all('name ASC');
        $suppliers = Supplier::all('name ASC');
        $generatedCode = Product::generateUniqueCode();

        require __DIR__ . '/../views/products/create.php';
    }

    /** POST /products */
    public function store(): void
    {
        RoleMiddleware::handle('products.index', 'create');

        $validator = new Validation($_POST);
        $validator->required('name', 'Nama sepatu wajib diisi')
                  ->required('product_code', 'Kode produk wajib diisi');

        $variantsInput = $this->extractVariantsFromPost();
        if (empty($variantsInput)) {
            flash('errors', 'Minimal harus ada 1 varian (ukuran/warna) untuk produk ini.');
            redirect('/products/create');
        }

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/products/create');
        }

        // Cegah barcode duplikat antar varian yang diinput sekaligus, dan dengan varian produk lain
        $barcodeError = $this->validateBarcodesUnique($variantsInput);
        if ($barcodeError) {
            flash('errors', $barcodeError);
            redirect('/products/create');
        }

        try {
            $photo = handle_photo_upload('photo', rtrim(UPLOAD_PATH, '/'));
        } catch (RuntimeException $e) {
            flash('errors', $e->getMessage());
            redirect('/products/create');
        }

        $productData = [
            'product_code' => trim($_POST['product_code']),
            'name'         => trim($_POST['name']),
            'category_id'  => ($_POST['category_id'] ?? '') ?: null,
            'brand_id'     => ($_POST['brand_id'] ?? '') ?: null,
            'supplier_id'  => ($_POST['supplier_id'] ?? '') ?: null,
            'gender'       => $_POST['gender'] ?? 'Unisex',
            'description'  => trim($_POST['description'] ?? ''),
            'photo'        => $photo,
            'status'       => $_POST['status'] ?? 'active',
        ];

        $productId = Product::createWithVariants($productData, $variantsInput);

        AuditLog::record(current_user()['id'], 'create', 'products', $productId, null, $productData['name']);

        flash('success', 'Produk berhasil ditambahkan beserta ' . count($variantsInput) . ' varian.');
        redirect('/products');
    }

    /** GET /products/{id}/edit */
    public function edit(string $id): void
    {
        RoleMiddleware::handle('products.index', 'edit');

        $product = Product::findWithRelations((int) $id);
        if (!$product) {
            abort(404, 'Produk tidak ditemukan.');
        }

        $variants = ProductVariant::forProduct((int) $id);
        $categories = Category::all('name ASC');
        $brands = Brand::all('name ASC');
        $suppliers = Supplier::all('name ASC');

        require __DIR__ . '/../views/products/edit.php';
    }

    /** POST /products/{id}/update */
    public function update(string $id): void
    {
        RoleMiddleware::handle('products.index', 'edit');

        $product = Product::find((int) $id);
        if (!$product) {
            abort(404, 'Produk tidak ditemukan.');
        }

        $validator = new Validation($_POST);
        $validator->required('name', 'Nama sepatu wajib diisi');

        $variantsInput = $this->extractVariantsFromPost();
        if (empty($variantsInput)) {
            flash('errors', 'Minimal harus ada 1 varian (ukuran/warna) untuk produk ini.');
            redirect('/products/' . $id . '/edit');
        }

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/products/' . $id . '/edit');
        }

        $barcodeError = $this->validateBarcodesUnique($variantsInput, (int) $id);
        if ($barcodeError) {
            flash('errors', $barcodeError);
            redirect('/products/' . $id . '/edit');
        }

        try {
            $newPhoto = handle_photo_upload('photo', rtrim(UPLOAD_PATH, '/'));
        } catch (RuntimeException $e) {
            flash('errors', $e->getMessage());
            redirect('/products/' . $id . '/edit');
        }

        $photo = $product['photo'];
        if ($newPhoto) {
            delete_photo_if_exists($product['photo'], rtrim(UPLOAD_PATH, '/'));
            $photo = $newPhoto;
        }

        $productData = [
            'name'        => trim($_POST['name']),
            'category_id' => ($_POST['category_id'] ?? '') ?: null,
            'brand_id'    => ($_POST['brand_id'] ?? '') ?: null,
            'supplier_id' => ($_POST['supplier_id'] ?? '') ?: null,
            'gender'      => $_POST['gender'] ?? 'Unisex',
            'description' => trim($_POST['description'] ?? ''),
            'photo'       => $photo,
            'status'      => $_POST['status'] ?? 'active',
        ];

        try {
            $skippedVariants = Product::updateWithVariants((int) $id, $productData, $variantsInput);
        } catch (Throwable $e) {
            flash('errors', 'Gagal menyimpan perubahan produk: ' . $e->getMessage());
            redirect('/products/' . $id . '/edit');
        }

        AuditLog::record(current_user()['id'], 'update', 'products', (int) $id, $product['name'], $productData['name']);

        if (!empty($skippedVariants)) {
            flash('success', 'Produk berhasil diperbarui. Catatan: varian ' . implode(', ', $skippedVariants) .
                ' tidak dihapus karena sudah pernah dipakai dalam transaksi (riwayat data harus tetap utuh).');
        } else {
            flash('success', 'Produk berhasil diperbarui.');
        }
        redirect('/products');
    }

    /** POST /products/{id}/delete */
    public function destroy(string $id): void
    {
        RoleMiddleware::handle('products.index', 'delete');

        $product = Product::find((int) $id);
        if (!$product) {
            abort(404, 'Produk tidak ditemukan.');
        }

        // Cegah crash: jika salah satu variannya sudah pernah dipakai dalam transaksi apa pun,
        // produk tidak bisa dihapus permanen (akan menabrak foreign key). Arahkan user untuk
        // menonaktifkan produk saja lewat Edit, supaya riwayat transaksi lama tetap utuh.
        foreach (ProductVariant::forProduct((int) $id) as $variant) {
            if (ProductVariant::hasReferences((int) $variant['id'])) {
                flash('errors', 'Produk ini tidak bisa dihapus karena salah satu variannya sudah pernah ' .
                    'dipakai dalam transaksi (penjualan/pembelian/stok). Gunakan status "Tidak Aktif" lewat menu Edit sebagai gantinya.');
                redirect('/products');
            }
        }

        // product_variants punya ON DELETE CASCADE, jadi otomatis ikut terhapus.
        // Foto fisik tetap harus dibersihkan manual agar tidak jadi file "sampah".
        delete_photo_if_exists($product['photo'], rtrim(UPLOAD_PATH, '/'));
        Product::delete((int) $id);

        AuditLog::record(current_user()['id'], 'delete', 'products', (int) $id, $product['name'], null);

        flash('success', 'Produk berhasil dihapus.');
        redirect('/products');
    }

    /**
     * Ambil array varian dari input form dinamis:
     * variant_id[] (kosong = varian baru), variant_size[], variant_color[], dst (index sejajar).
     */
    private function extractVariantsFromPost(): array
    {
        $ids        = $_POST['variant_id'] ?? [];
        $sizes      = $_POST['variant_size'] ?? [];
        $colors     = $_POST['variant_color'] ?? [];
        $barcodes   = $_POST['variant_barcode'] ?? [];
        $costPrices = $_POST['variant_cost_price'] ?? [];
        $sellPrices = $_POST['variant_sell_price'] ?? [];
        $discounts  = $_POST['variant_discount'] ?? [];
        $taxes      = $_POST['variant_tax_percent'] ?? [];
        $stocks     = $_POST['variant_stock'] ?? [];
        $minStocks  = $_POST['variant_min_stock'] ?? [];

        $variants = [];
        $count = count($sizes);

        for ($i = 0; $i < $count; $i++) {
            // Lewati baris yang sepenuhnya kosong (misalnya baris template yang tidak dihapus dari form)
            if (trim($sizes[$i] ?? '') === '' && trim($colors[$i] ?? '') === '') {
                continue;
            }

            $variants[] = [
                'id'          => !empty($ids[$i]) ? (int) $ids[$i] : null,
                'size'        => trim($sizes[$i] ?? ''),
                'color'       => trim($colors[$i] ?? ''),
                'barcode'     => trim($barcodes[$i] ?? '') ?: null,
                'cost_price'  => (float) ($costPrices[$i] ?? 0),
                'sell_price'  => (float) ($sellPrices[$i] ?? 0),
                'discount'    => (float) ($discounts[$i] ?? 0),
                'tax_percent' => (float) ($taxes[$i] ?? 0),
                'stock'       => (int) ($stocks[$i] ?? 0),
                'min_stock'   => (int) ($minStocks[$i] ?? 5),
            ];
        }

        return $variants;
    }

    /** Pastikan barcode unik antar baris yang diinput & terhadap varian produk lain di database. */
    private function validateBarcodesUnique(array $variants, ?int $excludeProductId = null): ?string
    {
        $seen = [];
        foreach ($variants as $variant) {
            if (!$variant['barcode']) {
                continue; // barcode opsional
            }

            if (isset($seen[$variant['barcode']])) {
                return 'Barcode "' . e($variant['barcode']) . '" dipakai lebih dari sekali di form ini.';
            }
            $seen[$variant['barcode']] = true;

            $existing = ProductVariant::findByBarcode($variant['barcode']);
            if ($existing && (int) $existing['product_id'] !== $excludeProductId) {
                return 'Barcode "' . e($variant['barcode']) . '" sudah dipakai produk lain.';
            }
        }

        return null;
    }
}
