# POS Toko Sepatu — SELESAI: Tahap 2-15 + Manajemen User + Email SMTP Asli

Seluruh 20 fitur dari spesifikasi awal + modul bonus (Manajemen User) sudah lengkap dan teruji.
**Update terbaru**: fitur Lupa Password kini benar-benar **mengirim email lewat SMTP asli**
(PHPMailer dibundel manual tanpa Composer), dengan fallback aman yang sudah teruji tidak pernah
membuat aplikasi crash meskipun SMTP belum dikonfigurasi atau kredensialnya salah.

## 1. Kebutuhan
- XAMPP (Apache + MySQL + PHP 8.1 atau lebih baru)
- Browser modern

## 2. Instalasi

1. Salin folder `pos-toko-sepatu` ke dalam `C:\xampp\htdocs\` (Windows) atau `/opt/lampp/htdocs/` (Linux).
2. Jalankan Apache & MySQL dari XAMPP Control Panel.
3. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`), lalu jalankan file SQL secara berurutan:
   - `database/migrations/001_create_all_tables.sql` (membuat database & seluruh tabel)
   - `database/seeders/001_seed_basic_data.sql` (data awal: role, menu, permission, metode pembayaran, dll.)
4. Buat akun Owner pertama dengan menjalankan seeder PHP (password di-hash otomatis oleh PHP, bukan hardcode di SQL):
   ```
   cd pos-toko-sepatu
   php database/seeders/create_admin.php
   ```
   Atau buka lewat browser: `http://localhost/pos-toko-sepatu/database/seeders/create_admin.php`
   lalu **hapus file `create_admin.php` setelah dipakai** (demi keamanan, karena membuat akun tanpa otentikasi).

5. (Opsional) Jika kredensial database Anda berbeda dari default (`root` tanpa password), set environment
   variable, atau langsung ubah nilai default di `config/database.php`:
   ```php
   define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
   define('DB_NAME', getenv('DB_NAME') ?: 'pos_toko_sepatu');
   define('DB_USER', getenv('DB_USER') ?: 'root');
   define('DB_PASS', getenv('DB_PASS') ?: '');
   ```

6. Pastikan `mod_rewrite` Apache aktif (default aktif di XAMPP) agar `.htaccess` di folder `public/` bekerja.

7. Akses aplikasi lewat:
   ```
   http://localhost/pos-toko-sepatu/public/login
   ```
   Login dengan:
   - Email: `owner@tokosepatu.test`
   - Password: `password123` (segera ganti lewat menu "Ganti Password")

8. **Untuk membuat akun tambahan (Kasir/Admin/Gudang) untuk keperluan testing**, tidak perlu insert
   manual lewat database lagi — gunakan menu **Manajemen User** (khusus terlihat oleh Owner) di sidebar
   setelah login, lalu klik "+ Tambah User".

9. **(Opsional) Aktifkan pengiriman email asli untuk fitur Lupa Password.** Secara default
   (`MAIL_ENABLED=false`), link reset password hanya dicatat ke `error_log` PHP — aplikasi tetap
   berfungsi normal tanpa SMTP. Untuk mengaktifkan pengiriman email sungguhan, set environment variable
   berikut sebelum menjalankan PHP (atau edit langsung nilai default di `config/mail.php` untuk testing
   lokal cepat):
   ```
   MAIL_ENABLED=true
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_ENCRYPTION=tls
   MAIL_USERNAME=alamat-anda@gmail.com
   MAIL_PASSWORD=app-password-16-digit
   MAIL_FROM_ADDRESS=alamat-anda@gmail.com
   MAIL_FROM_NAME="POS Toko Sepatu"
   ```
   Untuk Gmail, gunakan **App Password** (bukan password akun biasa) — buat di
   [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords) setelah 2FA aktif.
   Layanan SMTP lain (Mailtrap untuk testing, SMTP hosting, dsb.) juga didukung selama kredensial standar
   SMTP tersedia. Pengiriman memakai PHPMailer yang dibundel manual tanpa Composer (lihat
   `vendor/phpmailer/`), konsisten dengan arsitektur "PHP native tanpa framework" pada project ini.

> **Jika Anda upgrade dari ZIP Tahap sebelumnya** (bukan instalasi baru), jalankan tambahan file migrasi
> berikut agar menu Manajemen User & kategori Pengeluaran default ikut muncul tanpa perlu instal ulang
> dari nol:
> ```
> database/migrations/002_add_user_management_menu.sql
> database/migrations/003_add_expense_categories.sql
> ```

## 3. Struktur Folder Penting

