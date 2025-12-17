<?php
require_once '../config/helpers.php';
require_once '../config/database.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: ' . (isAdmin() ? '../admin/dashboard.php' : '../dashboard'));
    exit();
}

$database = new Database();
$db = $database->getConnection();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $nik = trim($_POST['nik'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validasi
    $errors = [];
    
    if (empty($nama_lengkap)) {
        $errors[] = 'Nama lengkap tidak boleh kosong';
    }
    
    if (empty($username)) {
        $errors[] = 'Username tidak boleh kosong';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username hanya boleh berisi huruf, angka, dan underscore (_)';
    }
    
    if (empty($email)) {
        $errors[] = 'Email tidak boleh kosong';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid';
    }
    
    if (empty($telepon)) {
        $errors[] = 'Nomor telepon harus diisi';
    } elseif (!preg_match('/^[0-9]{10,13}$/', $telepon)) {
        $errors[] = 'Format nomor telepon tidak valid (10-13 digit)';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'Konfirmasi password tidak cocok';
    }
    
    // Cek username sudah terdaftar
    $query = "SELECT id FROM users WHERE username = :username LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $errors[] = 'Username sudah digunakan, silakan pilih username lain';
    }
    
    // Cek username atau email sudah terdaftar
    if (empty($errors)) {
        $query = "SELECT id FROM users WHERE email = :email LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $errors[] = 'Email sudah terdaftar';
        }
    }
    
    // Jika validasi berhasil, simpan ke database
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Set default level to 'warga' for new registrations
        $level = 'warga';
        
        // Using only the fields that exist in the users table
        $query = "INSERT INTO users (username, password, nama_lengkap, level) 
                 VALUES (:username, :password, :nama_lengkap, :level)";
        
        try {
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':nama_lengkap', $nama_lengkap);
            $stmt->bindParam(':level', $level);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Pendaftaran berhasil! Silakan login dengan akun Anda.';
                header('Location: login.php');
                exit();
            } else {
                $errors[] = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Sistem Pengaduan Masyarakat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        body {
            background: #f5f7ff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 2.5rem;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-header h1 {
            color: #4361ee;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .register-header p {
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #495057;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus {
            border-color: #4361ee;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            width: 100%;
        }
        
        .btn-primary {
            color: white;
            background-color: #4361ee;
            border-color: #4361ee;
        }
        
        .btn-primary:hover {
            background-color: #3a56d4;
            border-color: #3a56d4;
        }
        
        .btn-outline-primary {
            color: #4361ee;
            background-color: transparent;
            border: 1px solid #4361ee;
        }
        
        .btn-outline-primary:hover {
            background-color: #f8f9fa;
        }
        
        .alert {
            padding: 0.75rem 1.25rem;
            margin-bottom: 1.5rem;
            border: 1px solid transparent;
            border-radius: 5px;
            font-size: 0.95rem;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-3 {
            margin-top: 1.5rem;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle input[type="password"],
        .password-toggle input[type="text"] {
            padding-right: 40px;
        }
        
        .password-toggle .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            cursor: pointer;
        }
        
        .password-requirements {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <img src="../assets/uploads/logo.png" alt="logo" style="height: 80px; margin-bottom: 1rem;">
            <h1>Daftar Akun Baru</h1>
            <p>Silakan isi form berikut untuk membuat akun baru</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin-bottom: 0; padding-left: 1.2rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap <span style="color: #dc3545;">*</span></label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                       value="<?php echo htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="username">Username <span style="color: #dc3545;">*</span></label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                <small class="form-text text-muted">Gunakan username yang unik dan mudah diingat</small>
                </div>
            
            <div class="form-group">
                <label for="nik">NIK <span style="color: #dc3545;">*</span></label>
                <input type="text" class="form-control" id="nik" name="nik" 
                       value="<?php echo htmlspecialchars($_POST['nik'] ?? ''); ?>" 
                       pattern="\d{16}" title="NIK harus 16 digit angka" required>
                <small class="text-muted">Masukkan 16 digit NIK Anda</small>
            </div>
            
            <div class="form-group">
                <label for="email">Email <span style="color: #dc3545;">*</span></label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="telepon">Nomor Telepon <span style="color: #dc3545;">*</span></label>
                <input type="tel" class="form-control" id="telepon" name="telepon" 
                       value="<?php echo htmlspecialchars($_POST['telepon'] ?? ''); ?>" 
                       pattern="[0-9]{10,13}" title="Masukkan 10-13 digit nomor telepon" required>
                <small class="text-muted">Contoh: 081234567890</small>
            </div>
            
            <div class="form-group">
                <label for="alamat">Alamat</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="2"><?php echo htmlspecialchars($_POST['alamat'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group password-toggle">
                <label for="password">Password <span style="color: #dc3545;">*</span></label>
                <input type="password" class="form-control" id="password" name="password" required>
                <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('password')"></i>
                <div class="password-requirements">Minimal 6 karakter</div>
            </div>
            
            <div class="form-group password-toggle">
                <label for="password_confirm">Konfirmasi Password <span style="color: #dc3545;">*</span></label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('password_confirm')"></i>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary">Daftar Sekarang</button>
            </div>
            
            <div class="text-center mt-3">
                <p>Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
            </div>
        </form>
    </div>
    
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling;
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }
        
        // Validasi form client-side
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('password_confirm').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Konfirmasi password tidak cocok');
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter');
            }
        });
    </script>
</body>
</html>
