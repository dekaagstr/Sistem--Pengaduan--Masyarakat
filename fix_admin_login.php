<?php
// Script untuk memperbaiki login admin
require_once 'config/database.php';

// Hash untuk password 'admin123'
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Update password admin
    $query = "UPDATE users SET password = :password WHERE username = 'admin'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':password', $hashed_password);
    
    if ($stmt->execute()) {
        echo "<h2>Password Admin Berhasil Diperbarui</h2>";
        echo "<p>Username: <strong>admin</strong></p>";
        echo "<p>Password baru: <strong>" . htmlspecialchars($new_password) . "</strong></p>";
        echo "<p>Hash password baru: " . htmlspecialchars($hashed_password) . "</p>";
        echo "<p><a href='auth/login.php'>Kembali ke halaman login</a></p>";
        
        // Verifikasi hash
        echo "<hr>";
        echo "<h3>Verifikasi Hash</h3>";
        echo "Verifikasi password 'admin123' dengan hash: " . 
             (password_verify('admin123', $hashed_password) ? "<span style='color:green'>SUKSES</span>" : "<span style='color:red'>GAGAL</span>");
    } else {
        echo "<p style='color:red'>Gagal memperbarui password admin.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