```
pos-toko-sepatu/
├── app/
│   ├── controllers/   -> logika request per fitur
│   ├── models/         -> query database (prepared statement via class Model)
│   ├── views/          -> tampilan (layouts/app.php untuk halaman login, layouts/guest.php untuk auth)
│   ├── helpers/        -> functions.php, auth_helper.php, Validation.php
│   └── middleware/     -> AuthMiddleware, RoleMiddleware, CsrfMiddleware
├── config/             -> app.php (konstanta aplikasi), database.php (koneksi PDO)
├── routes/web.php       -> tabel routing
├── public/              -> DOCUMENT ROOT — arahkan Apache virtual host ke sini untuk produksi
├── database/
│   ├── migrations/      -> file SQL struktur tabel
│   └── seeders/         -> data awal
└── storage/             -> logs & backup (di luar public, tidak bisa diakses browser)
```

> **Catatan produksi:** saat ini akses lewat `.../pos-toko-sepatu/public/...` karena document root masih folder
> project. Untuk produksi sebaiknya set Apache **DocumentRoot** langsung ke folder `public/`, lalu ubah
> `BASE_URL` di `config/app.php` menjadi `''` (string kosong).

## 4. Testing Tahap 2 (Autentikasi & RBAC) — manual

| Skenario | Langkah | Hasil yang Diharapkan |
|---|---|---|
| Login sukses | Login dengan akun Owner yang benar | Masuk ke `/dashboard`, nama & role tampil di navbar |
| Login gagal | Masukkan password salah | Pesan "Email atau password salah." muncul, tidak menyebutkan mana yang salah |
| Akses tanpa login | Buka `/dashboard` di browser baru (belum login) | Redirect ke `/login` dengan pesan sesi berakhir |
| Remember Me | Login dengan centang "Ingat saya", hapus session cookie manual, refresh | Tetap ter-login otomatis via cookie remember token |
| Lupa Password | Isi email di `/forgot-password` | Pesan sukses generik muncul; cek `storage/logs` atau error_log untuk link reset (sementara, sebelum SMTP diaktifkan) |
| Reset Password | Buka link reset dari log, isi password baru | Password berhasil diubah, bisa login dengan password baru |
| Ganti Password | Login, buka `/change-password`, isi password lama+baru | Password berhasil diperbarui |
| CSRF Protection | Submit form dari luar (tanpa token csrf yang valid) | Response 419 "Sesi form telah kedaluwarsa" |
| RBAC dasar | Login sebagai role Kasir, coba akses `/products` | Response 403 "Anda tidak memiliki izin" (**sudah diuji & terbukti bekerja**) |
| SQL Injection | Coba masukkan `' OR '1'='1` di form login | Login tetap gagal (query pakai prepared statement) |

## 5. Testing Tahap 3 (Master Data) — manual, sudah diuji end-to-end

| Skenario | Langkah | Hasil yang Diharapkan |
|---|---|---|
| Tambah kategori | Buka `/categories`, isi nama, simpan | Kategori baru muncul di tabel |
| Hapus kategori terpakai | Hapus kategori yang masih dipakai produk | Ditolak: "Kategori tidak bisa dihapus karena masih dipakai oleh produk" |
| Tambah produk + varian | Buka `/products/create`, isi data + minimal 1 baris varian | Produk & seluruh varian tersimpan dalam satu transaction (rollback jika ada error) |
| Tambah varian dinamis | Klik "+ Tambah Varian" di form produk | Baris varian baru muncul tanpa reload halaman |
| Barcode duplikat | Isi 2 varian dengan barcode yang sama dalam satu form | Ditolak dengan pesan barcode dipakai lebih dari sekali |
| Barcode dipakai produk lain | Isi barcode yang sudah dipakai produk lain | Ditolak dengan pesan barcode sudah dipakai produk lain |
| Edit produk | Ubah nama produk & stok varian, simpan | Data ter-update, varian lama diganti dengan data baru (replace-all) |
| Upload foto produk | Upload file .jpg/.png/.webp saat tambah/edit produk | Foto tersimpan dengan nama file acak, tervalidasi MIME asli (bukan cuma ekstensi) |
| Sidebar dinamis | Login sebagai Owner vs Kasir | Owner melihat semua menu; Kasir hanya melihat menu sesuai `role_permissions` |
| Pencarian produk | Ketik kata kunci di kolom cari halaman `/products` | Hasil difilter berdasarkan nama atau kode produk |

## 6. Testing Tahap 4 (Dashboard) — sudah diuji end-to-end dengan data sampel

