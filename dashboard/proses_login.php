<?php
require_once '../config/helpers.php';
require_once '../config/database.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to log debug information
function debug_log($message) {
    $log_file = __DIR__ . '/login_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    debug_log("Login attempt for username: $username");

    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check in users table
        $query = "SELECT * FROM users WHERE username = :username LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug user data (without password for security)
            $userDebug = $user;
            unset($userDebug['password']);
            debug_log("User found: " . json_encode($userDebug));
            
            // Check if account is active
            debug_log("User status: " . ($user['status'] ?? 'not set'));
            if (strtolower($user['status'] ?? '') !== 'active') {
                throw new Exception("Akun Anda tidak aktif. Silakan hubungi administrator.");
            }
            
            // Verify password
            debug_log("Verifying password...");
            debug_log("Stored hash: " . $user['password']);
            
            if (password_verify($password, $user['password'])) {
                debug_log("Password verification successful");
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['level'] = $user['level'];
                $_SESSION['is_admin'] = ($user['level'] === 'admin');
                
                // Update last login
                try {
                    $update = $db->prepare("UPDATE users SET terakhir_login = NOW() WHERE id = :id");
                    $update->bindParam(':id', $user['id']);
                    $update->execute();
                    debug_log("Last login updated");
                } catch (Exception $e) {
                    debug_log("Failed to update last login: " . $e->getMessage());
                    // Continue even if this fails
                }
                
                // Redirect based on user level
                $redirect = $_SESSION['is_admin'] ? '../admin/dashboard.php' : '../dashboard/index.php';
                debug_log("Redirecting to: $redirect");
                
                header("Location: $redirect");
                exit();
            } else {
                debug_log("Password verification failed");
                // For debugging: Log the password that was entered (only in development!)
                debug_log("Entered password (first 2 chars): " . substr($password, 0, 2));
                throw new Exception("Username atau password salah!");
            }
        } else {
            debug_log("User not found: $username");
            throw new Exception("Username atau password salah!");
        }
    } catch (PDOException $e) {
        debug_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
        header("Location: login.php");
        exit();
    } catch (Exception $e) {
        debug_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>