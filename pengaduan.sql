CREATE DATABASE pengaduan_masyarakat;
USE pengaduan_masyarakat;

-- Tabel untuk users/admin
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    level ENUM('admin', 'petugas') DEFAULT 'petugas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel untuk kategori pengaduan
CREATE TABLE kategori (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel untuk pengaduan
CREATE TABLE pengaduan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_kategori INT,
    nik VARCHAR(20) NOT NULL,
    nama_pelapor VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telepon VARCHAR(15),
    judul_pengaduan VARCHAR(200) NOT NULL,
    isi_pengaduan TEXT NOT NULL,
    lokasi_kejadian TEXT,
    tanggal_kejadian DATE,
    status ENUM('menunggu', 'diproses', 'selesai', 'ditolak') DEFAULT 'menunggu',
    foto VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id)
);

-- Tabel untuk tanggapan
CREATE TABLE tanggapan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_pengaduan INT,
    id_user INT,
    tanggapan TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pengaduan) REFERENCES pengaduan(id),
    FOREIGN KEY (id_user) REFERENCES users(id)
);

-- Insert data admin default
INSERT INTO users (username, password, nama_lengkap, level) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

-- Insert beberapa kategori
INSERT INTO kategori (nama_kategori, deskripsi) VALUES 
('Infrastruktur', 'Pengaduan terkait jalan, jembatan, drainase, dll'),
('Lingkungan', 'Pengaduan terkait kebersihan, sampah, pencemaran'),
('Pelayanan Publik', 'Pengaduan terkait pelayanan pemerintah'),
('Kesehatan', 'Pengaduan terkait fasilitas dan pelayanan kesehatan'),
('Pendidikan', 'Pengaduan terkait fasilitas dan pelayanan pendidikan');