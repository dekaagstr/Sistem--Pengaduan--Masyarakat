<?php
require_once '../config/helpers.php';
redirectIfLoggedIn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Pengaduan Masyarakat</title>
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
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #4361ee;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #6c757d;
            font-size: 0.95rem;
            margin: 0.5rem 0;
        }
        
        .app-description {
            font-size: 0.9rem;
            line-height: 1.6;
            color: #6c757d;
            margin: 1rem 0 0;
            padding: 1rem;
            background: #f8f9ff;
            border-radius: 8px;
            border-left: 3px solid #4361ee;
        }
        
        .divider {
            height: 1px;
            background: #e0e0e0;
            margin: 1.5rem 0;
        }
        
        .login-title {
            text-align: center;
            color: var(--primary);
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            border-color: #4361ee;
            outline: none;
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #4361ee;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn:hover {
            background: #3a56d4;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-3 {
            margin-top: 1rem;
        }
        
        .text-muted {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .btn-link {
            color: #4361ee;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        
        .btn-link:hover {
            color: #3a56d4;
            text-decoration: underline;
        }
        
        .form-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.9rem;
            color: #6c757d;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin: 1.5rem 0;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            color: #5a5a5a;
        }
        
        .feature-item i {
            color: #4361ee;
            margin-right: 8px;
            font-size: 1rem;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
            
            .login-header p {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../assets/uploads/logo.png" alt="logo" style="height: 80px; margin-bottom: 1rem;">
            <div style="text-align: center; width: 100%;">
                <h1>SIPEMAS</h1>
                <p>Sistem Informasi Pengaduan Masyarakat</p>
            </div>
            <div class="divider"></div>
            <p class="app-description">
                Media pengaduan resmi untuk menyampaikan keluhan dan masukan dari masyarakat.
                Layanan cepat, transparan, dan terpercaya untuk kenyamanan bersama.
            </p>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        
        <form action="proses_login.php" method="POST" style="margin-top: 2rem;">
            <div class="form-group">
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
            </div>
            
            <div class="form-group">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
            </div>
            
            <div class="form-group" style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </button>
                <div class="text-center mt-3">
                    <p>Belum punya akun? <a href="register.php">Buat akun baru</a></p>
                </div>
            </div>
            
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Respon Cepat</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Proses Transparan</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Layanan 24/7</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Terjamin Rahasia</span>
                </div>
            </div>
            
            <div class="form-footer">
                <p style="margin-bottom: 0.5rem;">
                    <i class="fas fa-phone-alt" style="margin-right: 5px; color: #4361ee;"></i>
                    <a href="tel:+628123456789" style="color: #4361ee; text-decoration: none;">+62 816-6666-7890</a>
                </p>
                <p style="margin: 0;">
                    <i class="fas fa-envelope" style="margin-right: 5px; color: #4361ee;"></i>
                    <a href="mailto:admin@sipemas.desa.id" style="color: #4361ee; text-decoration: none;">admin@sipemas.desa.id</a>
                </p>
                
            </div>
        </form>
    </div>
</body>
</html>