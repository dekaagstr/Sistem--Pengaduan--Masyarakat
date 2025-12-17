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

// Set default tanggal (bulan ini)
$start_date = date('Y-m-01');
$end_date = date('Y-m-t');

// Ambil parameter filter
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $start_date = $_POST['start_date'] ?? $start_date;
    $end_date = $_POST['end_date'] ?? $end_date;
    $status = $_POST['status'] ?? '';
    $kategori = $_POST['kategori'] ?? '';
}

// Query untuk mendapatkan data laporan
$query = "SELECT 
    p.*, 
    k.nama_kategori,
    u.nama_lengkap as admin_penanggung_jawab,
    (SELECT COUNT(*) FROM tanggapan t WHERE t.id_pengaduan = p.id) as jumlah_tanggapan
FROM pengaduan p
LEFT JOIN kategori k ON p.id_kategori = k.id
LEFT JOIN tanggapan tg ON tg.id_pengaduan = p.id
LEFT JOIN users u ON tg.id_user = u.id
WHERE DATE(p.created_at) BETWEEN :start_date AND :end_date";

$params = [
    ':start_date' => $start_date,
    ':end_date' => $end_date
];

// Tambahkan filter status
if (!empty($status)) {
    $query .= " AND p.status = :status";
    $params[':status'] = $status;
}

// Tambahkan filter kategori
if (!empty($kategori)) {
    $query .= " AND p.id_kategori = :kategori";
    $params[':kategori'] = $kategori;
}

$query .= " GROUP BY p.id ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung statistik
$query_stats = "SELECT 
    COUNT(*) as total_pengaduan,
    SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
    SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as diproses,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
    SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
FROM pengaduan 
WHERE DATE(created_at) BETWEEN :start_date AND :end_date";

$stmt_stats = $db->prepare($query_stats);
$stmt_stats->bindValue(':start_date', $start_date);
$stmt_stats->bindValue(':end_date', $end_date);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Ambil daftar kategori untuk filter
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori";
$kategori_list = $db->query($query_kategori)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengaduan - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .btn-success {
            background-color: var(--success);
            color: white;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.875rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .badge-info {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .badge-success {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .badge-danger {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .stat-card .number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .filter-form {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .col {
            flex: 1;
            padding: 0 10px;
            min-width: 200px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 30px;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .table th {
                background-color: #f1f1f1 !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-chart-bar"></i> Laporan Pengaduan</h2>
                <div>
                    <button onclick="window.print()" class="btn btn-primary no-print">
                        <i class="fas fa-print"></i> Cetak Laporan
                    </button>
                    <a href="dashboard.php" class="btn no-print">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Form Filter -->
                <form method="POST" class="filter-form no-print">
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label for="start_date">Dari Tanggal</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="end_date">Sampai Tanggal</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="menunggu" <?php echo (isset($_POST['status']) && $_POST['status'] == 'menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                                    <option value="diproses" <?php echo (isset($_POST['status']) && $_POST['status'] == 'diproses') ? 'selected' : ''; ?>>Diproses</option>
                                    <option value="selesai" <?php echo (isset($_POST['status']) && $_POST['status'] == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                    <option value="ditolak" <?php echo (isset($_POST['status']) && $_POST['status'] == 'ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                                </select>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label for="kategori">Kategori</label>
                                <select class="form-control" id="kategori" name="kategori">
                                    <option value="">Semua Kategori</option>
                                    <?php foreach ($kategori_list as $kat): ?>
                                        <option value="<?php echo $kat['id']; ?>" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == $kat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="laporan.php" class="btn">Reset</a>
                    </div>
                </form>

                <!-- Statistik -->
                <div class="card">
                    <div class="card-header">
                        <h3>Ringkasan Laporan</h3>
                        <span>Periode: <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <h3>Total Pengaduan</h3>
                                <div class="number"><?php echo number_format($stats['total_pengaduan']); ?></div>
                            </div>
                            <div class="stat-card">
                                <h3>Menunggu</h3>
                                <div class="number" style="color: #ef6c00;"><?php echo number_format($stats['menunggu']); ?></div>
                            </div>
                            <div class="stat-card">
                                <h3>Diproses</h3>
                                <div class="number" style="color: #1565c0;"><?php echo number_format($stats['diproses']); ?></div>
                            </div>
                            <div class="stat-card">
                                <h3>Selesai</h3>
                                <div class="number" style="color: #2e7d32;"><?php echo number_format($stats['selesai']); ?></div>
                            </div>
                            <div class="stat-card">
                                <h3>Ditolak</h3>
                                <div class="number" style="color: #c62828;"><?php echo number_format($stats['ditolak']); ?></div>
                            </div>
                        </div>

                        <!-- Grafik -->
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tabel Laporan -->
                <div class="card">
                    <div class="card-header">
                        <h3>Daftar Pengaduan</h3>
                        <span>Total: <?php echo count($laporan); ?> data</span>
                    </div>
                    <div class="card-body">
                        <?php if (count($laporan) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Judul</th>
                                            <th>Kategori</th>
                                            <th>Pelapor</th>
                                            <th>Status</th>
                                            <th>Tanggapan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; ?>
                                        <?php foreach ($laporan as $item): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo date('d M Y H:i', strtotime($item['created_at'])); ?></td>
                                                <td>
                                                    <a href="detail_pengaduan.php?id=<?php echo $item['id']; ?>" target="_blank">
                                                        <?php echo htmlspecialchars($item['judul_pengaduan']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['nama_kategori'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($item['nama_pelapor']); ?></td>
                                                <td>
                                                    <?php 
                                                    $status_class = '';
                                                    switch($item['status']) {
                                                        case 'menunggu':
                                                            $status_class = 'badge-warning';
                                                            break;
                                                        case 'diproses':
                                                            $status_class = 'badge-info';
                                                            break;
                                                        case 'selesai':
                                                            $status_class = 'badge-success';
                                                            break;
                                                        case 'ditolak':
                                                            $status_class = 'badge-danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($item['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($item['jumlah_tanggapan'] > 0): ?>
                                                        <span class="badge badge-success"><?php echo $item['jumlah_tanggapan']; ?> tanggapan</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Belum ada tanggapan</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center" style="padding: 40px 0;">
                                <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                                <p>Tidak ada data pengaduan untuk periode ini</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Data untuk chart
        const ctx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Menunggu', 'Diproses', 'Selesai', 'Ditolak'],
                datasets: [{
                    label: 'Jumlah Pengaduan',
                    data: [
                        <?php echo $stats['menunggu']; ?>, 
                        <?php echo $stats['diproses']; ?>, 
                        <?php echo $stats['selesai']; ?>, 
                        <?php echo $stats['ditolak']; ?>
                    ],
                    backgroundColor: [
                        'rgba(239, 108, 0, 0.7)',
                        'rgba(21, 101, 192, 0.7)',
                        'rgba(46, 125, 50, 0.7)',
                        'rgba(198, 40, 40, 0.7)'
                    ],
                    borderColor: [
                        'rgba(239, 108, 0, 1)',
                        'rgba(21, 101, 192, 1)',
                        'rgba(46, 125, 50, 1)',
                        'rgba(198, 40, 40, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Distribusi Status Pengaduan',
                        font: {
                            size: 16
                        }
                    }
                }
            }
        });

        // Update end date when start date changes
        document.getElementById('start_date').addEventListener('change', function() {
            const endDate = document.getElementById('end_date');
            if (this.value > endDate.value) {
                endDate.value = this.value;
            }
            endDate.min = this.value;
        });
    </script>
</body>
</html>