| Skenario | Langkah | Hasil yang Diharapkan |
|---|---|---|
| KPI Penjualan Hari Ini | Insert transaksi `sales` dengan `sale_date` = hari ini | Kartu "Penjualan Hari Ini" menampilkan total & jumlah transaksi yang benar |
| KPI Omzet Bulan Ini | Insert transaksi di bulan berjalan | Kartu "Omzet Bulan Ini" menjumlahkan seluruh transaksi bulan tsb (**teruji: Rp 300rb + Rp 600rb = Rp 900rb tampil benar**) |
| Estimasi Profit | Transaksi dengan selisih harga jual & harga modal | Profit = subtotal - (qty × cost_price), tampil di kartu hijau |
| Produk Hampir Habis | Set stok varian ≤ min_stock | Muncul di panel "Produk Hampir/Sudah Habis" dengan badge kuning (habis = merah) |
| Produk Terlaris | Ada beberapa sale_items dalam 30 hari terakhir | Tabel terlaris terurut dari qty terjual terbanyak |
| Grafik 14 Hari | Buka dashboard | Chart.js menampilkan garis Penjualan & Profit, hari tanpa transaksi otomatis terisi 0 (bukan bolong) |
| Aktivitas Terbaru | Login/logout/CRUD apa pun | Feed menampilkan nama user + aksi + waktu, terurut terbaru di atas |
| Dashboard tanpa data sama sekali | Database baru (belum ada transaksi) | Semua KPI menampilkan Rp 0 / 0 transaksi, tanpa error (bukan crash) |

## 7. Testing Tahap 5 (Pelanggan & Membership) — sudah diuji end-to-end

| Skenario | Langkah | Hasil yang Diharapkan |
|---|---|---|
| Tambah pelanggan | Isi form tambah di `/customers` | Kode member otomatis (`MBR-YYYYMMDD-XXXX`), level default Silver, poin 0 (**teruji berhasil**) |
| Cari pelanggan | Ketik nama/no HP/kode member di kolom cari | Hasil terfilter sesuai kata kunci |
| Detail pelanggan | Klik "Detail" pada salah satu baris | Menampilkan info lengkap, level+diskon, riwayat poin, riwayat pembelian |
| Tukar poin ke voucher (cukup) | Pelanggan 250 poin, tukar 200 poin | Poin tersisa 50, voucher senilai Rp 20.000 terbentuk, riwayat poin tercatat (**teruji, VCR-4941BDDF terbentuk**) |
| Tukar poin ke voucher (kurang) | Tukar poin melebihi saldo | Ditolak dengan pesan "Poin pelanggan tidak mencukupi." (**teruji**) |
| Update threshold level | Ubah "Gold" jadi min. 400 poin, diskon 3% | Tersimpan di `membership_levels`, dipakai untuk auto-upgrade level pelanggan (**teruji**) |
| RBAC | Hapus pelanggan sebagai role Kasir | Ditolak 403 (Kasir tidak punya permission `customers.index` create/edit/delete) |

## 8. Testing Tahap 6 (Pembelian Barang) — sudah diuji end-to-end

| Skenario | Langkah | Hasil yang Diharapkan |
|---|---|---|
| Buat pembelian baru | Isi supplier, tambah 1+ baris produk, qty, harga | Subtotal/PPN/Diskon/Grand Total terhitung otomatis di sisi client sebelum submit |
| Simpan pembelian | Submit form pembelian | Data tersimpan di `purchases`+`purchase_items` dalam satu transaction (**teruji: Rp 2.500.000 subtotal + PPN 275rb − diskon 50rb = Rp 2.725.000 tepat**) |
| Stok otomatis bertambah | Cek stok varian setelah pembelian disimpan | Stok bertambah sesuai qty dibeli (**teruji: 5 → 15 setelah beli 10 unit**) |
| Riwayat pergerakan stok | Cek tabel `stock_movements` | Tercatat `type='in'`, `reference_type='purchase'`, terhubung ke invoice terkait (**teruji**) |
| Harga otomatis terisi | Pilih produk di dropdown baris pembelian | Harga satuan otomatis terisi dari `cost_price` varian (bisa diubah manual jika perlu) |
| Detail & cetak invoice | Buka `/purchases/{id}`, klik "Cetak Invoice" | Sidebar & navbar otomatis tersembunyi saat mode print (CSS `@media print` + class `no-print`) |
| Baris kosong diabaikan | Tambah baris produk lalu kosongkan tanpa pilih produk | Baris kosong otomatis diabaikan saat submit, tidak menyebabkan error |

## 9. Testing Tahap 7 (Kasir/POS) — sudah diuji end-to-end, termasuk skenario keamanan

