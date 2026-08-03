<?php
$title = 'Pengaturan';
$currentRouteKey = 'settings.index';
ob_start();
?>

<h5 class="mb-3">Pengaturan</h5>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-title">Profil Toko</h6>
                <form method="POST" action="<?= url('/settings') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Nama Toko</label>
                        <input type="text" name="store_name" class="form-control" value="<?= e($settings['store_name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat Toko</label>
                        <textarea name="store_address" class="form-control" rows="2"><?= e($settings['store_address']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Logo Toko</label><br>
                        <?php if ($settings['store_logo']): ?>
                            <img src="<?= e(url('/uploads/store/' . $settings['store_logo'])) ?>" width="60" height="60" class="rounded mb-2" style="object-fit:cover;">
                            <br>
                        <?php endif; ?>
                        <input type="file" name="store_logo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Pajak Default (%)</label>
                            <input type="number" step="0.01" min="0" name="store_tax_percent" class="form-control" value="<?= e($settings['store_tax_percent']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Diskon Default (%)</label>
                            <input type="number" step="0.01" min="0" name="default_discount_percent" class="form-control" value="<?= e($settings['default_discount_percent']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Lebar Printer Struk</label>
                            <select name="receipt_printer_width" class="form-select">
                                <option value="58" <?= $settings['receipt_printer_width'] === '58' ? 'selected' : '' ?>>58mm</option>
                                <option value="80" <?= $settings['receipt_printer_width'] === '80' ? 'selected' : '' ?>>80mm</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">Simpan Pengaturan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6 class="card-title">Backup Database</h6>
                <p class="text-muted small">
                    Unduh salinan lengkap database (struktur + data) sebagai file .sql. Simpan file ini
                    di tempat yang aman secara berkala.
                </p>
                <a href="<?= url('/settings/backup') ?>" class="btn btn-success">⬇️ Unduh Backup Sekarang</a>
            </div>
        </div>

        <div class="card shadow-sm border-danger">
            <div class="card-body">
                <h6 class="card-title text-danger">Restore Database</h6>
                <p class="text-muted small">
                    <strong>⚠️ Peringatan:</strong> Restore akan MENIMPA seluruh data yang ada saat ini
                    dengan isi file backup yang diupload. Aksi ini tidak bisa dibatalkan. Pastikan Anda
                    sudah mengunduh backup terbaru sebelum melanjutkan.
                </p>
                <form method="POST" action="<?= url('/settings/restore') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">File Backup (.sql)</label>
                        <input type="file" name="backup_file" class="form-control" accept=".sql" required>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="confirm_restore" value="1" class="form-check-input" id="confirmRestore" required>
                        <label class="form-check-label small" for="confirmRestore">
                            Saya paham aksi ini akan menimpa seluruh data yang ada saat ini.
                        </label>
                    </div>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin ingin restore? Seluruh data saat ini akan tertimpa!');">
                        Restore Database
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/app.php';
