<?php
require_once '../config/helpers.php';
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $error = 'Email tidak boleh kosong';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        // Cek apakah email terdaftar
        $query = "SELECT id, username FROM masyarakat WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Generate token untuk reset password
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token berlaku 1 jam
            
            // Simpan token ke database
            $query = "UPDATE masyarakat SET reset_token = :token, reset_token_expires = :expires WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':expires', $expires);
            $stmt->bindParam(':id', $user['id']);
            
            if ($stmt->execute()) {
                // Kirim email dengan link reset password (dalam implementasi nyata)
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/pengaduan/auth/reset_password.php?token=" . $token;
                
                // Simpan pesan sukses
                $success = 'Instruksi untuk mereset password telah dikirim ke email Anda.';
                
                // Debug: Tampilkan link reset (dalam pengembangan)
                $success .= "<br><small>Link reset (hanya untuk pengembangan): <a href='$resetLink'>$resetLink</a></small>";
            } else {
                $error = 'Terjadi kesalahan. Silakan coba lagi.';
            }
        } else {
            // Jangan beri tahu user bahwa email tidak terdaftar (untuk keamanan)
            $success = 'Jika email terdaftar, instruksi reset password akan dikirim ke email Anda.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Sistem Pengaduan Masyarakat</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h2 class="login-title">Lupa Password</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (empty($success)): ?>
            <p>Masukkan alamat email yang terdaftar. Kami akan mengirimkan link untuk mereset password Anda.</p>
            
            <form action="forgot_password.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Kirim Link Reset Password</button>
            </form>
            <?php endif; ?>
            
            <div class="text-center mt-3">
                <a href="login.php" class="text-muted">Kembali ke halaman login</a>
            </div>
        </div>
    </div>
</body>
</html>
