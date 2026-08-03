USE pos_toko_sepatu;

-- ============================================================
-- ROLES
-- ============================================================
INSERT INTO roles (name) VALUES ('Owner'), ('Admin'), ('Kasir'), ('Gudang');

-- ============================================================
-- MENU (dipakai untuk sidebar + role_permissions)
-- ============================================================
INSERT INTO menus (label, route_key, icon, sort_order) VALUES
('Dashboard',        'dashboard.index',  'bi-speedometer2', 1),
('Produk',           'products.index',   'bi-box-seam',     2),
('Kategori',         'categories.index', 'bi-tags',         3),
('Brand',            'brands.index',     'bi-award',        4),
('Supplier',         'suppliers.index',  'bi-truck',        5),
('Pelanggan',        'customers.index',  'bi-people',       6),
('Pembelian',        'purchases.index',  'bi-cart-plus',    7),
('Penjualan (Kasir)','sales.index',      'bi-cash-register',8),
('Retur',            'returns.index',    'bi-arrow-return-left', 9),
('Stok',             'stock.index',      'bi-boxes',        10),
('Laporan',          'reports.index',    'bi-graph-up',     11),
('Pengeluaran',      'expenses.index',   'bi-wallet2',      12),
('Pengaturan',       'settings.index',   'bi-gear',         13),
('Audit Log',        'audit_logs.index', 'bi-clock-history',14),
('Manajemen User',   'users.index',       'bi-person-gear',  16);

-- ============================================================
-- ROLE PERMISSIONS
-- Owner: akses penuh semua menu (juga sudah di-bypass di kode via user_can(), ini sebagai cadangan data)
-- Admin: semua kecuali Audit Log & Pengaturan backup/restore penuh (dibatasi lebih detail di UI Tahap 2+)
-- Kasir: hanya Dashboard, Penjualan, Retur (view saja untuk retur)
-- Gudang: Dashboard, Produk, Supplier, Pembelian, Stok
-- ============================================================

INSERT INTO role_permissions (role_id, menu_id, can_view, can_create, can_edit, can_delete)
SELECT r.id, m.id, 1, 1, 1, 1
FROM roles r CROSS JOIN menus m
WHERE r.name = 'Owner';

INSERT INTO role_permissions (role_id, menu_id, can_view, can_create, can_edit, can_delete)
SELECT r.id, m.id, 1, 1, 1, 0
FROM roles r CROSS JOIN menus m
WHERE r.name = 'Admin' AND m.route_key NOT IN ('audit_logs.index');

INSERT INTO role_permissions (role_id, menu_id, can_view, can_create, can_edit, can_delete)
SELECT r.id, m.id, 1, 1, 0, 0
FROM roles r CROSS JOIN menus m
WHERE r.name = 'Kasir' AND m.route_key IN ('dashboard.index', 'sales.index', 'returns.index');

INSERT INTO role_permissions (role_id, menu_id, can_view, can_create, can_edit, can_delete)
SELECT r.id, m.id, 1, 1, 1, 0
FROM roles r CROSS JOIN menus m
WHERE r.name = 'Gudang' AND m.route_key IN ('dashboard.index', 'products.index', 'suppliers.index', 'purchases.index', 'stock.index');

-- ============================================================
-- USER OWNER DEFAULT
-- CATATAN: user Owner TIDAK dibuat lewat SQL statis di sini, karena hash
-- password harus dihasilkan oleh fungsi password_hash() PHP saat itu juga
-- agar dijamin valid. Jalankan database/seeders/create_admin.php sekali
-- setelah migrasi ini untuk membuat akun Owner pertama. Lihat README.
-- ============================================================

-- ============================================================
-- METODE PEMBAYARAN
-- ============================================================
INSERT INTO payment_methods (name, type) VALUES
('Cash', 'cash'),
('Debit Card', 'card'),
('Credit Card', 'card'),
('QRIS', 'ewallet'),
('Transfer Bank', 'transfer'),
('OVO', 'ewallet'),
('GoPay', 'ewallet'),
('DANA', 'ewallet'),
('ShopeePay', 'ewallet'),
('LinkAja', 'ewallet'),
('Gift Voucher', 'voucher'),
('Store Credit', 'credit');

-- ============================================================
-- MEMBERSHIP LEVELS
-- ============================================================
INSERT INTO membership_levels (name, min_points, discount_percent) VALUES
('Silver', 0, 0),
('Gold', 500, 2.5),
('Platinum', 2000, 5),
('Diamond', 5000, 10);

-- ============================================================
-- KATEGORI & BRAND CONTOH
-- ============================================================
INSERT INTO categories (name) VALUES
('Sneakers'), ('Running'), ('Casual'), ('Boots'), ('Sandal'), ('Formal');

INSERT INTO brands (name) VALUES
('Nike'), ('Adidas'), ('Puma'), ('Compass'), ('Converse'), ('New Balance'), ('Vans'), ('Asics');

-- ============================================================
-- KATEGORI PENGELUARAN DEFAULT
-- ============================================================
INSERT INTO expense_categories (name) VALUES
('Listrik'), ('Air'), ('Internet'), ('Gaji'), ('Transport'), ('Lain-lain');

-- ============================================================
-- PENGATURAN DEFAULT
-- ============================================================
INSERT INTO settings (setting_key, setting_value) VALUES
('store_name', 'Toko Sepatu Jaya'),
('store_address', 'Jl. Contoh No. 123, Depok'),
('store_tax_percent', '11'),
('receipt_printer_width', '80'),
('default_discount_percent', '0');