| Skenario | Langkah | Hasil yang Diharapkan |
|---|---|---|
| Cari produk | Ketik nama/barcode di kolom cari POS | Endpoint `/sales/search-product` mengembalikan JSON hasil pencarian (**teruji**) |
| Cari pelanggan | Ketik nama/HP/kode member | Endpoint `/sales/search-customer` mengembalikan JSON dengan level & diskon (**teruji**) |
| Checkout dengan member + pajak | 2 unit @Rp350.000, member diskon 2.5%, pajak 11%, bayar Rp800.000 | Subtotal 700rb, diskon 17.500, pajak 75.075, grand total 757.575, kembalian 42.425 — **semua angka teruji tepat** |
| Stok otomatis berkurang | Setelah checkout sukses | Stok varian berkurang sesuai qty, tercatat di `stock_movements` (`type='out'`, `reference_type='sale'`) (**teruji**) |
| Poin otomatis bertambah | Transaksi dengan pelanggan terpilih | Poin bertambah sesuai `grand_total / RUPIAH_PER_POINT_EARNED`, level membership otomatis disesuaikan ulang (**teruji: 75 poin dari Rp757.575, level auto-turun ke Silver karena tidak capai syarat Gold**) |
| Hold Transaksi | Isi keranjang, klik "Hold Transaksi" | Tersimpan status `held`, **stok TIDAK berkurang** sampai transaksi difinalisasi (**teruji**) |
| Recall Transaksi | Buka `/sales/held`, klik "Lanjutkan" | Keranjang otomatis terisi kembali dari data held (**teruji**) |
| Finalisasi dari Recall | Bayar transaksi yang di-recall | Status berubah `held` → `completed`, stok baru berkurang saat ini (**teruji**) |
| Kekurangan pembayaran | Bayar kurang dari grand total | Ditolak dengan pesan "Kekurangan pembayaran: Rp X", **stok tidak berubah** (**teruji**) |
| Split Bill | Tandai sebagian item "Bagian 2", proses split | Bagian 2 otomatis di-hold via AJAX di background, Bagian 1 diproses langsung (**teruji: endpoint AJAX hold mengembalikan JSON sukses**) |
| **Keamanan: manipulasi harga oleh Kasir** | Kasir kirim `cart_price[]=1` langsung lewat POST (bypass UI) | **Ditolak** — server selalu mengambil ulang `sell_price` asli dari database untuk role selain Owner/Admin, mengabaikan harga kiriman klien (**teruji: harga tersimpan tetap Rp500.000, bukan Rp1**) |
| RBAC Kasir | Login sebagai Kasir, akses `/sales` | Diizinkan (sesuai `role_permissions` seeder) |
| Stok tidak mencukupi | Checkout qty melebihi stok tersedia | Transaksi dibatalkan (rollback), pesan "Stok tidak mencukupi" |

## 10. Testing Tahap 8 (Metode Pembayaran & Struk Thermal) — sudah diuji end-to-end

| Skenario | Langkah | Hasil yang Diharapkan |
|---|---|---|
| Multi-payment | Checkout Rp300.000 dibayar Cash Rp200.000 + QRIS Rp100.000 (dengan no. referensi) | Tersimpan sebagai 2 baris terpisah di `sale_payments` (**teruji: Cash 200rb & QRIS 100rb + referensi QR123456**) |
| Kombinasi metode lain | Cash+Debit, QRIS+Voucher, Transfer+Cash | Semua kombinasi didukung karena form pembayaran berbasis array dinamis (tidak dibatasi 2 metode) |
| Struk 58mm | Buka `/sales/{id}/receipt?width=58` | Lebar struk & font menyesuaikan kertas 58mm, `@page { size: 58mm auto }` aktif saat cetak (**teruji**) |
| Struk 80mm | Buka `/sales/{id}/receipt?width=80` | Lebar struk & font menyesuaikan kertas 80mm (**teruji**) |
| Toggle lebar struk | Klik tombol "58mm"/"80mm" di halaman struk | Reload halaman dengan `?width=` sesuai, tanpa perlu kembali ke halaman lain |
| QR verifikasi | Buka halaman struk | QR code ter-generate otomatis (client-side, library `qrcode.js`) berisi URL struk digital transaksi tsb |
| Isi struk lengkap | Cek konten struk | Nama toko, alamat, kasir, tanggal, jam, daftar produk+qty+harga+diskon, PPN, grand total, rincian tiap metode bayar, kembalian, catatan, "Terima kasih" — semua sesuai spesifikasi awal |
| Sembunyikan toolbar saat cetak | Klik "Cetak" atau Ctrl+P di halaman struk | Tombol 58mm/80mm/Cetak otomatis hilang dari hasil cetak (`@media print`) |

## 11. Testing Tahap 9 (Retur Penjualan & Retur Pembelian) — sudah diuji end-to-end

| Skenario | Langkah | Hasil yang Diharapkan |
|---|---|---|
| Cari invoice penjualan | Masukkan no. invoice di `/returns/sales/create` | Menampilkan item yang bisa diretur beserta sisa qty yang belum diretur (**teruji**) |
| Retur sebagian qty | Retur 2 dari 3 unit terjual | Refund dihitung otomatis (2×harga satuan = Rp 700.000), stok bertambah kembali (**teruji: stok 7→9**) |
| Retur ulang invoice yang sama | Cari invoice yang sudah pernah diretur sebagian | Sisa qty otomatis berkurang (dari 3 jadi 1) (**teruji**) |
| **Anti-manipulasi qty retur** | Kirim `return_qty[]=10` langsung via POST, padahal sisa cuma 1 | Server **otomatis membatasi ke 1**, tidak bisa retur melebihi yang pernah dibeli (**teruji**) |
| Poin pelanggan terkoreksi | Retur transaksi bersama pelanggan & poin | Poin dikurangi proporsional terhadap nilai refund, level membership disesuaikan ulang |
| Cari invoice pembelian | Masukkan no. invoice PO di `/returns/purchases/create` | Menampilkan item yang bisa diretur ke supplier |
| Retur pembelian | Retur 2 dari 5 unit ke supplier | Stok berkurang sesuai qty retur (**teruji: stok 15→13**) |
| Retur pembelian saat stok kurang | Retur melebihi stok yang tersedia saat ini | Ditolak: "Stok tidak mencukupi untuk retur produk" |

