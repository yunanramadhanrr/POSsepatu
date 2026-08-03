<?php
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Validation.php';

class CategoryController
{
    /** GET /categories */
    public function index(): void
    {
        RoleMiddleware::handle('categories.index', 'view');
        $categories = Category::all('name ASC');
        require __DIR__ . '/../views/categories/index.php';
    }

    /** POST /categories */
    public function store(): void
    {
        RoleMiddleware::handle('categories.index', 'create');

        $validator = new Validation($_POST);
        $validator->required('name', 'Nama kategori wajib diisi');

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/categories');
        }

        $id = Category::insert(['name' => trim($_POST['name'])]);
        AuditLog::record(current_user()['id'], 'create', 'categories', $id, null, trim($_POST['name']));

        flash('success', 'Kategori berhasil ditambahkan.');
        redirect('/categories');
    }

    /** POST /categories/{id}/update */
    public function update(string $id): void
    {
        RoleMiddleware::handle('categories.index', 'edit');

        $category = Category::find((int) $id);
        if (!$category) {
            abort(404, 'Kategori tidak ditemukan.');
        }

        $validator = new Validation($_POST);
        $validator->required('name', 'Nama kategori wajib diisi');

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/categories');
        }

        Category::update((int) $id, ['name' => trim($_POST['name'])]);
        AuditLog::record(current_user()['id'], 'update', 'categories', (int) $id, $category['name'], trim($_POST['name']));

        flash('success', 'Kategori berhasil diperbarui.');
        redirect('/categories');
    }

    /** POST /categories/{id}/delete */
    public function destroy(string $id): void
    {
        RoleMiddleware::handle('categories.index', 'delete');

        $category = Category::find((int) $id);
        if (!$category) {
            abort(404, 'Kategori tidak ditemukan.');
        }

        if (Category::countProducts((int) $id) > 0) {
            flash('errors', 'Kategori tidak bisa dihapus karena masih dipakai oleh produk.');
            redirect('/categories');
        }

        Category::delete((int) $id);
        AuditLog::record(current_user()['id'], 'delete', 'categories', (int) $id, $category['name'], null);

        flash('success', 'Kategori berhasil dihapus.');
        redirect('/categories');
    }
}
