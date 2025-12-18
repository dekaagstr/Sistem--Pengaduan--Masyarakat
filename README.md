# Pengaduan Layanan Masyrakat

## Fitur Utama

- Autentikasi pengguna (Admin dan Warga)
- Pengajuan pengaduan oleh masyarakat
- Unggah foto sebagai bukti pengaduan
- Pelacakan status pengaduan (Menunggu, Diproses, Selesai)
- Dashboard warga untuk memantau pengaduan
- Dashboard admin untuk mengelola pengaduan
- Manajemen kategori pengaduan
- Manajemen data pengguna
- Pencetakan laporan pengaduan
- Backup database sistem
## Teknologi yang Digunakan

- PHP (Native)
- MySQL
- HTML5
- CSS3
- JavaScript
- Bootstrap
- XAMPP (Apache & MySQL)
- Git & GitHub

  ## Struktur Proyek
  admin/  Halaman dan fitur admin
  assets/  CSS, JavaScript, dan gambar
  auth/  Autentikasi (login & registrasi)
  config/  Konfigurasi database
  dashboard/  Dashboard admin dan warga
  database/  File SQL
  backups/  Backup database
  index.php/  Halaman utama
pengaduan.sql/ Database utama

## Persiapan dan Instalasi

1. Install XAMPP dan Git
2. Clone repository:
   ```bash
   git clone https://github.com/dekaagstr/Sistem-Pengaduan-Masyarakat.git
3. Pindahkan Folder Proyek ke "C:/xampp/htdocs/"
4. Jalankan Apache dan MySQL melalui XAMPP
5. Buat database dengan nama pengaduan
6. Import file pengaduan.sql menggunakan phpMyAdmin
7. Sesuaikan konfigurasi database pada folder config

## Cara Penggunaan
Warga
Registrasi dan login akun
Mengajukan pengaduan baru
Melihat status dan riwayat pengaduan

Admin
Login sebagai admin
Mengelola pengaduan masyarakat
Mengubah status pengaduan
Mengelola data pengguna dan kategori
Mencetak laporan dan melakukan backup database

### Halaman Login
<img width="1100" height="1072" alt="image" src="https://github.com/user-attachments/assets/afea4189-b50d-49ac-9bef-5c7b6f8927ac" />

Halaman login SIPEMAS digunakan sebagai akses awal pengguna ke sistem pengaduan masyarakat. Pengguna dapat masuk menggunakan username dan password atau melakukan pendaftaran akun baru.

### Halaman Admin
<img width="1845" height="1058" alt="admin" src="https://github.com/user-attachments/assets/c90d34b1-f854-473e-bf84-c316130f1060" />

Dashboard Admin digunakan untuk mengelola sistem pengaduan masyarakat. Admin dapat melihat ringkasan data pengaduan, jumlah warga terdaftar, serta memantau dan menindaklanjuti pengaduan terbaru.

### Halaman User
<img width="1832" height="1037" alt="8d576548-b16d-4d02-8956-cbc2701669c0" src="https://github.com/user-attachments/assets/f6f6ee83-f1e2-4456-9ee2-6c0ba6c91f05" />

Dashboard Warga menampilkan ringkasan pengaduan milik pengguna. Warga dapat melihat status pengaduan, membuat pengaduan baru, serta memantau proses penyelesaian secara transparan.
