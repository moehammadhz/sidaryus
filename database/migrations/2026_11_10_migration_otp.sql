-- ========================================================
-- Migrasi Manual / PHP Native: Penambahan Verifikasi WhatsApp & Tabel OTP
-- Database: sidaryus (MySQL)
-- ========================================================

-- 1. Tambah kolom no_wa dan wa_verified_at pada tabel users (jika belum ada)
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `no_wa` VARCHAR(20) NULL UNIQUE AFTER `email`,
    ADD COLUMN IF NOT EXISTS `wa_verified_at` TIMESTAMP NULL AFTER `no_wa`,
    ADD COLUMN IF NOT EXISTS `wa_verified_by` BIGINT UNSIGNED NULL AFTER `wa_verified_at`,
    ADD CONSTRAINT `fk_users_wa_verified_by`
        FOREIGN KEY (`wa_verified_by`) REFERENCES `users` (`id`)
        ON DELETE SET NULL;

-- 2. Pastikan akun existing tidak terkunci: isi wa_verified_at = NOW() jika masih NULL
UPDATE `users`
SET `wa_verified_at` = NOW()
WHERE `wa_verified_at` IS NULL;

-- 3. Buat tabel otp_codes untuk menyimpan hash kode verifikasi dan percobaan
CREATE TABLE IF NOT EXISTS `otp_codes` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `code` VARCHAR(255) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    CONSTRAINT `fk_otp_codes_user_id`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