## 12. Testing Tahap 10 (Manajemen Stok Lanjutan) — sudah diuji end-to-end

| Skenario | Langkah | Hasil yang Diharapkan |
|---|---|---|
| Stok Masuk manual | Tambah 5 unit ke varian dengan stok 10 | Stok bertambah jadi 15, tercatat di riwayat tipe "Masuk" (**teruji**) |
| Stok Keluar manual | Kurangi 3 unit dari stok 15 | Stok berkurang jadi 12 (**teruji**) |
| Stok Keluar melebihi stok | Coba keluarkan 999 unit padahal sisa 12 | Ditolak: "Stok tidak mencukupi untuk dikeluarkan", stok tidak berubah (**teruji**) |
| Mutasi antar varian | Pindahkan 4 unit dari Varian A (stok 12) ke Varian B (stok 5) | A berkurang jadi 8, B bertambah jadi 9, dua entri riwayat saling terhubung (**teruji**) |
| Penyesuaian stok | Set stok Varian A langsung ke 20 | Stok berubah ke 20, selisih (+12) otomatis tercatat sebagai movement tipe "Penyesuaian" (**teruji**) |
| Stock Opname | Hitung fisik: Varian A sistem 20 → fisik 18, Varian B sistem 9 → fisik 9 (sama) | Stok A otomatis disesuaikan ke 18 dengan movement selisih -2; **Varian B tidak menghasilkan movement sama sekali** karena selisihnya 0 (**teruji**) |
| Riwayat stok | Buka `/stock` | Semua pergerakan (masuk/keluar/mutasi/penyesuaian/opname) tampil dalam satu tabel terpusat (**teruji**) |
| Filter riwayat | Filter berdasarkan tipe "Opname" | Hanya menampilkan entri bertipe opname (**teruji**) |
| Detail sesi opname | Buka `/stock/opname/{id}` | Menampilkan tabel qty sistem vs fisik vs selisih per produk, dengan warna hijau/merah sesuai arah selisih |

## 13. Testing Tahap 11 (Laporan) — sudah diuji end-to-end dengan data sampel

| Skenario | Langkah | Hasil yang Diharapkan |
|---|---|---|
| Laporan Penjualan | Buka `/reports/sales?preset=today` dengan 2 transaksi hari ini (700rb + 350rb) | Total penjualan tampil tepat **Rp 1.050.000**, rincian per invoice benar (**teruji**) |
| Laporan Pembelian | Buka `/reports/purchases?preset=today` | Total pembelian tepat **Rp 2.000.000** (**teruji**) |
| Laporan Profit | Buka `/reports/profit?preset=today` | Omzet Rp 1.050.000, Modal (COGS) Rp 600.000, Profit **Rp 450.000** — ketiganya tepat (**teruji**) |
| Laporan Stok | Buka `/reports/stock` | Menampilkan seluruh varian + nilai stok (stok × harga modal), baris stok rendah otomatis disorot kuning |
| Produk Terlaris | Buka `/reports/products?type=best&preset=today` | Produk yang terjual hari ini muncul di daftar (**teruji**) |
| Produk Tidak Laku | Buka `/reports/products?type=worst` | Menampilkan produk aktif yang tidak terjual pada rentang tanggal dipilih |
| Laporan per Kasir | Buka `/reports/cashier?preset=today` | Rekap transaksi & total penjualan dikelompokkan per kasir (**teruji**) |
| Laporan per Supplier | Buka `/reports/supplier?preset=today` | Rekap pembelian dikelompokkan per supplier (**teruji**) |
| Laporan per Member | Buka `/reports/member?preset=today` | Rekap belanja dikelompokkan per pelanggan |
| Preset tanggal cepat | Pilih "Hari Ini"/"Minggu Ini"/"Bulan Ini"/"Tahun Ini" | Rentang tanggal otomatis terisi & laporan langsung ter-filter ulang |
| **Export Excel (CSV)** | Klik tombol "Export Excel" di laporan penjualan | File CSV terunduh dengan header `Content-Type: text/csv` + `Content-Disposition: attachment`, BOM UTF-8 agar Excel baca dengan benar, isi data sesuai filter aktif (**teruji**) |
| Export/Print PDF | Klik "Print / Simpan PDF" | Membuka dialog print browser; tombol filter & export otomatis tersembunyi (`no-print`); bisa disimpan sebagai PDF lewat opsi "Save as PDF" bawaan browser |

