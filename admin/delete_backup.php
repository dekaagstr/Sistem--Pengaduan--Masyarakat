<?php
session_start();
require_once __DIR__ . '/../config/helpers.php';
requireLogin();

// Hanya admin yang bisa mengakses
if (!isAdmin()) {
    $_SESSION['error'] = 'Akses ditolak';
    header('Location: ../dashboard/');
    exit();
}

if (!isset($_GET['file']) || empty($_GET['file'])) {
    $_SESSION['error'] = 'File backup tidak ditemukan';
    header('Location: backup_database.php');
    exit();
}

$file = '../backups/' . basename($_GET['file']);

// Pastikan file ada dan aman untuk dihapus
if (file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
    if (unlink($file)) {
        $_SESSION['success'] = 'Backup berhasil dihapus: ' . basename($file);
    } else {
        $_SESSION['error'] = 'Gagal menghapus file backup';
    }
} else {
    $_SESSION['error'] = 'File backup tidak valid';
}

header('Location: backup_database.php');
exit();
