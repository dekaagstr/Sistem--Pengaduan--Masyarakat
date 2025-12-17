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

// Ambil data statistik
try {
    // Total pengaduan
    $query = "SELECT COUNT(*) as total FROM pengaduan";
    $stmt = $db->query($query);
    $total_pengaduan = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pengaduan baru (hari ini)
    $query = "SELECT COUNT(*) as total FROM pengaduan WHERE DATE(created_at) = CURDATE()";
    $stmt = $db->query($query);
    $pengaduan_baru = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pengaduan selesai
    $query = "SELECT COUNT(*) as total FROM pengaduan WHERE status = 'selesai'";
    $stmt = $db->query($query);
    $pengaduan_selesai = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total user
    $query = "SELECT COUNT(*) as total FROM users WHERE level = 'warga'";
    $stmt = $db->query($query);
    $total_warga = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Pengaduan terbaru
    $query = "SELECT p.*, k.nama_kategori, p.nama_pelapor 
              FROM pengaduan p 
              LEFT JOIN kategori k ON p.id_kategori = k.id 
              ORDER BY p.created_at DESC 
              LIMIT 5";
    $stmt = $db->query($query);
    $recent_pengaduan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Status pengaduan
    $query = "SELECT status, COUNT(*) as total 
              FROM pengaduan 
              GROUP BY status";
    $stmt = $db->query($query);
    $status_pengaduan = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $total_pengaduan = $pengaduan_baru = $pengaduan_selesai = $total_warga = 0;
    $recent_pengaduan = [];
    $status_pengaduan = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sistem Pengaduan Masyarakat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
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
            --pink: #f72585;
            --purple: #7209b7;
            --cyan: #4cc9f0;
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
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h2, .card-header h3 {
            margin: 0;
            color: var(--dark);
        }
        
        .card-body {
            padding: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-info {
            background-color: #e7f5ff;
            border-left: 4px solid var(--info);
            color: #0c5460;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            border-left: 4px solid var(--warning);
            color: #856404;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
            color: var(--dark);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: inherit;
        }
        
        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 10px 0;
            color: inherit;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: inherit;
            opacity: 0.9;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--gray);
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        .badge-info { background-color: #d1ecf1; color: #0c5460; }
        
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--secondary);
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: var(--gray);
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #e6e9ff;
            --secondary: #3f37c9;
            --success: #4bb543;
            --success-light: #e8f5e9;
            --danger: #f44336;
            --danger-light: #ffebee;
            --warning: #ff9800;
            --warning-light: #fff8e1;
            --info: #2196f3;
            --info-light: #e3f2fd;
            --light: #f8f9fa;
            --lighter: #f5f7fb;
            --dark: #212529;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --border-radius: 0.5rem;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background-color: var(--lighter);
            color: var(--dark);
            margin: 0;
            padding: 0;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 0.8rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--box-shadow);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            height: 70px;
            transition: var(--transition);
        }
        
        .admin-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .admin-container {
            display: flex;
            margin-top: 70px;
            min-height: calc(100vh - 70px);
        }
        
        .admin-sidebar {
            width: 250px;
            background-color: #fff;
            min-height: 100%;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            position: fixed;
            height: calc(100vh - 70px);
            overflow-y: auto;
            transition: all 0.3s;
        }
        
        .admin-sidebar h3 {
            color: var(--primary);
            padding: 1.5rem 1.5rem 1rem;
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            border-bottom: 1px solid #eee;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: #555;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            border-left-color: var(--primary);
        }
        
        .sidebar-menu li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .admin-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.75rem;
            color: var(--dark);
            margin: 0;
            font-weight: 600;
        }
        
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.03);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }
        
        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .card-body {
            padding: 1.5rem;
            flex: 1;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary);
            opacity: 0.1;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card.primary { 
            border-left-color: var(--primary);
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
        }
        .stat-card.success { 
            border-left-color: var(--success);
            background: linear-gradient(135deg, #f0fff4 0%, #e6fffa 100%);
        }
        .stat-card.info { 
            border-left-color: var(--info);
            background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 100%);
        }
        .stat-card.warning { 
            border-left-color: var(--warning);
            background: linear-gradient(135deg, #fffaf0 0%, #fff8e1 100%);
        }
        .stat-card.danger { 
            border-left-color: var(--danger);
            background: linear-gradient(135deg, #fff5f5 0%, #fff0f0 100%);
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: white;
            font-size: 1.75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.05) rotate(5deg);
        }
        
        .stat-icon.primary { background-color: var(--primary); }
        .stat-icon.success { background-color: var(--success); }
        .stat-icon.warning { background-color: var(--warning); }
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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
            color: #495057;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .btn {
            display: inline-block;
            padding: 6px 12px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.8rem;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        .alert {
            padding: 12px 15px;
            background-color: #e6f3ff;
            border-left: 4px solid #4dabf7;
            color: #0c63e4;
            margin: 15px 0;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .card-header .btn {
                margin-top: 10px;
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Dashboard Admin</h2>
                <div>
                    <a href="backup_database.php" class="btn btn-sm" style="margin-right: 10px; background-color: #4CAF50; color: white;">
                        <i class="fas fa-database"></i> Backup Database
                    </a>
                    <a href="laporan.php" class="btn btn-sm" style="margin-right: 10px;">
                        <i class="fas fa-file-pdf"></i> Cetak Laporan
                    </a>
                    <a href="kategori.php" class="btn btn-sm" style="margin-right: 10px;">
                        <i class="fas fa-tags"></i> Kategori
                    </a>
                    <a href="users.php" class="btn btn-sm" style="margin-right: 10px;">
                        <i class="fas fa-users"></i> Pengguna
                    </a>
                    <a href="../auth/logout.php" class="btn btn-sm" onclick="return confirm('Apakah Anda yakin ingin logout?')">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
                </a>
            </div>
            <div class="card-body">
                <p>Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin'); ?></strong></p>
                <div class="alert">
                    <strong>Anda login sebagai Administrator</strong> - Anda memiliki akses penuh untuk mengelola sistem pengaduan masyarakat.
                </div>

                <div class="stats-grid">
                    <div class="stat-card" style="background: linear-gradient(135deg, #4361ee, #3f37c9); color: white;">
                        <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                        <div class="stat-number"><?php echo number_format($total_pengaduan); ?></div>
                        <div class="stat-label">Total Pengaduan</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #4cc9f0, #4895ef); color: white;">
                        <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                        <div class="stat-number"><?php echo number_format($pengaduan_baru); ?></div>
                        <div class="stat-label">Pengaduan Hari Ini</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #4bb543, #2e7d32); color: white;">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-number"><?php echo number_format($pengaduan_selesai); ?></div>
                        <div class="stat-label">Pengaduan Selesai</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #f72585, #b5179e); color: white;">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-number"><?php echo number_format($total_warga); ?></div>
                        <div class="stat-label">Warga Terdaftar</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Pengaduan Terbaru</h3>
                        <a href="lihat_pengaduan.php" class="btn btn-sm">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_pengaduan) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Judul</th>
                                            <th>Pelapor</th>
                                            <th>Tanggal</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_pengaduan as $pengaduan): ?>
                                            <tr>
                                                <td>#<?php echo $pengaduan['id']; ?></td>
                                                <td><?php echo htmlspecialchars($pengaduan['judul_pengaduan'] ?? 'Tidak ada judul'); ?></td>
                                                <td><?php echo htmlspecialchars($pengaduan['nama_pelapor'] ?? 'Anonim'); ?></td>
                                                <td><?php echo date('d M Y', strtotime($pengaduan['created_at'])); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch (strtolower($pengaduan['status'])) {
                                                        case 'diproses':
                                                            $status_class = 'warning';
                                                            break;
                                                        case 'ditolak':
                                                            $status_class = 'danger';
                                                            break;
                                                        case 'selesai':
                                                            $status_class = 'success';
                                                            break;
                                                        default:
                                                            $status_class = 'info';
                                                    }
                                                    ?>
                                                    <span class="badge badge-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst($pengaduan['status'] ?? 'Baru'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="detail_pengaduan.php?id=<?php echo $pengaduan['id']; ?>" class="btn btn-sm">Detail</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center" style="padding: 20px; color: var(--gray);">
                                <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.5; margin-bottom: 1rem;"></i>
                                <p>Belum ada pengaduan terbaru</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