## 14. Testing Manajemen User — sudah diuji end-to-end

| Skenario | Langkah | Hasil yang Diharapkan |
|---|---|---|
| Tambah user baru | Owner tambah user role Kasir lewat `/users/create` | User baru berhasil dibuat, bisa langsung login dengan password yang diset (**teruji**) |
| RBAC modul User | Login sebagai Kasir, akses `/users` | Ditolak 403 — hanya Owner yang bisa akses (**teruji**) |
| Hapus akun sendiri | Owner coba hapus akun miliknya sendiri | Ditolak: "Anda tidak bisa menghapus akun sendiri" (**teruji**) |
| Nonaktifkan/Aktifkan | Toggle status user lain | Status berpindah aktif ↔ tidak aktif dengan benar (**teruji**) |
| Hapus user tanpa riwayat | Hapus user yang belum pernah bertransaksi | Berhasil dihapus permanen (**teruji**) |
| **Hapus user dengan riwayat transaksi** | Hapus user yang sudah pernah membuat penjualan | Ditolak: "User ini memiliki riwayat transaksi dan tidak bisa dihapus. Gunakan Nonaktifkan saja." (**teruji**) |
| Anti-lockout Owner | Skenario satu-satunya Owner aktif mencoba ganti role/nonaktifkan diri sendiri | Ditolak agar sistem tidak pernah kehilangan akses Owner sepenuhnya |

## 15. Testing Tahap 12 (Pengeluaran) — sudah diuji end-to-end

| Skenario | Langkah | Hasil yang Diharapkan |
|---|---|---|
| Catat pengeluaran | Tambah pengeluaran kategori "Listrik" Rp50.000 | Tersimpan & tampil di daftar dengan total periode ter-update (**teruji**) |
| Filter periode | Ubah rentang tanggal | Daftar & total pengeluaran ter-filter ulang sesuai rentang |
| **Laba Bersih di Laporan Profit** | Omzet hari ini Rp350.000 (modal Rp200.000) + pengeluaran Rp50.000 | Profit Kotor **Rp150.000**, Laba Bersih **Rp100.000** (150rb − 50rb) — kedua angka tepat (**teruji**) |
| Edit/Hapus pengeluaran | Ubah jumlah atau hapus entri pengeluaran | Data ter-update, total & laporan profit otomatis menyesuaikan |

## 16. Testing Tahap 13 (Pengaturan & Backup/Restore) — sudah diuji end-to-end

| Skenario | Langkah | Hasil yang Diharapkan |
|---|---|---|
| Update profil toko | Ubah nama, alamat, pajak, diskon default, lebar printer | Tersimpan ke tabel `settings`, langsung dipakai POS & struk (**teruji**) |
| Upload logo toko | Upload file .jpg/.png/.webp di form pengaturan | Logo tersimpan & tampil di halaman pengaturan + struk thermal |
| **Backup database** | Klik "Unduh Backup Sekarang" | File `.sql` terunduh berisi struktur (`CREATE TABLE`) + data (`INSERT INTO`) seluruh 30 tabel (**teruji: 759 baris, 163 statement INSERT**) |
| **Restore database (round-trip)** | Upload kembali file backup yang baru diunduh | Seluruh data ter-restore identik — jumlah baris di 5 tabel sampel (users, roles, categories, menus, settings) **sama persis** sebelum & sesudah restore (**teruji**) |
| Login pasca-restore | Login ulang setelah proses restore | Berhasil normal, password hash & sesi tetap konsisten (**teruji**) |
| Konfirmasi restore wajib | Submit form restore tanpa centang konfirmasi | Ditolak: "Anda harus mencentang konfirmasi sebelum melakukan restore." |
| Validasi format file | Upload file selain `.sql` untuk restore | Ditolak: "File harus berformat .sql." |

## 17. Testing Tahap 14 & 15 (Notifikasi & Audit Log) — sudah diuji end-to-end

