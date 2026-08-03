<?php
/**
 * Tabel routing aplikasi.
 * Format: '<METHOD> <path>' => [Controller::class, 'method', middleware options]
 *
 * middleware options (array, opsional):
 *   'auth' => true                                -> wajib login (AuthMiddleware)
 *   'permission' => ['route_key', 'ability']       -> wajib izin RBAC tertentu (RoleMiddleware)
 *
 * CsrfMiddleware otomatis dijalankan untuk semua request POST/PUT/DELETE (lihat public/index.php).
 */

return [
    // ---------- Guest routes (belum login) ----------
    'GET /login'  => ['AuthController', 'showLogin'],
    'POST /login' => ['AuthController', 'login'],
    'GET /forgot-password'  => ['AuthController', 'showForgotPassword'],
    'POST /forgot-password' => ['AuthController', 'sendResetLink'],
    'GET /reset-password'   => ['AuthController', 'showResetPassword'],
    'POST /reset-password'  => ['AuthController', 'resetPassword'],

    // ---------- Authenticated routes ----------
    'POST /logout' => ['AuthController', 'logout', ['auth' => true]],

    'GET /change-password'  => ['AuthController', 'showChangePassword', ['auth' => true]],
    'POST /change-password' => ['AuthController', 'changePassword', ['auth' => true]],

    'GET /dashboard' => ['DashboardController', 'index', ['auth' => true]],

    // ---------- Kategori ----------
    'GET /categories'                  => ['CategoryController', 'index',   ['auth' => true]],
    'POST /categories'                 => ['CategoryController', 'store',   ['auth' => true]],
    'POST /categories/{id}/update'     => ['CategoryController', 'update',  ['auth' => true]],
    'POST /categories/{id}/delete'     => ['CategoryController', 'destroy', ['auth' => true]],

    // ---------- Brand ----------
    'GET /brands'               => ['BrandController', 'index',   ['auth' => true]],
    'POST /brands'              => ['BrandController', 'store',   ['auth' => true]],
    'POST /brands/{id}/update'  => ['BrandController', 'update',  ['auth' => true]],
    'POST /brands/{id}/delete'  => ['BrandController', 'destroy', ['auth' => true]],

    // ---------- Supplier ----------
    'GET /suppliers'               => ['SupplierController', 'index',   ['auth' => true]],
    'POST /suppliers'              => ['SupplierController', 'store',   ['auth' => true]],
    'POST /suppliers/{id}/update'  => ['SupplierController', 'update',  ['auth' => true]],
    'POST /suppliers/{id}/delete'  => ['SupplierController', 'destroy', ['auth' => true]],

    // ---------- Produk (+ Varian) ----------
    'GET /products'                => ['ProductController', 'index',   ['auth' => true]],
    'GET /products/create'         => ['ProductController', 'create',  ['auth' => true]],
    'POST /products'               => ['ProductController', 'store',   ['auth' => true]],
    'GET /products/{id}/edit'      => ['ProductController', 'edit',    ['auth' => true]],
    'POST /products/{id}/update'   => ['ProductController', 'update',  ['auth' => true]],
    'POST /products/{id}/delete'   => ['ProductController', 'destroy', ['auth' => true]],

    // ---------- Pelanggan & Membership ----------
    'GET /customers'                      => ['CustomerController', 'index',        ['auth' => true]],
    'GET /customers/{id}'                 => ['CustomerController', 'show',         ['auth' => true]],
    'POST /customers'                     => ['CustomerController', 'store',        ['auth' => true]],
    'POST /customers/{id}/update'         => ['CustomerController', 'update',       ['auth' => true]],
    'POST /customers/{id}/delete'         => ['CustomerController', 'destroy',      ['auth' => true]],
    'POST /customers/{id}/redeem-points'  => ['CustomerController', 'redeemPoints', ['auth' => true]],
    'POST /membership-levels/{id}/update' => ['MembershipLevelController', 'update', ['auth' => true]],

    // ---------- Pembelian Barang ----------
    'GET /purchases'         => ['PurchaseController', 'index',  ['auth' => true]],
    'GET /purchases/create'  => ['PurchaseController', 'create', ['auth' => true]],
    'POST /purchases'        => ['PurchaseController', 'store',  ['auth' => true]],
    'GET /purchases/{id}'    => ['PurchaseController', 'show',   ['auth' => true]],

    // ---------- Kasir / POS ----------
    'GET /sales'                    => ['SaleController', 'index',          ['auth' => true]],
    'GET /sales/held'               => ['SaleController', 'held',           ['auth' => true]],
    'POST /sales/{id}/cancel-hold'  => ['SaleController', 'cancelHold',     ['auth' => true]],
    'GET /sales/search-product'     => ['SaleController', 'searchProduct',  ['auth' => true]],
    'GET /sales/search-customer'    => ['SaleController', 'searchCustomer', ['auth' => true]],
    'POST /sales/validate-voucher'  => ['SaleController', 'validateVoucher',['auth' => true]],
    'POST /sales/hold'              => ['SaleController', 'hold',           ['auth' => true]],
    'POST /sales/checkout'          => ['SaleController', 'checkout',       ['auth' => true]],
    'GET /sales/{id}'               => ['SaleController', 'show',           ['auth' => true]],
    'GET /sales/{id}/receipt'       => ['SaleController', 'receipt',        ['auth' => true]],

    // ---------- Retur ----------
    'GET /returns'                    => ['ReturnHubController', 'index', ['auth' => true]],

    'GET /returns/sales'              => ['SaleReturnController', 'index',  ['auth' => true]],
    'GET /returns/sales/create'       => ['SaleReturnController', 'create', ['auth' => true]],
    'POST /returns/sales'             => ['SaleReturnController', 'store',  ['auth' => true]],
    'GET /returns/sales/{id}'         => ['SaleReturnController', 'show',   ['auth' => true]],

    'GET /returns/purchases'          => ['PurchaseReturnController', 'index',  ['auth' => true]],
    'GET /returns/purchases/create'   => ['PurchaseReturnController', 'create', ['auth' => true]],
    'POST /returns/purchases'         => ['PurchaseReturnController', 'store',  ['auth' => true]],
    'GET /returns/purchases/{id}'     => ['PurchaseReturnController', 'show',   ['auth' => true]],

    // ---------- Manajemen Stok ----------
    'GET /stock'                    => ['StockController', 'index',          ['auth' => true]],
    'GET /stock/in'                 => ['StockController', 'showIn',         ['auth' => true]],
    'POST /stock/in'                => ['StockController', 'storeIn',        ['auth' => true]],
    'GET /stock/out'                => ['StockController', 'showOut',        ['auth' => true]],
    'POST /stock/out'               => ['StockController', 'storeOut',       ['auth' => true]],
    'GET /stock/mutation'           => ['StockController', 'showMutation',   ['auth' => true]],
    'POST /stock/mutation'          => ['StockController', 'storeMutation',  ['auth' => true]],
    'GET /stock/adjustment'         => ['StockController', 'showAdjustment', ['auth' => true]],
    'POST /stock/adjustment'        => ['StockController', 'storeAdjustment',['auth' => true]],
    'GET /stock/opname'             => ['StockController', 'opnameIndex',    ['auth' => true]],
    'GET /stock/opname/create'      => ['StockController', 'opnameCreate',   ['auth' => true]],
    'POST /stock/opname'            => ['StockController', 'opnameStore',    ['auth' => true]],
    'GET /stock/opname/{id}'        => ['StockController', 'opnameShow',     ['auth' => true]],

    // ---------- Laporan ----------
    'GET /reports'            => ['ReportController', 'index',     ['auth' => true]],
    'GET /reports/sales'      => ['ReportController', 'sales',     ['auth' => true]],
    'GET /reports/purchases' => ['ReportController', 'purchases', ['auth' => true]],
    'GET /reports/profit'    => ['ReportController', 'profit',    ['auth' => true]],
    'GET /reports/stock'     => ['ReportController', 'stock',     ['auth' => true]],
    'GET /reports/products'  => ['ReportController', 'products',  ['auth' => true]],
    'GET /reports/cashier'   => ['ReportController', 'cashier',   ['auth' => true]],
    'GET /reports/supplier'  => ['ReportController', 'supplier',  ['auth' => true]],
    'GET /reports/member'    => ['ReportController', 'member',    ['auth' => true]],

    // ---------- Manajemen User (khusus Owner) ----------
    'GET /users'                    => ['UserController', 'index',        ['auth' => true]],
    'GET /users/create'             => ['UserController', 'create',       ['auth' => true]],
    'POST /users'                   => ['UserController', 'store',        ['auth' => true]],
    'GET /users/{id}/edit'          => ['UserController', 'edit',         ['auth' => true]],
    'POST /users/{id}/update'       => ['UserController', 'update',       ['auth' => true]],
    'POST /users/{id}/delete'       => ['UserController', 'destroy',      ['auth' => true]],
    'POST /users/{id}/toggle-status'=> ['UserController', 'toggleStatus', ['auth' => true]],

    // ---------- Pengeluaran ----------
    'GET /expenses'                 => ['ExpenseController', 'index',   ['auth' => true]],
    'POST /expenses'                => ['ExpenseController', 'store',   ['auth' => true]],
    'POST /expenses/{id}/update'    => ['ExpenseController', 'update',  ['auth' => true]],
    'POST /expenses/{id}/delete'    => ['ExpenseController', 'destroy', ['auth' => true]],

    // ---------- Pengaturan ----------
    'GET /settings'          => ['SettingsController', 'index',   ['auth' => true]],
    'POST /settings'         => ['SettingsController', 'update',  ['auth' => true]],
    'GET /settings/backup'   => ['SettingsController', 'backup',  ['auth' => true]],
    'POST /settings/restore' => ['SettingsController', 'restore', ['auth' => true]],

    // ---------- Notifikasi ----------
    'GET /notifications' => ['NotificationController', 'index', ['auth' => true]],

    // ---------- Audit Log ----------
    'GET /audit-logs' => ['AuditLogController', 'index', ['auth' => true]],
];
