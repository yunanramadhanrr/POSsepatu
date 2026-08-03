<?php
require_once __DIR__ . '/../models/StockMovement.php';
require_once __DIR__ . '/../models/StockOpname.php';
require_once __DIR__ . '/../models/ProductVariant.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Validation.php';

class StockController
{
    /** GET /stock — riwayat pergerakan stok terpusat dengan filter */
    public function index(): void
    {
        RoleMiddleware::handle('stock.index', 'view');

        $filters = [
            'search'    => trim($_GET['search'] ?? ''),
            'type'      => $_GET['type'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to'   => $_GET['date_to'] ?? '',
        ];

        $movements = StockMovement::history($filters, 200);
        $variants = ProductVariant::allWithProductName();

        require __DIR__ . '/../views/stock/index.php';
    }

    /** GET /stock/in */
    public function showIn(): void
    {
        RoleMiddleware::handle('stock.index', 'create');
        $variants = ProductVariant::allWithProductName();
        require __DIR__ . '/../views/stock/in.php';
    }

    /** POST /stock/in */
    public function storeIn(): void
    {
        RoleMiddleware::handle('stock.index', 'create');

        $variantId = (int) ($_POST['product_variant_id'] ?? 0);
        $qty = (int) ($_POST['qty'] ?? 0);
        $note = trim($_POST['note'] ?? 'Stok masuk manual');

        if ($variantId <= 0 || $qty <= 0) {
            flash('errors', 'Pilih produk dan isi qty lebih dari 0.');
            redirect('/stock/in');
        }

        ProductVariant::adjustStock($variantId, $qty, 'in', 'manual', null, $note, current_user()['id']);
        AuditLog::record(current_user()['id'], 'stock_in', 'product_variants', $variantId, null, "+$qty ($note)");

        flash('success', 'Stok masuk berhasil dicatat.');
        redirect('/stock');
    }

    /** GET /stock/out */
    public function showOut(): void
    {
        RoleMiddleware::handle('stock.index', 'create');
        $variants = ProductVariant::allWithProductName();
        require __DIR__ . '/../views/stock/out.php';
    }

    /** POST /stock/out */
    public function storeOut(): void
    {
        RoleMiddleware::handle('stock.index', 'create');

        $variantId = (int) ($_POST['product_variant_id'] ?? 0);
        $qty = (int) ($_POST['qty'] ?? 0);
        $note = trim($_POST['note'] ?? 'Stok keluar manual');

        if ($variantId <= 0 || $qty <= 0) {
            flash('errors', 'Pilih produk dan isi qty lebih dari 0.');
            redirect('/stock/out');
        }

        $variant = ProductVariant::find($variantId);
        if (!$variant || (int) $variant['stock'] < $qty) {
            flash('errors', 'Stok tidak mencukupi untuk dikeluarkan (sisa: ' . ($variant['stock'] ?? 0) . ').');
            redirect('/stock/out');
        }

        ProductVariant::adjustStock($variantId, -$qty, 'out', 'manual', null, $note, current_user()['id']);
        AuditLog::record(current_user()['id'], 'stock_out', 'product_variants', $variantId, null, "-$qty ($note)");

        flash('success', 'Stok keluar berhasil dicatat.');
        redirect('/stock');
    }

    /** GET /stock/mutation */
    public function showMutation(): void
    {
        RoleMiddleware::handle('stock.index', 'create');
        $variants = ProductVariant::allWithProductName();
        require __DIR__ . '/../views/stock/mutation.php';
    }

    /** POST /stock/mutation — pindahkan stok dari satu varian ke varian lain (mis. koreksi salah input) */
    public function storeMutation(): void
    {
        RoleMiddleware::handle('stock.index', 'create');

        $fromId = (int) ($_POST['from_variant_id'] ?? 0);
        $toId = (int) ($_POST['to_variant_id'] ?? 0);
        $qty = (int) ($_POST['qty'] ?? 0);
        $note = trim($_POST['note'] ?? 'Mutasi stok');

        if ($fromId <= 0 || $toId <= 0 || $qty <= 0) {
            flash('errors', 'Pilih varian asal, varian tujuan, dan qty lebih dari 0.');
            redirect('/stock/mutation');
        }
        if ($fromId === $toId) {
            flash('errors', 'Varian asal dan tujuan tidak boleh sama.');
            redirect('/stock/mutation');
        }

        $fromVariant = ProductVariant::find($fromId);
        if (!$fromVariant || (int) $fromVariant['stock'] < $qty) {
            flash('errors', 'Stok varian asal tidak mencukupi (sisa: ' . ($fromVariant['stock'] ?? 0) . ').');
            redirect('/stock/mutation');
        }

        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            ProductVariant::adjustStock($fromId, -$qty, 'out', 'mutation', null, 'Mutasi keluar: ' . $note, current_user()['id']);
            ProductVariant::adjustStock($toId, $qty, 'in', 'mutation', null, 'Mutasi masuk: ' . $note, current_user()['id']);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            flash('errors', 'Gagal memproses mutasi stok.');
            redirect('/stock/mutation');
        }

        AuditLog::record(current_user()['id'], 'stock_mutation', 'product_variants', $fromId, null, "mutasi $qty ke variant #$toId");

        flash('success', 'Mutasi stok berhasil diproses.');
        redirect('/stock');
    }

