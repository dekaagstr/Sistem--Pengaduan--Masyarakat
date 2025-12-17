<?php
// Script untuk mereset password semua user
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Daftar user dan password baru
    $users = [
        ['username' => 'admin', 'password' => 'admin123'],
        ['username' => 'petugas', 'password' => 'petugas123'],
        ['username' => 'warga', 'password' => 'warga123']
    ];
    
    echo "<h2>Reset Password User</h2>";
    
    foreach ($users as $user) {
        $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
        
        // Update password
        $query = "UPDATE users SET password = :password WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':username', $user['username']);
        
        if ($stmt->execute()) {
            echo "<div style='margin-bottom: 15px; padding: 10px; background: #dff0d8; border: 1px solid #d6e9c6; border-radius: 4px;'>";
            echo "<strong>" . htmlspecialchars($user['username']) . "</strong> - Password berhasil direset<br>";
            echo "Username: " . htmlspecialchars($user['username']) . "<br>";
            echo "Password baru: " . htmlspecialchars($user['password']) . "<br>";
            echo "Status verifikasi: ";
            echo password_verify($user['password'], $hashed_password) ? 
                 "<span style='color:green'>SUKSES</span>" : 
                 "<span style='color:red'>GAGAL</span>";
            echo "</div>";
        } else {
            echo "<div style='color:red; margin-bottom: 10px;'>Gagal mereset password untuk " . 
                 htmlspecialchars($user['username']) . "</div>";
        }
    }
    
    echo "<hr>";
    echo "<p><a href='auth/login.php' style='display: inline-block; margin-top: 15px; padding: 8px 15px; background: #337ab7; color: white; text-decoration: none; border-radius: 4px;'>Kembali ke Halaman Login</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color:red; padding: 10px; background: #f2dede; border: 1px solid #ebccd1; border-radius: 4px;'>";
    echo "Error: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
