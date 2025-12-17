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

// Inisialisasi variabel
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah data per halaman
$offset = ($page - 1) * $limit;

// Query dasar untuk menghitung total data
$query_count = "SELECT COUNT(*) as total FROM pengaduan p 
                LEFT JOIN kategori k ON p.id_kategori = k.id 
                LEFT JOIN users u ON p.id = u.id 
                WHERE 1=1";

// Query untuk mengambil data
$query = "SELECT p.*, k.nama_kategori, u.nama_lengkap as nama_pelapor, 
          u.no_telp as telp_pelapor, u.alamat as alamat_pelapor
          FROM pengaduan p 
          LEFT JOIN kategori k ON p.id_kategori = k.id 
          LEFT JOIN users u ON p.id = u.id 
          WHERE 1=1";

$params = [];
$where = [];

// Filter pencarian
if (!empty($search)) {
    $where[] = "(p.judul_pengaduan LIKE :search OR p.isi_laporan LIKE :search OR u.nama_lengkap LIKE :search)";
    $params[':search'] = "%$search%";
}

// Filter status
if (!empty($status) && in_array($status, ['menunggu', 'diproses', 'selesai', 'ditolak'])) {
    $where[] = "p.status = :status";
    $params[':status'] = $status;
}

// Gabungkan kondisi WHERE
if (!empty($where)) {
    $where_clause = " AND " . implode(" AND ", $where);
    $query .= $where_clause;
    $query_count .= $where_clause;
}

// Hitung total data
$stmt_count = $db->prepare($query_count);
foreach ($params as $key => $value) {
    $stmt_count->bindValue($key, $value);
}
$stmt_count->execute();
$total_data = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_data / $limit);

// Tambahkan pengurutan dan limit
$query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";

// Eksekusi query
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$pengaduan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Pengaduan - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #e6e9ff;
            --secondary: #3f37c9;
            --success: #4caf50;
            --danger: #f44336;
            --warning: #ff9800;
            --info: #2196f3;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --white: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fb;
            color: #333;
            line-height: 1.6;
            padding: 0;
            margin: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            background: var(--white);
            border-radius: 8px;
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
            background: var(--primary);
            color: white;
        }
        
        .card-header h2 {
            font-size: 1.5rem;
            margin: 0;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
        }
        
        .btn-outline-primary {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
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
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        
        .table th,
        .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .text-truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
            display: inline-block;
        }
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .form-control {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.9rem;
            height: 38px;
        }
        
        .form-control:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .status-filter {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .status-btn {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            cursor: pointer;
            border: 1px solid #ddd;
            background: white;
            transition: all 0.3s ease;
        }
        
        .status-btn:hover,
        .status-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination .page-item {
            list-style: none;
        }
        
        .pagination .page-link {
            display: block;
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            color: var(--primary);
            text-decoration: none;
            border-radius: 4px;
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .pagination .page-link:hover {
            background-color: #e9ecef;
        }
        
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }
        
        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .action-btn {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }
        
        .action-btn i {
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .table {
                min-width: 900px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            .action-btn {
                justify-content: center;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Daftar Pengaduan</h2>
                <a href="../dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <!-- Filter Section -->
                <form method="get" action="" class="filters">
                    <div style="flex: 1;">
                        <div class="input-group">
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Cari pengaduan..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="menunggu" <?php echo $status === 'menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                            <option value="diproses" <?php echo $status === 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                            <option value="selesai" <?php echo $status === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                            <option value="ditolak" <?php echo $status === 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                        </select>
                    </div>
                </form>

                <?php if (count($pengaduan_list) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Judul</th>
                                    <th>Pelapor</th>
                                    <th>Kategori</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pengaduan_list as $pengaduan): 
                                    $status_class = '';
                                    $status_text = '';
                                    switch (strtolower($pengaduan['status'])) {
                                        case 'diproses':
                                            $status_class = 'warning';
                                            $status_text = 'Diproses';
                                            break;
                                        case 'ditolak':
                                            $status_class = 'danger';
                                            $status_text = 'Ditolak';
                                            break;
                                        case 'selesai':
                                            $status_class = 'success';
                                            $status_text = 'Selesai';
                                            break;
                                        case 'menunggu':
                                        default:
                                            $status_class = 'info';
                                            $status_text = 'Menunggu';
                                    }
                                ?>
                                    <tr>
                                        <td>#<?php echo $pengaduan['id']; ?></td>
                                        <td>
                                            <div class="text-truncate" title="<?php echo htmlspecialchars($pengaduan['judul_pengaduan']); ?>">
                                                <?php echo htmlspecialchars($pengaduan['judul_pengaduan']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($pengaduan['nama_pelapor'] ?? 'Anonim'); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($pengaduan['telp_pelapor'] ?? '-'); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($pengaduan['nama_kategori'] ?? '-'); ?></td>
                                        <td><?php echo date('d M Y', strtotime($pengaduan['created_at'])); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="detail_pengaduan.php?id=<?php echo $pengaduan['id']; ?>" 
                                                   class="action-btn btn-primary" 
                                                   title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_pengaduan.php?id=<?php echo $pengaduan['id']; ?>" 
                                                   class="action-btn btn-outline-primary" 
                                                   title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav>
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?>">
                                            &laquo; Sebelumnya
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                    if ($start_page > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor;
                                
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
                                }
                                ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status) ? '&status=' . urlencode($status) : ''; ?>">
                                            Selanjutnya &raquo;
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-inbox"></i>
                        <h3>Tidak ada data pengaduan</h3>
                        <p>Belum ada pengaduan yang ditemukan</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>