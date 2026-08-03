-- ============================================================
-- POS TOKO SEPATU - FULL DATABASE MIGRATION
-- Engine: MySQL 8 / MariaDB 10.4+
-- Charset: utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS pos_toko_sepatu
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE pos_toko_sepatu;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. ROLES & USERS & PERMISSIONS
-- ============================================================

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) DEFAULT NULL,
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_token_expires_at DATETIME DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    last_login_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    INDEX idx_users_email (email)
) ENGINE=InnoDB;

-- Menu yang bisa diberi izin per role (dinamis, tidak hardcode di controller)
CREATE TABLE menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT DEFAULT NULL,
    label VARCHAR(100) NOT NULL,
    route_key VARCHAR(100) NOT NULL UNIQUE, -- ex: 'products.index'
    icon VARCHAR(50) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (parent_id) REFERENCES menus(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    menu_id INT NOT NULL,
    can_view TINYINT(1) NOT NULL DEFAULT 0,
    can_create TINYINT(1) NOT NULL DEFAULT 0,
    can_edit TINYINT(1) NOT NULL DEFAULT 0,
    can_delete TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_role_menu (role_id, menu_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 2. MASTER DATA: KATEGORI, BRAND, SUPPLIER, PRODUK
-- ============================================================

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(150),
    pic VARCHAR(100),
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category_id INT DEFAULT NULL,
    brand_id INT DEFAULT NULL,
    supplier_id INT DEFAULT NULL,
    gender ENUM('Pria','Wanita','Unisex','Anak') DEFAULT 'Unisex',
    description TEXT,
    photo VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    INDEX idx_products_code (product_code),
    FULLTEXT INDEX ft_products_name (name)
) ENGINE=InnoDB;

CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    barcode VARCHAR(50) DEFAULT NULL UNIQUE,
    size VARCHAR(10) DEFAULT NULL,
    color VARCHAR(50) DEFAULT NULL,
    cost_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    sell_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    stock INT NOT NULL DEFAULT 0,
    min_stock INT NOT NULL DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_variants_barcode (barcode),
    INDEX idx_variants_stock (stock)
) ENGINE=InnoDB;

-- ============================================================
-- 3. MEMBERSHIP & PELANGGAN
-- ============================================================

CREATE TABLE membership_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    min_points INT NOT NULL DEFAULT 0,
    discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_code VARCHAR(30) UNIQUE,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(150),
    address TEXT,
    birth_date DATE DEFAULT NULL,
    membership_level_id INT DEFAULT NULL,
    points INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (membership_level_id) REFERENCES membership_levels(id) ON DELETE SET NULL,
    INDEX idx_customers_phone (phone)
) ENGINE=InnoDB;

CREATE TABLE point_histories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    sale_id INT DEFAULT NULL,
    points_change INT NOT NULL,
    note VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 4. PEMBELIAN (dari Supplier)
-- ============================================================

CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(30) NOT NULL UNIQUE,
    supplier_id INT NOT NULL,
    user_id INT NOT NULL,
    purchase_date DATE NOT NULL,
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
    tax DECIMAL(14,2) NOT NULL DEFAULT 0,
    discount DECIMAL(14,2) NOT NULL DEFAULT 0,
    grand_total DECIMAL(14,2) NOT NULL DEFAULT 0,
    status ENUM('completed','void') NOT NULL DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    product_variant_id INT NOT NULL,
    qty INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(14,2) NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB;

CREATE TABLE purchase_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    return_number VARCHAR(30) NOT NULL UNIQUE,
    return_date DATE NOT NULL,
    reason VARCHAR(255),
    total DECIMAL(14,2) NOT NULL DEFAULT 0,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE purchase_return_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_return_id INT NOT NULL,
    product_variant_id INT NOT NULL,
    qty INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(14,2) NOT NULL,
    FOREIGN KEY (purchase_return_id) REFERENCES purchase_returns(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB;

-- ============================================================
-- 5. PENJUALAN (Kasir/POS)
-- ============================================================

CREATE TABLE payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    type VARCHAR(30) NOT NULL, -- cash, card, ewallet, transfer, voucher, credit
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    value DECIMAL(12,2) NOT NULL,
    value_type ENUM('nominal','percent') NOT NULL DEFAULT 'nominal',
    expired_at DATE DEFAULT NULL,
    status ENUM('active','used','expired') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(30) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    customer_id INT DEFAULT NULL,
    voucher_id INT DEFAULT NULL,
    sale_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
    discount_total DECIMAL(14,2) NOT NULL DEFAULT 0,
    tax DECIMAL(14,2) NOT NULL DEFAULT 0,
    grand_total DECIMAL(14,2) NOT NULL DEFAULT 0,
    paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    change_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    note VARCHAR(255),
    status ENUM('completed','held','void') NOT NULL DEFAULT 'completed',
    held_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE SET NULL,
    INDEX idx_sales_date (sale_date),
    INDEX idx_sales_status (status)
) ENGINE=InnoDB;

CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_variant_id INT NOT NULL,
    qty INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    discount DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(14,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB;

CREATE TABLE sale_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    payment_method_id INT NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    reference_number VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
) ENGINE=InnoDB;

CREATE TABLE sale_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    return_number VARCHAR(30) NOT NULL UNIQUE,
    return_date DATE NOT NULL,
    reason VARCHAR(255),
    total DECIMAL(14,2) NOT NULL DEFAULT 0,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE sale_return_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_return_id INT NOT NULL,
    product_variant_id INT NOT NULL,
    qty INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(14,2) NOT NULL,
    FOREIGN KEY (sale_return_id) REFERENCES sale_returns(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB;

-- ============================================================
-- 6. STOK
-- ============================================================

CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_variant_id INT NOT NULL,
    type ENUM('in','out','adjustment','opname') NOT NULL,
    qty INT NOT NULL,
    reference_type VARCHAR(30) DEFAULT NULL, -- purchase, sale, sale_return, purchase_return, manual, opname
    reference_id INT DEFAULT NULL,
    note VARCHAR(255),
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_stock_ref (reference_type, reference_id)
) ENGINE=InnoDB;

CREATE TABLE stock_opnames (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opname_number VARCHAR(30) NOT NULL UNIQUE,
    opname_date DATE NOT NULL,
    user_id INT NOT NULL,
    note VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE stock_opname_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_opname_id INT NOT NULL,
    product_variant_id INT NOT NULL,
    system_qty INT NOT NULL,
    physical_qty INT NOT NULL,
    difference INT NOT NULL,
    FOREIGN KEY (stock_opname_id) REFERENCES stock_opnames(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id)
) ENGINE=InnoDB;

-- ============================================================
-- 7. PENGELUARAN
-- ============================================================

CREATE TABLE expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_category_id INT NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    expense_date DATE NOT NULL,
    note VARCHAR(255),
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expense_category_id) REFERENCES expense_categories(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- 8. PENGATURAN, NOTIFIKASI, AUDIT LOG
-- ============================================================

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL, -- low_stock, out_of_stock, birthday, backup_reminder
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_read (is_read)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(50) NOT NULL, -- create, update, delete, login, logout
    table_name VARCHAR(50) DEFAULT NULL,
    record_id INT DEFAULT NULL,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
