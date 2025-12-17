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

// Proses form jika ada data yang dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $tanggapan = trim($_POST['tanggapan'] ?? '');
    $id_petugas = $_SESSION['user_id'];
    
    // Validasi data
    $errors = [];
    
    if (!in_array($status, ['menunggu', 'diproses', 'selesai', 'ditolak'])) {
        $errors[] = "Status tidak valid";
    }
    
    if (empty($tanggapan) && $status !== 'menunggu') {
        $errors[] = "Tanggapan tidak boleh kosong untuk status ini";
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Update status pengaduan
            $query = "UPDATE pengaduan SET status = :status WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':status' => $status,
                ':id' => $id
            ]);
            
            // Jika ada tanggapan, simpan ke tabel tanggapan
            if (!empty($tanggapan)) {
                // Cek apakah sudah ada tanggapan sebelumnya
                $query = "SELECT id FROM tanggapan WHERE id_pengaduan = :id_pengaduan";
                $stmt = $db->prepare($query);
                $stmt->execute([':id_pengaduan' => $id]);
                $tanggapan_ada = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($tanggapan_ada) {
                    // Update tanggapan yang sudah ada
                    $query = "UPDATE tanggapan 
                             SET tanggapan = :tanggapan, 
                                 id_user = :id_user,
                                 created_at = NOW()
                             WHERE id_pengaduan = :id_pengaduan";
                } else {
                    // Buat tanggapan baru
                    $query = "INSERT INTO tanggapan 
                             (id_pengaduan, id_user, tanggapan, created_at)
                             VALUES 
                             (:id_pengaduan, :id_user, :tanggapan, NOW())";
                }
                
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':id_pengaduan' => $id,
                    ':id_user' => $id_petugas,
                    ':tanggapan' => $tanggapan
                ]);
            }
            
            // Commit transaksi
            $db->commit();
            
            $_SESSION['success'] = "Pengaduan berhasil diperbarui";
            header("Location: detail_pengaduan.php?id=" . $id);
            exit();
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Format tanggal
$tanggal_dibuat = date('d F Y H:i', strtotime($pengaduan['created_at']));
$tanggal_ditanggapi = !empty($pengaduan['tgl_tanggapan']) ? date('d F Y H:i', strtotime($pengaduan['tgl_tanggapan'])) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengaduan - Admin</title>
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
        
        .btn-back {
            background-color: var(--gray);
            color: white;
        }
        
        .btn-back:hover {
            background-color: #5a6268;
            color: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            transition: all 0.2s;
        }
        
        .radio-option:hover {
            background-color: #f8f9fa;
        }
        
        .radio-option input[type="radio"] {
            margin: 0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .status-menunggu {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-diproses {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-selesai {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-ditolak {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .detail-item {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
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
            max-height: 300px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
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
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 10px;
            }
            
            .radio-option {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-edit"></i> Edit Pengaduan</h2>
                <a href="detail_pengaduan.php?id=<?php echo $id; ?>" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Kembali ke Detail
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul style="margin: 0; padding-left: 20px;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="detail-item">
                        <div class="detail-label">ID Pengaduan</div>
                        <div class="detail-value">#<?php echo $pengaduan['id']; ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Judul Pengaduan</div>
                        <div class="detail-value"><?php echo htmlspecialchars($pengaduan['judul_pengaduan']); ?></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Status Pengaduan</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="status" value="menunggu" <?php echo ($pengaduan['status'] === 'menunggu') ? 'checked' : ''; ?>>
                                <span class="status-badge status-menunggu">Menunggu</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="status" value="diproses" <?php echo ($pengaduan['status'] === 'diproses') ? 'checked' : ''; ?>>
                                <span class="status-badge status-diproses">Diproses</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="status" value="selesai" <?php echo ($pengaduan['status'] === 'selesai') ? 'checked' : ''; ?>>
                                <span class="status-badge status-selesai">Selesai</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="status" value="ditolak" <?php echo ($pengaduan['status'] === 'ditolak') ? 'checked' : ''; ?>>
                                <span class="status-badge status-ditolak">Ditolak</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="tanggapan" class="form-label">Tanggapan</label>
                        <textarea name="tanggapan" id="tanggapan" class="form-control" placeholder="Masukkan tanggapan Anda..."><?php echo isset($pengaduan['tanggapan']) ? htmlspecialchars($pengaduan['tanggapan']) : ''; ?></textarea>
                        <small class="text-muted">Isi tanggapan jika status diproses, selesai, atau ditolak</small>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Pelapor</div>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($pengaduan['nama_pelapor']); ?> 
                            (<?php echo !empty($pengaduan['telp_pelapor']) ? htmlspecialchars($pengaduan['telp_pelapor']) : 'No. Telp tidak tersedia'; ?>)
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Tanggal Dibuat</div>
                        <div class="detail-value"><?php echo $tanggal_dibuat; ?></div>
                    </div>
                    
                    <?php if (!empty($tanggal_ditanggapi)): ?>
                    <div class="detail-item">
                        <div class="detail-label">Tanggapan Terakhir</div>
                        <div class="detail-value"><?php echo $tanggal_ditanggapi; ?> oleh <?php echo htmlspecialchars($pengaduan['nama_petugas'] ?? 'Petugas'); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                        <a href="detail_pengaduan.php?id=<?php echo $id; ?>" class="btn btn-back">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Menonaktifkan textarea tanggapan jika status menunggu
        document.addEventListener('DOMContentLoaded', function() {
            const statusRadios = document.querySelectorAll('input[name="status"]');
            const tanggapanTextarea = document.getElementById('tanggapan');
            
            function updateTanggapanField() {
                const selectedStatus = document.querySelector('input[name="status"]:checked').value;
                if (selectedStatus === 'menunggu') {
                    tanggapanTextarea.disabled = true;
                    tanggapanTextarea.placeholder = 'Tidak diperlukan tanggapan untuk status menunggu';
                } else {
                    tanggapanTextarea.disabled = false;
                    tanggapanTextarea.placeholder = 'Masukkan tanggapan Anda...';
                    if (selectedStatus === 'diproses' && !tanggapanTextarea.value) {
                        tanggapanTextarea.placeholder = 'Berikan informasi proses penanganan...';
                    } else if (selectedStatus === 'selesai' && !tanggapanTextarea.value) {
                        tanggapanTextarea.placeholder = 'Berikan penjelasan penyelesaian...';
                    } else if (selectedStatus === 'ditolak' && !tanggapanTextarea.value) {
                        tanggapanTextarea.placeholder = 'Berikan alasan penolakan...';
                    }
                }
            }
            
            // Panggil fungsi saat halaman dimuat
            updateTanggapanField();
            
            // Tambahkan event listener untuk setiap radio button
            statusRadios.forEach(radio => {
                radio.addEventListener('change', updateTanggapanField);
            });
            
            // Validasi form sebelum submit
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const selectedStatus = document.querySelector('input[name="status"]:checked').value;
                const tanggapan = tanggapanTextarea.value.trim();
                
                if (selectedStatus !== 'menunggu' && !tanggapan) {
                    e.preventDefault();
                    alert('Mohon isi tanggapan untuk status yang dipilih');
                    tanggapanTextarea.focus();
                }
            });
        });
    </script>
</body>
</html>