<?php
require_once 'config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to check and fix admin status
function checkAndFixAdminStatus() {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Check if status column exists
        $checkColumn = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
        if ($checkColumn->rowCount() == 0) {
            // Add status column if it doesn't exist
            $db->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
            echo "Added 'status' column to users table.<br>";
        }
        
        // Get all admin users
        $query = "SELECT id, username, level, status FROM users WHERE level = 'admin'";
        $stmt = $db->query($query);
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Admin Accounts</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Level</th><th>Status</th><th>Action</th></tr>";
        
        foreach ($admins as $admin) {
            echo "<tr>";
            echo "<td>" . $admin['id'] . "</td>";
            echo "<td>" . htmlspecialchars($admin['username']) . "</td>";
            echo "<td>" . htmlspecialchars($admin['level']) . "</td>";
            echo "<td>" . ($admin['status'] ?? 'not set') . "</td>";
            
            // Add button to activate admin
            if (empty($admin['status']) || $admin['status'] !== 'active') {
                echo "<td><a href='?activate=" . $admin['id'] . "'>Activate Admin</a></td>";
            } else {
                echo "<td>Active</td>";
            }
            
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Handle activation
        if (isset($_GET['activate'])) {
            $adminId = (int)$_GET['activate'];
            $update = $db->prepare("UPDATE users SET status = 'active' WHERE id = ? AND level = 'admin'");
            if ($update->execute([$adminId])) {
                echo "<div style='color:green; margin:10px 0;'>Admin account activated successfully. <a href='check_admin_status.php'>Refresh page</a></div>";
            } else {
                echo "<div style='color:red; margin:10px 0;'>Failed to activate admin account.</div>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<div style='color:red; margin:10px 0;'>Error: " . $e->getMessage() . "</div>";
    }
}

// Run the check
checkAndFixAdminStatus();
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    a { color: #0066cc; text-decoration: none; }
    a:hover { text-decoration: underline; }
    table { border-collapse: collapse; margin: 20px 0; }
    th { background: #f0f0f0; padding: 8px; text-align: left; }
    td { padding: 8px; border: 1px solid #ddd; }
</style>

<p><a href="login.php">Kembali ke halaman login</a></p>
