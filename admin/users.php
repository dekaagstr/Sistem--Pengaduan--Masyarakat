<?php
require_once '../config/helpers.php';
require_once '../config/database.php';

// Cek apakah user sudah login dan merupakan admin
if (!isset($_SESSION['user_id']) || $_SESSION['level'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Koneksi database
$database = new Database();
$db = $database->getConnection();

// Proses update status user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $new_status = $_POST['status'];
    
    // Pastikan status yang valid
    $new_status = ($new_status === 'active') ? 'active' : 'inactive';
    
    // Debug: Tampilkan nilai yang akan diupdate
    error_log("Updating user ID: $id to status: $new_status");
    
    $query = "UPDATE users SET status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':status', $new_status);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Status user berhasil diupdate!";
        // Debug: Tampilkan pesan sukses
        error_log("User ID $id status updated to: $new_status");
    } else {
        $error = $stmt->errorInfo();
        $_SESSION['error'] = "Gagal mengupdate status user! " . $error[2];
        // Debug: Tampilkan error
        error_log("Error updating user status: " . print_r($error, true));
    }
    
    // Kembali ke halaman sebelumnya dengan parameter yang sama
    $query_string = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
    header("Location: " . $_SERVER['PHP_SELF'] . $query_string);
    exit();
}

// Proses hapus user
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Cek apakah user yang akan dihapus adalah admin
    $query = "SELECT level FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['level'] === 'admin') {
        $_SESSION['error'] = "Tidak dapat menghapus akun admin!";
    } else {
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "User berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Gagal menghapus user!";
        }
    }
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Inisialisasi status default jika kolom status belum ada
try {
    // Cek apakah kolom status sudah ada
    $checkStatusColumn = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
    if ($checkStatusColumn->rowCount() == 0) {
        // Tambahkan kolom status jika belum ada
        $db->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
        
        // Set semua user yang sudah ada menjadi active
        $db->exec("UPDATE users SET status = 'active' WHERE status IS NULL");
    }
} catch (PDOException $e) {
    // Tangani error jika terjadi
    error_log("Error initializing user status: " . $e->getMessage());
}

// Ambil data user dengan pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Hitung total data
$query = "SELECT COUNT(*) as total FROM users";
$stmt = $db->query($query);
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_users / $per_page);

