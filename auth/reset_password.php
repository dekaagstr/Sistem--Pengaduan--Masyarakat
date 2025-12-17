<?php
require_once '../config/helpers.php';
require_once '../config/database.php';

$error = '';
$success = '';
$validToken = false;
$token = $_GET['token'] ?? '';

// Validasi token
if (!empty($token)) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id FROM masyarakat WHERE reset_token = :token AND reset_token_expires > NOW()";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $userId = $user['id'];
        $validToken = true;
        
        // Proses reset password jika form disubmit
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($password) || empty($confirm_password)) {
                $error = 'Password dan konfirmasi password tidak boleh kosong';
            } elseif ($password !== $confirm_password) {
                $error = 'Password dan konfirmasi password tidak cocok';
            } else {
                // Update password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $query = "UPDATE masyarakat SET password = :password, reset_token = NULL, reset_token_expires = NULL WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':id', $userId);
                
                if ($stmt->execute()) {
                    $success = 'Password berhasil direset. Silakan login dengan password baru Anda.';
                    $validToken = false; // Sembunyikan form setelah berhasil reset
                } else {
                    $error = 'Terjadi kesalahan. Silakan coba lagi.';
                }
            }
        }
    } else {
        $error = 'Link reset password tidak valid atau sudah kadaluarsa.';
    }
} else {
    $error = 'Token tidak valid.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Sistem Pengaduan Masyarakat</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h2 class="login-title">Reset Password</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-primary">Kembali ke Login</a>
                </div>
            <?php endif; ?>
            
            <?php if ($validToken): ?>
            <p>Silakan masukkan password baru Anda.</p>
            
            <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                <div class="form-group">
                    <label class="form-label" for="password">Password Baru</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Konfirmasi Password Baru</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Reset Password</button>
            </form>
            <?php endif; ?>
            
            <?php if (!$validToken && empty($success)): ?>
                <div class="text-center mt-3">
                    <a href="login.php" class="text-muted">Kembali ke halaman login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
