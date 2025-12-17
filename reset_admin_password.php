<?php
// Hanya untuk development, jangan digunakan di production
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Hash untuk password 'admin123'
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Update password admin
    $query = "UPDATE users SET password = :password WHERE username = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':password', $hashed_password);
    
    if ($stmt->execute()) {
        echo "Password admin berhasil direset!<br>";
        echo "Username: admin<br>";
        echo "Password baru: admin123<br>";
        echo "Hash: " . $hashed_password . "<br>";
        echo "<a href='auth/login.php'>Kembali ke halaman login</a>";
    } else {
        echo "Gagal mereset password admin.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
