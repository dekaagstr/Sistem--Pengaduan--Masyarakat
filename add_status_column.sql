-- Menambahkan kolom status ke tabel users jika belum ada
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active';

-- Update semua user yang sudah ada untuk memiliki status aktif
UPDATE users SET status = 'active' WHERE status IS NULL;
