<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Validation.php';

class UserController
{
    /** GET /users */
    public function index(): void
    {
        require_role(['Owner']);
        $users = User::allWithRole();
        $roles = self::roles();
        require __DIR__ . '/../views/users/index.php';
    }

    /** GET /users/create */
    public function create(): void
    {
        require_role(['Owner']);
        $roles = self::roles();
        require __DIR__ . '/../views/users/create.php';
    }

    /** POST /users */
    public function store(): void
    {
        require_role(['Owner']);

        $validator = new Validation($_POST);
        $validator->required('name', 'Nama wajib diisi')
                  ->required('email', 'Email wajib diisi')
                  ->email('email', 'Format email tidak valid')
                  ->required('password', 'Password wajib diisi')
                  ->minLength('password', 8, 'Password minimal 8 karakter')
                  ->required('role_id', 'Role wajib dipilih');

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/users/create');
        }

        if (User::findByEmail(trim($_POST['email']))) {
            flash('errors', 'Email sudah dipakai user lain.');
            redirect('/users/create');
        }

        $userId = User::insert([
            'role_id'  => (int) $_POST['role_id'],
            'name'     => trim($_POST['name']),
            'email'    => trim($_POST['email']),
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'status'   => $_POST['status'] ?? 'active',
        ]);

        AuditLog::record(current_user()['id'], 'create', 'users', $userId, null, trim($_POST['name']));

        flash('success', 'User berhasil ditambahkan.');
        redirect('/users');
    }

    /** GET /users/{id}/edit */
    public function edit(string $id): void
    {
        require_role(['Owner']);

        $user = User::find((int) $id);
        if (!$user) {
            abort(404, 'User tidak ditemukan.');
        }

        $roles = self::roles();
        require __DIR__ . '/../views/users/edit.php';
    }

    /** POST /users/{id}/update */
    public function update(string $id): void
    {
        require_role(['Owner']);

        $user = User::find((int) $id);
        if (!$user) {
            abort(404, 'User tidak ditemukan.');
        }

        $validator = new Validation($_POST);
        $validator->required('name', 'Nama wajib diisi')
                  ->required('email', 'Email wajib diisi')
                  ->email('email', 'Format email tidak valid')
                  ->required('role_id', 'Role wajib dipilih');

        if (!empty($_POST['password'])) {
            $validator->minLength('password', 8, 'Password baru minimal 8 karakter');
        }

        if ($validator->fails()) {
            flash('errors', implode('<br>', $validator->allMessages()));
            redirect('/users/' . $id . '/edit');
        }

        $emailOwner = User::findByEmail(trim($_POST['email']));
        if ($emailOwner && (int) $emailOwner['id'] !== (int) $id) {
            flash('errors', 'Email sudah dipakai user lain.');
            redirect('/users/' . $id . '/edit');
        }

        // Cegah owner mengunci diri sendiri: tidak boleh nonaktifkan diri sendiri atau ganti role sendiri
        // jika itu berarti tidak ada Owner aktif lain yang tersisa.
        $isSelf = (int) current_user()['id'] === (int) $id;
        $newRole = self::roleNameById((int) $_POST['role_id']);
        $newStatus = $_POST['status'] ?? 'active';

        if ($isSelf && ($newRole !== 'Owner' || $newStatus !== 'active') && User::countOtherActiveOwners((int) $id) === 0) {
            flash('errors', 'Tidak bisa mengubah role/status akun sendiri karena Anda adalah satu-satunya Owner aktif.');
            redirect('/users/' . $id . '/edit');
        }

        $updateData = [
            'name'    => trim($_POST['name']),
            'email'   => trim($_POST['email']),
            'role_id' => (int) $_POST['role_id'],
            'status'  => $newStatus,
        ];

        if (!empty($_POST['password'])) {
            $updateData['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        User::update((int) $id, $updateData);

        AuditLog::record(current_user()['id'], 'update', 'users', (int) $id, $user['name'], trim($_POST['name']));

        flash('success', 'User berhasil diperbarui.');
        redirect('/users');
    }

    /** POST /users/{id}/delete */
    public function destroy(string $id): void
    {
        require_role(['Owner']);

        $user = User::find((int) $id);
        if (!$user) {
            abort(404, 'User tidak ditemukan.');
        }

        if ((int) current_user()['id'] === (int) $id) {
            flash('errors', 'Anda tidak bisa menghapus akun sendiri.');
            redirect('/users');
        }

        if (self::roleNameById((int) $user['role_id']) === 'Owner' && User::countOtherActiveOwners((int) $id) === 0) {
            flash('errors', 'Tidak bisa menghapus satu-satunya Owner aktif.');
            redirect('/users');
        }

        if (User::hasTransactionHistory((int) $id)) {
            flash('errors', 'User ini memiliki riwayat transaksi dan tidak bisa dihapus. Gunakan "Nonaktifkan" saja.');
            redirect('/users');
        }

        User::delete((int) $id);
        AuditLog::record(current_user()['id'], 'delete', 'users', (int) $id, $user['name'], null);

        flash('success', 'User berhasil dihapus.');
        redirect('/users');
    }

    /** POST /users/{id}/toggle-status — aktifkan/nonaktifkan akun tanpa menghapus data */
    public function toggleStatus(string $id): void
    {
        require_role(['Owner']);

        $user = User::find((int) $id);
        if (!$user) {
            abort(404, 'User tidak ditemukan.');
        }

        if ((int) current_user()['id'] === (int) $id) {
            flash('errors', 'Anda tidak bisa menonaktifkan akun sendiri.');
            redirect('/users');
        }

        $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';

        if ($newStatus === 'inactive'
            && self::roleNameById((int) $user['role_id']) === 'Owner'
            && User::countOtherActiveOwners((int) $id) === 0
        ) {
            flash('errors', 'Tidak bisa menonaktifkan satu-satunya Owner aktif.');
            redirect('/users');
        }

        User::update((int) $id, ['status' => $newStatus]);
        AuditLog::record(current_user()['id'], 'toggle_status', 'users', (int) $id, $user['status'], $newStatus);

        flash('success', 'Status user berhasil diubah menjadi ' . ($newStatus === 'active' ? 'Aktif' : 'Tidak Aktif') . '.');
        redirect('/users');
    }

    private static function roles(): array
    {
        return User::raw('SELECT * FROM roles ORDER BY id ASC');
    }

    private static function roleNameById(int $roleId): ?string
    {
        $rows = User::raw('SELECT name FROM roles WHERE id = :id', [':id' => $roleId]);
        return $rows[0]['name'] ?? null;
    }
}
