<?php
require_once 'header.php';

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Hitung statistik dengan error handling
try {
    $query_pengaduan = "SELECT COUNT(*) as total FROM pengaduan";
    $stmt_pengaduan = $db->prepare($query_pengaduan);
    $stmt_pengaduan->execute();
    $total_pengaduan = $stmt_pengaduan->fetch(PDO::FETCH_ASSOC)['total'];

    $query_menunggu = "SELECT COUNT(*) as total FROM pengaduan WHERE status = 'menunggu'";
    $stmt_menunggu = $db->prepare($query_menunggu);
    $stmt_menunggu->execute();
    $total_menunggu = $stmt_menunggu->fetch(PDO::FETCH_ASSOC)['total'];

    $query_diproses = "SELECT COUNT(*) as total FROM pengaduan WHERE status = 'diproses'";
    $stmt_diproses = $db->prepare($query_diproses);
    $stmt_diproses->execute();
    $total_diproses = $stmt_diproses->fetch(PDO::FETCH_ASSOC)['total'];

    $query_selesai = "SELECT COUNT(*) as total FROM pengaduan WHERE status = 'selesai'";
    $stmt_selesai = $db->prepare($query_selesai);
    $stmt_selesai->execute();
    $total_selesai = $stmt_selesai->fetch(PDO::FETCH_ASSOC)['total'];

    // Pengaduan terbaru
    $query_recent = "SELECT p.*, k.nama_kategori 
                     FROM pengaduan p 
                     LEFT JOIN kategori k ON p.id_kategori = k.id 
                     ORDER BY p.created_at DESC 
                     LIMIT 5";
    $stmt_recent = $db->prepare($query_recent);
    $stmt_recent->execute();
    $recent_pengaduan = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $total_pengaduan = $total_menunggu = $total_diproses = $total_selesai = 0;
    $recent_pengaduan = [];
}
?>

