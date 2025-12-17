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

// Pastikan file ada dan aman untuk diunduh
if (file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
} else {
    $_SESSION['error'] = 'File backup tidak valid';
    header('Location: backup_database.php');
    exit();
}
