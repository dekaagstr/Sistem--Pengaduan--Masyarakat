<?php
require_once 'config/helpers.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();


$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori";
$stmt_kategori = $db->prepare($query_kategori);
$stmt_kategori->execute();
$kategories = $stmt_kategori->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_kategori = $_POST['id_kategori'];
    $nik = $_POST['nik'];
    $nama_pelapor = $_POST['nama_pelapor'];
    $email = $_POST['email'];
    $telepon = $_POST['telepon'];
    $judul_pengaduan = $_POST['judul_pengaduan'];
    $isi_pengaduan = $_POST['isi_pengaduan'];
    $lokasi_kejadian = $_POST['lokasi_kejadian'];
    $tanggal_kejadian = $_POST['tanggal_kejadian'];
    
   
    $foto = null;
    if (!empty($_FILES['foto']['name'])) {
        $upload_dir = "assets/uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $upload_result = uploadFile($_FILES['foto'], $upload_dir);
        if ($upload_result['success']) {
            $foto = $upload_result['filename'];
        } else {
            $_SESSION['error'] = $upload_result['message'];
        }
    }
    
    $query = "INSERT INTO pengaduan (id_kategori, nik, nama_pelapor, email, telepon, judul_pengaduan, isi_pengaduan, lokasi_kejadian, tanggal_kejadian, foto) 
              VALUES (:id_kategori, :nik, :nama_pelapor, :email, :telepon, :judul_pengaduan, :isi_pengaduan, :lokasi_kejadian, :tanggal_kejadian, :foto)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_kategori', $id_kategori);
    $stmt->bindParam(':nik', $nik);
    $stmt->bindParam(':nama_pelapor', $nama_pelapor);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':telepon', $telepon);
    $stmt->bindParam(':judul_pengaduan', $judul_pengaduan);
    $stmt->bindParam(':isi_pengaduan', $isi_pengaduan);
    $stmt->bindParam(':lokasi_kejadian', $lokasi_kejadian);
    $stmt->bindParam(':tanggal_kejadian', $tanggal_kejadian);
    $stmt->bindParam(':foto', $foto);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Pengaduan berhasil dikirim! Nomor pengaduan: " . $db->lastInsertId();
        header("Location: pengaduan_masyarakat.php");
        exit();
    } else {
        $_SESSION['error'] = "Terjadi kesalahan saat mengirim pengaduan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaduan Masyarakat</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content" style="display: flex; justify-content: space-between; align-items: center;">
                <div class="logo">Sistem Pengaduan Masyarakat</div>
                <nav class="nav">
                    <ul style="display: flex; align-items: center; gap: 10px; list-style: none; margin: 0; padding: 0;">
                        <li>
                            <a href="dashboard/" class="btn" style="background: #f8f9fa; color: #4361ee; padding: 8px 16px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; border: 1px solid #e0e0e0;">
                                <i class="fas fa-tachometer-alt" style="margin-right: 6px;"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="auth/logout.php" style="padding: 8px 16px; color: #dc3545; text-decoration: none; display: flex; align-items: center;">
                                <i class="fas fa-sign-out-alt" style="margin-right: 6px;"></i> Logout
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h2 style="margin: 0;">Form Pengaduan Masyarakat</h2>
                </div>
                <div class="card-body">
                    <?php
                    if (isset($_SESSION['success'])) {
                        echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                        unset($_SESSION['success']);
                    }
                    
                    if (isset($_SESSION['error'])) {
                        echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                        unset($_SESSION['error']);
                    }
                    ?>
                    
                    <form action="pengaduan_masyarakat.php" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label" for="id_kategori">Kategori Pengaduan *</label>
                                    <select class="form-control" id="id_kategori" name="id_kategori" required>
                                        <option value="">Pilih Kategori</option>
                                        <?php foreach ($kategories as $kategori): ?>
                                            <option value="<?php echo $kategori['id']; ?>"><?php echo htmlspecialchars($kategori['nama_kategori']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label" for="nik">NIK *</label>
                                    <input type="text" class="form-control" id="nik" name="nik" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label" for="nama_pelapor">Nama Lengkap *</label>
                                    <input type="text" class="form-control" id="nama_pelapor" name="nama_pelapor" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label" for="telepon">Nomor Telepon *</label>
                                    <input type="text" class="form-control" id="telepon" name="telepon" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="judul_pengaduan">Judul Pengaduan *</label>
                            <input type="text" class="form-control" id="judul_pengaduan" name="judul_pengaduan" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="isi_pengaduan">Isi Pengaduan *</label>
                            <textarea class="form-control" id="isi_pengaduan" name="isi_pengaduan" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label" for="lokasi_kejadian">Lokasi Kejadian *</label>
                                    <input type="text" class="form-control" id="lokasi_kejadian" name="lokasi_kejadian" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label" for="tanggal_kejadian">Tanggal Kejadian *</label>
                                    <input type="date" class="form-control" id="tanggal_kejadian" name="tanggal_kejadian" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="foto">Foto Bukti</label>
                            <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                            <small>Format: JPG, PNG, GIF (Maksimal 2MB)</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Kirim Pengaduan</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Sistem Pengaduan Layanan Masyarakat</p>
        </div>
    </footer>
</body>
</html>