<style>
    :root {
        --primary: #4361ee;
        --primary-light: #e6e9ff;
        --primary-dark: #3a56d4;
        --success: #4bb543;
        --success-light: #e8f5e9;
        --warning: #ff9800;
        --warning-light: #fff3e0;
        --danger: #f44336;
        --danger-light: #ffebee;
        --info: #2196f3;
        --info-light: #e3f2fd;
        --light: #f8f9fa;
        --dark: #2c3e50;
        --gray: #6c757d;
        --light-gray: #f1f3f6;
        --border-radius: 12px;
        --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        --transition: all 0.3s ease;
    }
    
    .welcome-message {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        padding: 2rem;
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
        box-shadow: var(--box-shadow);
        position: relative;
        overflow: hidden;
    }
    
    .welcome-message::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
        transform: rotate(30deg);
    }
    
    .welcome-message h2 {
        color: white;
        margin: 0 0 0.5rem 0;
        font-size: 1.8rem;
        position: relative;
    }
    
    .welcome-message p {
        color: rgba(255, 255, 255, 0.9);
        margin: 0;
        font-size: 1rem;
        position: relative;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: #fff;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--box-shadow);
        transition: var(--transition);
        border: none;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: var(--primary);
    }
    
    .stat-card.total::before { background: var(--primary); }
    .stat-card.menunggu::before { background: var(--warning); }
    .stat-card.diproses::before { background: var(--info); }
    .stat-card.selesai::before { background: var(--success); }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .stat-card .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        font-size: 1.5rem;
        color: white;
    }
    
    .stat-card.total .stat-icon { background: var(--primary); }
    .stat-card.menunggu .stat-icon { background: var(--warning); }
    .stat-card.diproses .stat-icon { background: var(--info); }
    .stat-card.selesai .stat-icon { background: var(--success); }
    
    .stat-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 1rem;
    }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        color: white;
        font-size: 1.5rem;
    }
    
    .stat-icon.total { background-color: var(--primary); }
    .stat-icon.menunggu { background-color: var(--warning); }
    .stat-icon.diproses { background-color: var(--info); }
    .stat-icon.selesai { background-color: var(--success); }
    
    .stat-info h3 {
        font-size: 0.9rem;
        color: var(--gray);
        margin: 0;
        font-weight: 500;
    }
    
    .stat-label {
        font-size: 0.9rem;
        color: var(--gray);
        margin: 0;
        font-weight: 500;
    }
    
    .stat-change {
        font-size: 0.8rem;
        color: var(--success);
        display: flex;
        align-items: center;
        margin-top: 0.5rem;
    }
    
    .stat-change.down {
        color: var(--danger);
    }
    
    .stat-number {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--dark);
        margin: 0.5rem 0;
        line-height: 1.2;
    }
    
    .recent-complaints {
        background: #fff;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        margin-top: 2rem;
        overflow: hidden;
        transition: var(--transition);
    }
    
    .recent-complaints:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .recent-complaints .card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fff;
    }
    
    .card-header h3 {
        margin: 0;
        font-size: 1.25rem;
        color: var(--dark);
    }
    
    .recent-complaints .card-header h3 {
        margin: 0;
        color: var(--dark);
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table th,
    .table td {
        padding: 1rem 1.5rem;
        text-align: left;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .table th {
        background-color: #f8f9fa;
        color: var(--gray);
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .table tbody tr:hover {
        background-color: rgba(67, 97, 238, 0.05);
    }
    
    .badge {
        display: inline-block;
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.25rem;
    }
    
    .badge-warning {
        color: #856404;
        background-color: #fff3cd;
    }
    
    .badge-info {
        color: #0c5460;
        background-color: #d1ecf1;
    }
    
    .badge-success {
        color: #155724;
        background-color: #d4edda;
    }
    
    .badge-danger {
        color: #721c24;
        background-color: #f8d7da;
    }
    
    .text-muted {
        color: #6c757d !important;
    }
    
    .no-data {
        padding: 3rem 1.5rem;
        text-align: center;
        color: var(--gray);
    }
    
    .no-data i {
        font-size: 3rem;
        opacity: 0.3;
        margin-bottom: 1rem;
        display: block;
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
        
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    }
</style>

<?php
// Fungsi untuk mengubah nama hari ke bahasa Indonesia
function hariIndo($tanggal) {
    $day = date('N', strtotime($tanggal));
    $days = array(
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu'
    );
    return $days[$day];
}

// Fungsi untuk mengubah nama bulan ke bahasa Indonesia
function tglIndo($tanggal) {
    $bulan = array (
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    $pecahkan = explode('-', $tanggal);
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}

$sekarang = date('Y-m-d');
$hari_ini = hariIndo($sekarang);
$tgl_indo = tglIndo($sekarang);
?>

<div class="welcome-message">
    <h2>Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Pengguna'); ?></h2>
    <p><?php echo $hari_ini . ', ' . $tgl_indo; ?></p>
</div>

<div class="stats-grid">
    <div class="stat-card total">
        <div class="stat-header">
            <div class="stat-icon total">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="stat-info">
                <h3>Total Pengaduan</h3>
                <div class="stat-number"><?php echo number_format($total_pengaduan); ?></div>
            </div>
        </div>
    </div>
    
    <div class="stat-card menunggu">
        <div class="stat-header">
            <div class="stat-icon menunggu">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3>Menunggu</h3>
                <div class="stat-number"><?php echo number_format($total_menunggu); ?></div>
            </div>
        </div>
    </div>
    
    <div class="stat-card diproses">
        <div class="stat-header">
            <div class="stat-icon diproses">
                <i class="fas fa-spinner"></i>
            </div>
            <div class="stat-info">
                <h3>Diproses</h3>
                <div class="stat-number"><?php echo number_format($total_diproses); ?></div>
            </div>
        </div>
    </div>
    
    <div class="stat-card selesai">
        <div class="stat-header">
            <div class="stat-icon selesai">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3>Selesai</h3>
                <div class="stat-number"><?php echo number_format($total_selesai); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="recent-complaints">
    <div class="card-header">
        <h3>Pengaduan Terbaru</h3>
    </div>
    <div class="card-body p-0">
        <h3>Selamat datang, <?php echo isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'User'; ?>!</h3>
        <p>Ini adalah sistem pengaduan layanan masyarakat.</p>
        
        <div class="alert alert-<?php echo isAdmin() ? 'info' : 'warning'; ?>">
            <strong>Anda login sebagai <?php echo isAdmin() ? 'Warga' : 'Warga'; ?></strong> - 
            <?php 
            if (isAdmin()) {
                echo 'Anda memiliki akses untuk membuat dan melacak pengaduan.';
            } else {
                echo 'Anda dapat membuat dan melacak pengaduan Anda.';
            }
            ?>
        </div>
        
        <?php if (count($recent_pengaduan) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Foto</th>
                            <th>Detail Pengaduan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($recent_pengaduan as $pengaduan): 
                            // Inisialisasi variabel
                    $photo_src = '';
                    $foto_name = $pengaduan['foto'] ?? '';
                    
                    if (!empty($foto_name)) {
    // Bersihkan nama file
    $foto_name = basename($foto_name);
    
    // Path yang benar relatif ke root website
    $correct_path = '../assets/uploads/' . $foto_name;
    $full_path = realpath(__DIR__ . '/../assets/uploads/') . '/' . $foto_name;
    
    // Cek apakah file ada di lokasi yang benar
    if (file_exists($full_path)) {
        $photo_src = $correct_path;
    } 
    // Fallback: coba cari di direktori uploads
    else {
        $uploads_dir = realpath(__DIR__ . '/../assets/uploads/');
        if ($uploads_dir && is_dir($uploads_dir)) {
            $files = scandir($uploads_dir);
            foreach ($files as $file) {
                if (strtolower($file) === strtolower($foto_name)) {
                    $photo_src = '../assets/uploads/' . $file;
                    break;
                }
            }
        }
    }
}
                            
                            // Format date
                            $tgl_pengaduan = date('d/m/Y', strtotime($pengaduan['created_at']));
                            $jam_pengaduan = date('H:i', strtotime($pengaduan['created_at']));
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <?php if (!empty($photo_src)): ?>
                                        <div class="complaint-photo" style="width: 80px; height: 60px; overflow: hidden; border-radius: 4px;">
                                            <img src="<?php echo $photo_src; ?>" alt="Foto Pengaduan" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    <?php else: ?>
                                        <div style="width: 80px; height: 60px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                            <i class="fas fa-image" style="font-size: 24px; color: #999;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="font-weight-bold"><?php echo htmlspecialchars($pengaduan['judul_pengaduan'] ?? '-'); ?></div>
                                    <div class="text-muted small">
                                        <i class="far fa-calendar-alt mr-1"></i> <?php echo $tgl_pengaduan . ' ' . $jam_pengaduan; ?>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-tag mr-1"></i> <?php echo htmlspecialchars($pengaduan['nama_kategori'] ?? '-'); ?>
                                    </div>
                                    <div class="mt-1 text-truncate" style="max-width: 300px;">
                                        <?php 
                                        $isi = strip_tags($pengaduan['isi_pengaduan'] ?? '');
                                        echo strlen($isi) > 100 ? substr($isi, 0, 100) . '...' : $isi;
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch (strtolower($pengaduan['status'])) {
                                        case 'menunggu':
                                            $status_class = 'warning';
                                            break;
                                        case 'diproses':
                                            $status_class = 'info';
                                            break;
                                        case 'selesai':
                                            $status_class = 'success';
                                            break;
                                        case 'ditolak':
                                            $status_class = 'danger';
                                            break;
                                        default:
                                            $status_class = 'secondary';
                                    }
                                    ?>
                                    <span class="badge badge-<?php echo $status_class; ?>">
                                        <?php echo ucfirst($pengaduan['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="detail_pengaduan.php?id=<?php echo $pengaduan['id']; ?>" class="btn" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-inbox"></i>
                <p>Belum ada pengaduan yang dibuat</p>
                <a href="pengaduan_masyarakat.php" class="btn mt-3">
                    <i class="fas fa-plus"></i> Buat Pengaduan Baru
                </a>
            </div>
        <?php endif; ?>
      </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>