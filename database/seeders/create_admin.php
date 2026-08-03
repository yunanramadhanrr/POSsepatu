<?php
/**
 * Jalankan SATU KALI setelah migrasi + seeder dasar untuk membuat akun Owner pertama.
 *
 * Cara pakai (CLI, direkomendasikan):
 *   php database/seeders/create_admin.php
 *
 * Atau lewat browser (lalu HAPUS file ini setelah selesai demi keamanan):
 *   http://localhost/pos-toko-sepatu/database/seeders/create_admin.php
 */

require_once __DIR__ . '/../../config/database.php';

$name  = 'Owner Toko';
$email = 'owner@tokosepatu.test';
$plainPassword = 'password123'; // WAJIB diganti lewat menu "Ganti Password" setelah login pertama

$db = Database::getConnection();

// Cegah duplikasi jika script dijalankan berkali-kali
$check = $db->prepare('SELECT id FROM users WHERE email = :email');
$check->execute([':email' => $email]);

if ($check->fetch()) {
    echo "User dengan email {$email} sudah ada. Tidak ada perubahan.\n";
    exit;
}

$roleStmt = $db->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
$roleStmt->execute([':name' => 'Owner']);
$role = $roleStmt->fetch();

if (!$role) {
    die("Role 'Owner' tidak ditemukan. Jalankan seeder 001_seed_basic_data.sql terlebih dahulu.\n");
}

$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

$insert = $db->prepare(
    'INSERT INTO users (role_id, name, email, password, status) VALUES (:role_id, :name, :email, :password, "active")'
);
$insert->execute([
    ':role_id'  => $role['id'],
    ':name'     => $name,
    ':email'    => $email,
    ':password' => $hashedPassword,
]);

echo "Akun Owner berhasil dibuat.\n";
echo "Email    : {$email}\n";
echo "Password : {$plainPassword} (segera ganti setelah login!)\n";
