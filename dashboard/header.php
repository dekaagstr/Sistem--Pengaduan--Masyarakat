<?php
// Pastikan helpers.php di-include dengan path yang benar
require_once '../config/helpers.php';
requireLogin();

// Debug: cek session data
error_log("Session data: " . print_r($_SESSION, true));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Pengaduan Masyarakat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">Sistem Pengaduan Layanan Masyarakat</div>
                <nav>
                    <ul class="nav-menu">
                        <li><a href="index.php">Dashboard</a></li>
                        <?php if (isAdmin()): ?>
                            <!-- Menu untuk Admin -->
                            <li><a href="pengaduan.php">Data Pengaduan</a></li>
                            <li><a href="kategori.php">Kategori</a></li>
                            <li><a href="users.php">Users</a></li>
                            <li><a href="laporan.php">Laporan</a></li>
                            <li><a href="backup.php" class="btn btn-outline-primary btn-sm ms-2"><i class="bi bi-database"></i> Backup Database</a></li>
                        <?php else: ?>
                            <!-- Menu untuk Warga -->
                            <li><a href="pengaduan.php">Pengaduan Saya</a></li>
                            <li><a href="../pengaduan_masyarakat.php">Buat Pengaduan Baru</a></li>
                        <?php endif; ?>
                        <li><a href="../auth/logout.php">Logout (<?php echo isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'User'; ?>)</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">