DROP TABLE IF EXISTS kategori;

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO kategori VALUES("1","Infrastruktur","Pengaduan terkait jalan, jembatan, drainase, dll","2025-10-27 13:56:31");
INSERT INTO kategori VALUES("2","Lingkungan","Pengaduan terkait kebersihan, sampah, pencemaran","2025-10-27 13:56:31");
INSERT INTO kategori VALUES("3","Pelayanan Publik","Pengaduan terkait pelayanan pemerintah","2025-10-27 13:56:31");
INSERT INTO kategori VALUES("4","Kesehatan","Pengaduan terkait fasilitas dan pelayanan kesehatan","2025-10-27 13:56:31");
INSERT INTO kategori VALUES("5","Pendidikan","Pengaduan terkait fasilitas dan pelayanan pendidikan","2025-10-27 13:56:31");



DROP TABLE IF EXISTS pengaduan;

CREATE TABLE `pengaduan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_kategori` int(11) DEFAULT NULL,
  `nik` varchar(20) NOT NULL,
  `nama_pelapor` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telepon` varchar(15) DEFAULT NULL,
  `judul_pengaduan` varchar(200) NOT NULL,
  `isi_pengaduan` text NOT NULL,
  `lokasi_kejadian` text DEFAULT NULL,
  `tanggal_kejadian` date DEFAULT NULL,
  `status` enum('menunggu','diproses','selesai','ditolak') DEFAULT 'menunggu',
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_kategori` (`id_kategori`),
  CONSTRAINT `pengaduan_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS tanggapan;

CREATE TABLE `tanggapan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_pengaduan` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `tanggapan` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_pengaduan` (`id_pengaduan`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `tanggapan_ibfk_1` FOREIGN KEY (`id_pengaduan`) REFERENCES `pengaduan` (`id`),
  CONSTRAINT `tanggapan_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




DROP TABLE IF EXISTS users;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `level` enum('admin','petugas','warga') NOT NULL DEFAULT 'warga',
  `foto` varchar(255) DEFAULT 'default.png',
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `terakhir_login` datetime DEFAULT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO users VALUES("1","admin","$2y$10$51Ewn4xYEmmv5aQP.nR2ceIwUL9qcuVc5VYOTpvCzV.1x1NB6/t7y","Administrator","admin@example.com","081234567890","","admin","default.png","aktif","2025-12-03 12:19:34","2025-12-03 11:31:05","2025-12-03 12:19:34");
INSERT INTO users VALUES("2","petugas","$2y$10$htMVT7kVlPvCDERlPRfy6OnfV6Lt7jHS4YDy7tDadRPFJYHfmaVlq","Petugas 1","petugas@example.com","081234567891","","petugas","default.png","aktif","2025-12-03 11:46:19","2025-12-03 11:31:05","2025-12-03 11:46:19");
INSERT INTO users VALUES("3","warga","$2y$10$rFrzgfuVmXtVfS09V8cUfOX2UrOVn7ZM8maBt0mhc1SiGnWp1MuAS","Warga 1","warga@example.com","081234567892","Jl. Contoh No. 123","warga","default.png","aktif","2025-12-03 12:09:52","2025-12-03 11:31:05","2025-12-03 12:09:52");



