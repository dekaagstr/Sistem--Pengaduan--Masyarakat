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

// Ambil ID pengaduan dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Query untuk mengambil detail pengaduan
$query = "SELECT p.*, k.nama_kategori, 
          p.nama_pelapor, p.telepon as telp_pelapor, 
          t.tanggapan, t.created_at as tgl_tanggapan,
          u.nama_lengkap as nama_petugas
          FROM pengaduan p 
          LEFT JOIN kategori k ON p.id_kategori = k.id 
          LEFT JOIN tanggapan t ON p.id = t.id_pengaduan
          LEFT JOIN users u ON t.id_user = u.id 
          WHERE p.id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$pengaduan = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika data tidak ditemukan
if (!$pengaduan) {
    $_SESSION['error'] = "Data pengaduan tidak ditemukan";
    header('Location: lihat_pengaduan.php');
    exit();
}

// Format tanggal
$tanggal_dibuat = date('d F Y H:i', strtotime($pengaduan['created_at']));
$tanggal_ditanggapi = $pengaduan['tgl_tanggapan'] ? date('d F Y H:i', strtotime($pengaduan['tgl_tanggapan'])) : 'Belum ada tanggapan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pengaduan - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Tetap gunakan style yang sama dengan lihat_pengaduan.php */
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
            padding: 25px;
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
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
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
        
        .detail-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .detail-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
            display: block;
        }
        
        .detail-value {
            font-size: 1.05rem;
        }
        
        .foto-pengaduan {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid #eee;
        }
        
        .tanggapan-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            border-left: 4px solid var(--primary);
        }
        
        .tanggapan-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .tanggapan-content {
            line-height: 1.6;
            white-space: pre-line;
        }
        
        .no-data {
            text-align: center;
            padding: 30px 20px;
            color: var(--gray);
            font-style: italic;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .btn-edit {
            background-color: var(--warning);
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #e08e0b;
            color: white;
        }
        
        .btn-back {
            background-color: var(--gray);
            color: white;
        }
        
        .btn-back:hover {
            background-color: #5a6268;
            color: white;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .card-body {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-info-circle"></i> Detail Pengaduan</h2>
                <a href="lihat_pengaduan.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div>
                        <?php
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
                        <span class="badge badge-<?php echo $status_class; ?>">
                            <?php echo $status_text; ?>
                        </span>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">ID Pengaduan</div>
                    <div class="detail-value">#<?php echo $pengaduan['id']; ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Judul Pengaduan</div>
                    <div class="detail-value"><?php echo htmlspecialchars($pengaduan['judul_pengaduan']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Isi Laporan</div>
                    <div class="detail-value" style="white-space: pre-line;"><?php echo htmlspecialchars($pengaduan['isi_pengaduan']); ?></div>
                </div>
                
                <div class="detail-item">
    <div class="detail-label">Foto Bukti</div>
    <?php 
    // Try multiple possible paths
    $possible_paths = [
        '../assets/uploads/' . $pengaduan['foto'],
        'assets/uploads/' . $pengaduan['foto'],
        'uploads/' . $pengaduan['foto'],
        '../uploads/' . $pengaduan['foto'],
        $pengaduan['foto']
    ];
    
    $found = false;
    $actual_path = '';
    
    // Debug: Tampilkan path yang dicoba
    echo '<!-- Debug: Mencari file foto -->';
    foreach ($possible_paths as $path) {
        $full_path = __DIR__ . '/' . $path;
        echo "<!-- Mencoba: $full_path -->";
        if (file_exists($full_path) && is_file($full_path)) {
            $found = true;
            $actual_path = $path;
            echo "<!-- File ditemukan: $path -->";
            break;
        }
    }
    
    if ($found) {
        // Periksa tipe file
        $file_extension = strtolower(pathinfo($actual_path, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            echo '<div class="image-preview">';
            echo '<img src="' . htmlspecialchars($actual_path) . '" alt="Foto Bukti" style="max-width: 100%; max-height: 400px;">';
            echo '</div>';
            echo '<div class="mt-2">';
            echo '<a href="' . htmlspecialchars($actual_path) . '" class="btn btn-sm btn-primary" target="_blank" download>';
            echo '<i class="fas fa-download"></i> Unduh Foto';
            echo '</a>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-warning">Format file tidak didukung.</div>';
            echo '<!-- Ekstensi file: ' . htmlspecialchars($file_extension) . ' -->';
        }
    } else {
        echo '<div class="alert alert-warning">File foto tidak ditemukan.</div>';
        echo '<!-- Path yang dicoba: ' . htmlspecialchars(print_r($possible_paths, true)) . ' -->';
        echo '<!-- Direktori saat ini: ' . __DIR__ . ' -->';
    }
    ?>
</div>
                
                <div class="detail-item">
                    <div class="detail-label">Kategori</div>
                    <div class="detail-value"><?php echo htmlspecialchars($pengaduan['nama_kategori'] ?? '-'); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Data Pelapor</div>
                    <div class="detail-value">
                        <div><strong>Nama:</strong> <?php echo htmlspecialchars($pengaduan['nama_pelapor'] ?? 'Anonim'); ?></div>
                        <div><strong>No. Telepon:</strong> <?php echo htmlspecialchars($pengaduan['telp_pelapor'] ?? '-'); ?></div>
                        <div><strong>Alamat:</strong> <?php echo htmlspecialchars($pengaduan['alamat_pelapor'] ?? '-'); ?></div>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Tanggal Dibuat</div>
                    <div class="detail-value"><?php echo $tanggal_dibuat; ?></div>
                </div>
                
                <?php if (!empty($pengaduan['tanggapan'])): ?>
                <div class="tanggapan-box">
                    <div class="tanggapan-header">
                        <div><strong>Tanggapan Petugas</strong></div>
                        <div>Ditanggapi oleh: <?php echo htmlspecialchars($pengaduan['nama_petugas'] ?? 'Admin'); ?></div>
                    </div>
                    <div class="tanggapan-content">
                        <?php echo nl2br(htmlspecialchars($pengaduan['tanggapan'])); ?>
                    </div>
                    <div style="text-align: right; margin-top: 10px; font-size: 0.9rem; color: var(--gray);">
                        <?php echo $tanggal_ditanggapi; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <a href="edit_pengaduan.php?id=<?php echo $pengaduan['id']; ?>" class="btn btn-edit">
                        <i class="fas fa-edit"></i> Edit Pengaduan
                    </a>
                    <a href="lihat_pengaduan.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
