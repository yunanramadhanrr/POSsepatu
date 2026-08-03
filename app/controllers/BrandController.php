<?php
require_once __DIR__ . '/../models/Brand.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Validation.php';

class BrandController
{
    /** GET /brands */
    public function index(): void
    {
        RoleMiddleware::handle('brands.index', 'view');
        $brands = Brand::all('name ASC');
        require __DIR__ . '/../views/brands/index.php';
    }

    /** POST /brands */
    public function store(): void
    {
        RoleMiddleware::handle('brands.index', 'create');

        $validator = new Validation($_POST);
        $validator->required('name', 'Nama brand wajib diisi');

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/brands');
        }

        $id = Brand::insert(['name' => trim($_POST['name'])]);
        AuditLog::record(current_user()['id'], 'create', 'brands', $id, null, trim($_POST['name']));

        flash('success', 'Brand berhasil ditambahkan.');
        redirect('/brands');
    }

    /** POST /brands/{id}/update */
    public function update(string $id): void
    {
        RoleMiddleware::handle('brands.index', 'edit');

        $brand = Brand::find((int) $id);
        if (!$brand) {
            abort(404, 'Brand tidak ditemukan.');
        }

        $validator = new Validation($_POST);
        $validator->required('name', 'Nama brand wajib diisi');

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/brands');
        }

        Brand::update((int) $id, ['name' => trim($_POST['name'])]);
        AuditLog::record(current_user()['id'], 'update', 'brands', (int) $id, $brand['name'], trim($_POST['name']));

        flash('success', 'Brand berhasil diperbarui.');
        redirect('/brands');
    }

    /** POST /brands/{id}/delete */
    public function destroy(string $id): void
    {
        RoleMiddleware::handle('brands.index', 'delete');

        $brand = Brand::find((int) $id);
        if (!$brand) {
            abort(404, 'Brand tidak ditemukan.');
        }

        if (Brand::countProducts((int) $id) > 0) {
            flash('errors', 'Brand tidak bisa dihapus karena masih dipakai oleh produk.');
            redirect('/brands');
        }

        Brand::delete((int) $id);
        AuditLog::record(current_user()['id'], 'delete', 'brands', (int) $id, $brand['name'], null);

        flash('success', 'Brand berhasil dihapus.');
        redirect('/brands');
    }
}