    /** GET /stock/adjustment */
    public function showAdjustment(): void
    {
        RoleMiddleware::handle('stock.index', 'create');
        $variants = ProductVariant::allWithProductName();
        require __DIR__ . '/../views/stock/adjustment.php';
    }

    /** POST /stock/adjustment — set stok ke nilai baru tertentu (koreksi langsung dengan alasan) */
    public function storeAdjustment(): void
    {
        RoleMiddleware::handle('stock.index', 'create');

        $variantId = (int) ($_POST['product_variant_id'] ?? 0);
        $newStock = (int) ($_POST['new_stock'] ?? -1);
        $reason = trim($_POST['reason'] ?? '');

        $variant = ProductVariant::find($variantId);
        if (!$variant) {
            flash('errors', 'Produk tidak ditemukan.');
            redirect('/stock/adjustment');
        }
        if ($newStock < 0) {
            flash('errors', 'Stok baru tidak boleh negatif.');
            redirect('/stock/adjustment');
        }
        if ($reason === '') {
            flash('errors', 'Alasan penyesuaian wajib diisi.');
            redirect('/stock/adjustment');
        }

        $difference = $newStock - (int) $variant['stock'];
        if ($difference !== 0) {
            ProductVariant::adjustStock($variantId, $difference, 'adjustment', 'manual', null, $reason, current_user()['id']);
        }

        AuditLog::record(current_user()['id'], 'stock_adjustment', 'product_variants', $variantId, (string) $variant['stock'], (string) $newStock);

        flash('success', 'Penyesuaian stok berhasil disimpan.');
        redirect('/stock');
    }

    /** GET /stock/opname — daftar sesi stock opname */
    public function opnameIndex(): void
    {
        RoleMiddleware::handle('stock.index', 'view');
        $opnames = StockOpname::allWithUser();
        require __DIR__ . '/../views/stock/opname_index.php';
    }

    /** GET /stock/opname/create */
    public function opnameCreate(): void
    {
        RoleMiddleware::handle('stock.index', 'create');
        $variants = ProductVariant::allWithProductName();
        require __DIR__ . '/../views/stock/opname_create.php';
    }

    /** POST /stock/opname */
    public function opnameStore(): void
    {
        RoleMiddleware::handle('stock.index', 'create');

        $variantIds = $_POST['variant_id'] ?? [];
        $physicalQtys = $_POST['physical_qty'] ?? [];
        $note = trim($_POST['note'] ?? '');

        $counts = [];
        for ($i = 0; $i < count($variantIds); $i++) {
            if ($variantIds[$i] === '' || $physicalQtys[$i] === '') {
                continue; // baris yang tidak diisi qty fisik dilewati (belum dihitung)
            }
            $counts[] = [
                'product_variant_id' => (int) $variantIds[$i],
                'physical_qty'       => (int) $physicalQtys[$i],
            ];
        }

        if (empty($counts)) {
            flash('errors', 'Isi minimal 1 hasil hitung fisik.');
            redirect('/stock/opname/create');
        }

        $opnameId = StockOpname::process($note, $counts, current_user()['id']);

        AuditLog::record(current_user()['id'], 'create', 'stock_opnames', $opnameId, null, 'Stock opname ' . count($counts) . ' item');

        flash('success', 'Stock opname berhasil diproses. Stok sistem telah disesuaikan.');
        redirect('/stock/opname/' . $opnameId);
    }

    /** GET /stock/opname/{id} */
    public function opnameShow(string $id): void
    {
        RoleMiddleware::handle('stock.index', 'view');

        $opname = StockOpname::findWithItems((int) $id);
        if (!$opname) {
            abort(404, 'Data stock opname tidak ditemukan.');
        }

        require __DIR__ . '/../views/stock/opname_show.php';
    }
}