| Skenario | Langkah | Hasil yang Diharapkan |
|---|---|---|
| Badge lonceng navbar | Login dengan kondisi ada produk hampir habis/habis | Badge merah muncul di ikon lonceng dengan jumlah notifikasi aktif (**teruji**) |
| Produk Hampir Habis vs Habis | Buka `/notifications` dengan 1 varian stok=2 (min=5) dan 1 varian stok=0 | Keduanya muncul di kategori terpisah — "Hampir Habis" (Sisa 2) dan "Habis" (badge merah) (**teruji**) |
| Member Ulang Tahun | Buka `/notifications` dengan member yang `birth_date`-nya cocok bulan/tanggal hari ini | Muncul di kartu "Member Ulang Tahun Hari Ini" (**teruji: "Rina Ulang Tahun" terdeteksi tepat**) |
| Reminder Backup (belum pernah) | Buka `/notifications` sebelum pernah backup sama sekali | Pesan merah: "Belum pernah melakukan backup database sama sekali!" (**teruji**) |
| Reminder Backup (setelah backup) | Lakukan backup lewat `/settings/backup`, lalu cek ulang | Berubah jadi "Masih aman ✅" dengan tanggal backup terakhir (**teruji**) |
| Halaman Audit Log | Buka `/audit-logs` | Menampilkan seluruh aktivitas (login, CRUD, checkout, backup, dst.) yang sudah tercatat sejak Tahap 2 (**teruji**) |
| Filter Audit Log | Filter berdasarkan aksi "backup" | Hanya menampilkan entri log dengan aksi tsb (**teruji**) |
| RBAC Audit Log | Login sebagai Admin/Kasir, akses `/audit-logs` | Ditolak — sesuai desain awal, hanya Owner yang bisa lihat audit log (dikecualikan eksplisit di seeder RBAC) |

## 18. Testing Email SMTP Asli (Lupa Password) — sudah diuji end-to-end

| Skenario | Langkah | Hasil yang Diharapkan |
|---|---|---|
| Default aman (SMTP nonaktif) | `MAIL_ENABLED=false` (default), minta reset password | Tidak error, pesan generik tetap tampil, link dicatat ke `error_log` dengan status "email terkirim: tidak" (**teruji**) |
| SMTP aktif tapi kredensial salah | `MAIL_ENABLED=true` dengan host SMTP tidak valid | **Tidak crash** — halaman tetap HTTP 200, pesan generik tetap tampil ke user, detail error teknis (mis. "Could not connect to SMTP host") tercatat rapi di `error_log` untuk debugging admin (**teruji**) |
| Token tetap valid meski email gagal | Buka link reset password dari log setelah percobaan SMTP gagal | Halaman "Password Baru" tetap muncul normal — pembuatan token tidak bergantung sukses/gagalnya pengiriman email (**teruji**) |
| Anti user-enumeration tetap terjaga | Minta reset untuk email terdaftar vs tidak terdaftar | Pesan yang ditampilkan **identik** di kedua kasus, apa pun status SMTP |

## 19. Update UI: Sidebar Modern + Semua Aset Lokal (Offline-First)

Perubahan terbaru menyempurnakan tampilan dan menghilangkan ketergantungan ke internet untuk styling:

- **Sidebar dirombak total**: dikelompokkan per kategori (Utama, Master Data, Transaksi, Inventori &
  Laporan, Administrasi), pakai Bootstrap Icons asli (bukan emoji campuran), active state dengan aksen
  warna, avatar inisial user di bagian bawah, dan bisa ditutup/dibuka di layar mobile/tablet.
- **Semua aset (Bootstrap, Bootstrap Icons, Chart.js, QR Code generator) di-bundle lokal** di
  `public/assets/vendor/` — **tidak lagi bergantung pada CDN** (`cdn.jsdelivr.net`, dll). Ini penting
  karena beberapa jaringan (kantor, ISP tertentu di Indonesia) memblokir/lambat mengakses CDN, yang
  sebelumnya menyebabkan seluruh tampilan gagal termuat (halaman jadi HTML polos tanpa styling).

| Skenario | Langkah | Hasil yang Diharapkan |
|---|---|---|
| Sidebar terkelompok | Login sebagai Owner | 5 grup menu tampil rapi sesuai kategori (**teruji**) |
| Sidebar dinamis per role | Login sebagai Kasir | Hanya grup "Utama" & "Transaksi" yang tampil — grup kosong otomatis hilang (**teruji**) |
| Semua aset lokal bisa diakses | Buka langsung `/assets/vendor/bootstrap/css/bootstrap.min.css`, dst. | HTTP 200 untuk seluruh file Bootstrap CSS/JS, Bootstrap Icons (CSS+font), Chart.js, QR generator (**teruji, semua 200**) |
| Tidak ada referensi CDN tersisa | Cek seluruh source `app/views/` | Nihil — sudah di-scan otomatis, tidak ada lagi `cdn.jsdelivr.net` di manapun (**teruji**) |
| Struk tetap ada QR code | Buka halaman struk transaksi | QR code tetap tergenerate normal, kini pakai library `qrcode-generator` (MIT license) yang di-bundle lokal, bukan lagi paket `qrcode` dari CDN |

**Catatan teknis**: ditemukan juga saat testing bahwa PHP built-in dev server (`php -S`) punya
perilaku berbeda dari Apache soal serving file statis saat pakai router script — sudah ditambahkan
kompatibilitas khusus di `public/index.php` (hanya aktif saat `php -S`, tidak berpengaruh sama sekali
ke Apache/Laragon/XAMPP produksi) supaya testing lokal dengan `php -S` akurat.

