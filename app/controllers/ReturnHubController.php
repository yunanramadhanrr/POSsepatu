<?php
class ReturnHubController
{
    /** GET /returns — halaman pilihan antara Retur Penjualan & Retur Pembelian */
    public function index(): void
    {
        RoleMiddleware::handle('returns.index', 'view');
        require __DIR__ . '/../views/returns/hub.php';
    }
}
