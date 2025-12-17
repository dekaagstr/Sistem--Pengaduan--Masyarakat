<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }
}


function redirectIfLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        header("Location: ../dashboard/index.php");
        exit();
    }
}


function isAdmin() {
    return isset($_SESSION['level']) && $_SESSION['level'] === 'admin';
}

function formatTanggal($date) {
    if (empty($date) || $date == '0000-00-00') return '-';
    
    try {
        $bulan = array(
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        );
        $timestamp = strtotime($date);
        if ($timestamp === false) return '-';
        
        $tanggal = date('j', $timestamp);
        $bulan_num = date('n', $timestamp);
        $tahun = date('Y', $timestamp);
        
        return $tanggal . ' ' . $bulan[$bulan_num] . ' ' . $tahun;
    } catch (Exception $e) {
        return '-';
    }
}


function getStatusBadge($status) {
    $badges = [
        'menunggu' => '<span class="status-badge menunggu">Menunggu</span>',
        'diproses' => '<span class="status-badge diproses">Diproses</span>',
        'selesai' => '<span class="status-badge selesai">Selesai</span>',
        'ditolak' => '<span class="status-badge ditolak">Ditolak</span>'
    ];
    return $badges[$status] ?? '<span class="status-badge">Unknown</span>';
}


function uploadFile($file, $target_dir) {
    if (!isset($file['name']) || empty($file['name'])) {
        return ["success" => false, "message" => "File tidak dipilih."];
    }

    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $filename = uniqid() . '_' . basename($file["name"]);
    $target_file = $target_dir . $filename;
    

    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return ["success" => false, "message" => "File bukan gambar."];
    }

    if ($file["size"] > 2000000) {
        return ["success" => false, "message" => "Ukuran file terlalu besar."];
    }

  
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        return ["success" => false, "message" => "Hanya file JPG, JPEG, PNG & GIF yang diizinkan."];
    }

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["success" => true, "filename" => $filename];
    } else {
        return ["success" => false, "message" => "Terjadi kesalahan saat upload file."];
    }
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}


function validateNIK($nik) {
    return preg_match('/^[0-9]{16}$/', $nik);
}


function validateTelepon($telepon) {
    return preg_match('/^[0-9]{10,13}$/', $telepon);
}


function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)));
}


function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>