// Cek apakah kolom created_at ada
try {
    $checkCreatedAt = $db->query("SHOW COLUMNS FROM users LIKE 'created_at'");
    $hasCreatedAt = ($checkCreatedAt->rowCount() > 0);
    
    if ($hasCreatedAt) {
        // Jika kolom created_at ada
        $query = "SELECT id, username, nama_lengkap, level, status, 
                 COALESCE(created_at, NOW()) as created_at 
                 FROM users 
                 ORDER BY created_at DESC 
                 LIMIT :limit OFFSET :offset";
    } else {
        // Jika kolom created_at tidak ada
        $query = "SELECT id, username, nama_lengkap, level, status, 
                 NOW() as created_at 
                 FROM users 
                 ORDER BY id DESC 
                 LIMIT :limit OFFSET :offset";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pastikan setiap user memiliki created_at
    foreach ($users as &$user) {
        if (empty($user['created_at'])) {
            $user['created_at'] = date('Y-m-d H:i:s');
        }
    }
    unset($user); // Hapus referensi terakhir
    
} catch (PDOException $e) {
    // Jika terjadi error, gunakan query yang lebih sederhana
    error_log("Error fetching users: " . $e->getMessage());
    $query = "SELECT id, username, nama_lengkap, level, status, 
             NOW() as created_at 
             FROM users 
             ORDER BY id DESC 
             LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #e6e9ff;
            --secondary: #3f37c9;
            --success: #4bb543;
            --danger: #f44336;
            --warning: #ff9800;
            --info: #2196f3;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #333;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--dark);
        }
        
        .card-body {
            padding: 20px;
            overflow-x: auto;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.875rem;
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #d32f2f;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        
        .table th,
        .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark);
            position: sticky;
            top: 0;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .badge-success {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .badge-warning {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .badge-danger {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .badge-info {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .text-center {
            text-align: center;
        }
        
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        
        .alert-danger {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .action-buttons a {
            color: var(--gray);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .action-buttons a:hover {
            color: var(--primary);
        }
        
        /* Toggle Switch Style */
        .status-toggle {
            position: relative;
            display: inline-flex;
            align-items: center;
            width: 80px;
            height: 28px;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            outline: none;
            padding: 0 5px;
            transition: all 0.3s ease;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            overflow: hidden;
        }
        
        .status-toggle.active {
            background-color: #4CAF50;
            color: white;
            justify-content: flex-end;
        }
        
        .status-toggle.inactive {
            background-color: #e0e0e0;
            color: #757575;
            justify-content: flex-start;
        }
        
        .toggle-handle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: white;
            margin: 0 3px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .active .toggle-handle {
            transform: translateX(0);
        }
        
        .inactive .toggle-handle {
            transform: translateX(0);
        }
        
        .toggle-text {
            margin: 0 8px;
            white-space: nowrap;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-toggle:hover {
            opacity: 0.9;
        }
        
        .action-buttons a:hover {
            color: var(--primary);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: var(--primary);
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .pagination .active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .pagination .disabled {
            color: #ccc;
            pointer-events: none;
            border-color: #eee;
        }
        
        .search-box {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .search-box input[type="text"] {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .search-box button {
            padding: 8px 16px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .search-box button:hover {
            background-color: var(--secondary);
        }
        
        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .search-box {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-users"></i> Daftar Pengguna</h2>
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Cari pengguna...">
                    <button type="button" id="searchBtn"><i class="fas fa-search"></i> Cari</button>
                </div>
                
                <?php if (count($users) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Level</th>
                                    <th>Status</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $no + $offset; ?></td>
                                        <td><?php echo htmlspecialchars($user['nama_lengkap'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td>
                                            <?php 
                                            $level = $user['level'] ?? 'warga';
                                            $level_class = $level === 'admin' ? 'badge-danger' : ($level === 'petugas' ? 'badge-info' : 'badge-success');
                                            ?>
                                            <span class="badge <?php echo $level_class; ?>">
                                                <?php echo ucfirst($level); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $status = $user['status'] ?? 'active';
                                            $status_class = $status === 'active' ? 'badge-success' : 'badge-warning';
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></td>
                                        <td class="action-buttons">
                                            <?php if ($user['level'] !== 'admin'): ?>
                                                <?php 
                                                    $current_status = strtolower(trim($user['status'] ?? 'active'));
                                                    $new_status = ($current_status === 'active') ? 'inactive' : 'active';
                                                    $status_text = ($current_status === 'active') ? 'Nonaktif' : 'Aktif';
                                                    $title_text = ($current_status === 'active') ? 'Nonaktifkan Akun' : 'Aktifkan Akun';
                                                    $icon_class = ($current_status === 'active') ? 'fa-check' : 'fa-times';
                                                ?>
                                                <form method="POST" action="" style="display: inline-block;" onsubmit="return confirm('Apakah Anda yakin ingin <?php echo strtolower($title_text); ?>?')">
                                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="status" value="<?php echo $new_status; ?>">
                                                    <button type="submit" name="update_status" class="status-toggle <?php echo $current_status === 'active' ? 'active' : 'inactive'; ?>" 
                                                            title="<?php echo $title_text; ?>">
                                                        <span class="toggle-handle">
                                                            <i class="fas <?php echo $icon_class; ?>"></i>
                                                        </span>
                                                        <span class="toggle-text">
                                                            <?php echo $status_text; ?>
                                                        </span>
                                                    </button>
                                                </form>
                                                <a href="#" onclick="hapusUser(<?php echo $user['id']; ?>)" title="Hapus">
                                                    <i class="fas fa-trash-alt text-danger"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Tidak tersedia</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php $no++; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo ($page - 1); ?>">&laquo; Sebelumnya</a>
                            <?php else: ?>
                                <span class="disabled">&laquo; Sebelumnya</span>
                            <?php endif; ?>
                            
                            <?php
                            $start = max(1, $page - 2);
                            $end = min($start + 4, $total_pages);
                            
                            if ($start > 1) {
                                echo '<a href="?page=1">1</a>';
                                if ($start > 2) {
                                    echo '<span>...</span>';
                                }
                            }
                            
                            for ($i = $start; $i <= $end; $i++) {
                                if ($i == $page) {
                                    echo '<span class="active">' . $i . '</span>';
                                } else {
                                    echo '<a href="?page=' . $i . '">' . $i . '</a>';
                                }
                            }
                            
                            if ($end < $total_pages) {
                                if ($end < $total_pages - 1) {
                                    echo '<span>...</span>';
                                }
                                echo '<a href="?page=' . $total_pages . '">' . $total_pages . '</a>';
                            }
                            ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo ($page + 1); ?>">Selanjutnya &raquo;</a>
                            <?php else: ?>
                                <span class="disabled">Selanjutnya &raquo;</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="text-center" style="padding: 40px 0;">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                        <p>Tidak ada data pengguna</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Fungsi untuk konfirmasi hapus user
        function hapusUser(id) {
            if (confirm('Apakah Anda yakin ingin menghapus user ini? Tindakan ini tidak dapat dibatalkan!')) {
                window.location.href = '?hapus=' + id;
            }
        }
        
        // Fungsi untuk pencarian
        document.getElementById('searchBtn').addEventListener('click', function() {
            const searchTerm = document.getElementById('searchInput').value.trim();
            if (searchTerm) {
                window.location.href = '?search=' + encodeURIComponent(searchTerm);
            }
        });
        
        // Tambahkan event listener untuk tombol enter pada input search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('searchBtn').click();
            }
        });
    </script>
</body>
</html>
