<?php
session_start();
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';
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

// Pastikan file ada dan aman untuk dipulihkan
if (!file_exists($file) || pathinfo($file, PATHINFO_EXTENSION) !== 'sql') {
    $_SESSION['error'] = 'File backup tidak valid';
    header('Location: backup_database.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Matikan pemeriksaan foreign key sementara
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // Baca isi file SQL
    $sql = file_get_contents($file);
    
    // Eksekusi query SQL
    $db->exec($sql);
    
    // Hidupkan kembali pemeriksaan foreign key
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    $_SESSION['success'] = 'Database berhasil dipulihkan dari backup: ' . basename($file);
} catch (PDOException $e) {
    $_SESSION['error'] = 'Gagal memulihkan database: ' . $e->getMessage();
}

header('Location: backup_database.php');
exit();