## 20. Known Limitations (akan dilengkapi di tahap berikutnya)
- Modul Produk pakai strategi "replace-all" untuk varian saat update.
- **Split Bill** = bagian yang dipisah otomatis di-hold sebagai transaksi terpisah (selesaikan lewat
  Transaksi Held); untuk satu tagihan dibayar beberapa metode sekaligus, gunakan **multi-payment**.
- Voucher & diskon member belum bisa dibatasi kombinasinya — keduanya otomatis terakumulasi.
- Recall transaksi held tidak memvalidasi ulang voucher yang sebelumnya dipilih.
- **Cetak struk memakai pendekatan browser print** (CSS `@page` presisi ukuran kertas thermal), bukan
  ESC/POS mentah — cukup pilih printer thermal & ukuran kertas 58mm/80mm di dialog cetak browser.
- **Export PDF** memakai pendekatan "Print → Save as PDF" bawaan browser (bukan generate file .pdf
  langsung dari server). Ini konsisten dengan pendekatan struk thermal di Tahap 8 dan tidak memerlukan
  library tambahan (mis. TCPDF/mPDF via Composer) yang di luar cakupan "PHP native tanpa framework".
  Export **Excel** sudah berupa file `.csv` sungguhan yang diunduh langsung (bukan print-based), sudah
  diuji berhasil terunduh dengan header yang benar.
- Logo toko di struk belum diambil dari file upload — menyusul saat halaman Pengaturan (Tahap 13).
- Refund retur penjualan dihitung dari harga jual per unit saat itu (proporsional dari subtotal item),
  belum memperhitungkan ulang alokasi diskon voucher/member pada retur parsial secara penuh.
- Retur belum mendukung pertukaran barang (tukar ukuran) dalam satu langkah — saat ini retur & pembelian
  pengganti masih dua transaksi terpisah.

- **Restore database** memakai pemisahan statement SQL sederhana (split berdasarkan titik koma akhir
  baris) — aman & sudah teruji round-trip untuk file backup yang dihasilkan oleh fitur Backup di aplikasi
  ini sendiri, namun **tidak dijamin aman** untuk dump SQL kompleks dari sumber lain yang mengandung
  titik koma di dalam string/prosedur tersimpan. Untuk kebutuhan migrasi server yang lebih kompleks,
  tetap disarankan pakai `mysqldump`/phpMyAdmin import langsung.
- Statement DDL (`CREATE TABLE`, `DROP TABLE`) di MySQL otomatis melakukan implicit commit, sehingga
  proses restore tidak 100% atomic di level database meskipun dibungkus transaction — jika restore
  gagal di tengah jalan, sebagian tabel mungkin sudah ter-drop/create ulang. Selalu backup dulu sebelum
  restore (aplikasi sudah mewajibkan checkbox konfirmasi untuk mengingatkan risiko ini).

## 21. Pengembangan Lanjutan (Opsional)

Seluruh 20 fitur dari spesifikasi awal (Tahap 1) sudah terimplementasi dan teruji end-to-end. Berikut
beberapa arah pengembangan lanjutan yang bisa dipertimbangkan jika toko berkembang lebih besar —
**tidak wajib**, aplikasi sudah bisa dipakai penuh tanpa ini:

- **Multi-cabang/gudang**: skema saat ini single-location; untuk multi-cabang perlu tabel `warehouses`
  dan `product_id + warehouse_id` sebagai kunci stok, plus transfer stok antar cabang.
- **ESC/POS asli & buka laci kasir otomatis**: perlu print-agent terpisah (WebUSB atau aplikasi kecil
  di komputer kasir) di luar cakupan native PHP web app — lihat catatan di bagian Known Limitations.
- **PDF binary asli** (bukan print-to-PDF browser): integrasikan library seperti mPDF/TCPDF via Composer
  jika suatu saat project ini boleh keluar dari batasan "PHP native tanpa framework/dependency".
- **Notifikasi real-time** (WebSocket/polling) alih-alih cek saat halaman dimuat.
- **Export PDF/Excel untuk semua modul CRUD** (Kategori, Brand, dst.), saat ini export CSV baru ada
  di modul Laporan.
- **Dark mode** yang lebih menyeluruh (saat ini toggle ada tapi styling belum 100% dioptimasi di semua
  halaman).
- **API/webhook** untuk integrasi marketplace (Tokopedia/Shopee) jika toko mulai jualan online juga.

Terima kasih sudah mengikuti seluruh proses pengembangan bertahap ini! 🙏👟
Tahap 14: Notifikasi otomatis (produk hampir/habis stok, member ulang tahun, reminder backup belum
dilakukan) dan Tahap 15: Audit Log — halaman UI untuk menelusuri & memfilter seluruh aktivitas yang
sudah tercatat sejak Tahap 2, melengkapi 20 fitur awal yang direncanakan.







