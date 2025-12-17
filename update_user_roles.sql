-- Update existing petugas users to warga
UPDATE users SET level = 'warga' WHERE level = 'petugas';

-- Modify the ENUM to only include 'admin' and 'warga'
ALTER TABLE users 
MODIFY COLUMN level ENUM('admin', 'warga') DEFAULT 'warga';
