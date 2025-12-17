-- Menambahkan kolom untuk reset password
ALTER TABLE masyarakat
ADD COLUMN reset_token VARCHAR(64) NULL,
ADD COLUMN reset_token_expires DATETIME NULL,
ADD COLUMN email VARCHAR(100) NULL AFTER telp;

-- Buat index untuk pencarian yang lebih cepat
CREATE INDEX idx_reset_token ON masyarakat (reset_token);
