<?php
require_once 'header.php';

if (!isset($_GET['id'])) {
    header("Location: pengaduan.php");
    exit();
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$id_pengaduan = $_GET['id'];

// Get data pengaduan
$query_pengaduan = "SELECT p.*, k.nama_kategori 
                    FROM pengaduan p 
                    LEFT JOIN kategori k ON p.id_kategori = k.id 
                    WHERE p.id = :id";
$stmt_pengaduan = $db->prepare($query_pengaduan);
$stmt_pengaduan->bindParam(':id', $id_pengaduan);
$stmt_pengaduan->execute();
$pengaduan = $stmt_pengaduan->fetch(PDO::FETCH_ASSOC);

if (!$pengaduan) {
    $_SESSION['error'] = "Data pengaduan tidak ditemukan!";
    header("Location: pengaduan.php");
    exit();
}

// Get tanggapan
$query_tanggapan = "SELECT t.*, u.nama_lengkap 
                    FROM tanggapan t 
                    LEFT JOIN users u ON t.id_user = u.id 
                    WHERE t.id_pengaduan = :id_pengaduan 
                    ORDER BY t.created_at DESC";
$stmt_tanggapan = $db->prepare($query_tanggapan);
$stmt_tanggapan->bindParam(':id_pengaduan', $id_pengaduan);
$stmt_tanggapan->execute();
$tanggapans = $stmt_tanggapan->fetchAll(PDO::FETCH_ASSOC);

// Proses tambah tanggapan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_tanggapan'])) {
    $tanggapan = $_POST['tanggapan'];
    $id_user = $_SESSION['user_id'];
    
    $query = "INSERT INTO tanggapan (id_pengaduan, id_user, tanggapan) 
              VALUES (:id_pengaduan, :id_user, :tanggapan)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_pengaduan', $id_pengaduan);
    $stmt->bindParam(':id_user', $id_user);
    $stmt->bindParam(':tanggapan', $tanggapan);
    
    if ($stmt->execute()) {
        // Update status pengaduan menjadi diproses jika masih menunggu
        if ($pengaduan['status'] == 'menunggu') {
            $query_update = "UPDATE pengaduan SET status = 'diproses' WHERE id = :id";
            $stmt_update = $db->prepare($query_update);
            $stmt_update->bindParam(':id', $id_pengaduan);
            $stmt_update->execute();
        }
        
        $_SESSION['success'] = "Tanggapan berhasil ditambahkan!";
        header("Location: detail_pengaduan.php?id=" . $id_pengaduan);
        exit();
    } else {
        $_SESSION['error'] = "Gagal menambahkan tanggapan!";
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2>Detail Pengaduan</h2>
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

        <!-- Informasi Pengaduan -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>Informasi Pengaduan</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <table class="table">
                            <tr>
                                <th width="30%">Judul Pengaduan</th>
                                <td><?php echo htmlspecialchars($pengaduan['judul_pengaduan']); ?></td>
                            </tr>
                            <tr>
                                <th>Kategori</th>
                                <td><?php echo htmlspecialchars($pengaduan['nama_kategori']); ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td><?php echo getStatusBadge($pengaduan['status']); ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Pengaduan</th>
                                <td><?php echo formatTanggal($pengaduan['created_at']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-6">
                        <table class="table">
                            <tr>
                                <th width="30%">Nama Pelapor</th>
                                <td><?php echo htmlspecialchars($pengaduan['nama_pelapor']); ?></td>
                            </tr>
                            <tr>
                                <th>NIK</th>
                                <td><?php echo $pengaduan['nik']; ?></td>
                            </tr>
                            <tr>
                                <th>Telepon</th>
                                <td><?php echo $pengaduan['telepon']; ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?php echo $pengaduan['email'] ?: '-'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <table class="table">
                            <tr>
                                <th width="15%">Lokasi Kejadian</th>
                                <td><?php echo htmlspecialchars($pengaduan['lokasi_kejadian']); ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Kejadian</th>
                                <td><?php echo formatTanggal($pengaduan['tanggal_kejadian']); ?></td>
                            </tr>
                            <tr>
                                <th>Isi Pengaduan</th>
                                <td><?php echo nl2br(htmlspecialchars($pengaduan['isi_pengaduan'])); ?></td>
                            </tr>
                            <?php if ($pengaduan['foto']): ?>
                            <tr>
                                <th>Foto Bukti</th>
                                <td>
                                    <img src="../assets/uploads/<?php echo $pengaduan['foto']; ?>" 
                                         alt="Foto Bukti" 
                                         style="max-width: 300px; max-height: 300px; border-radius: 4px;">
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Tanggapan -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>Berikan Tanggapan</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="detail_pengaduan.php?id=<?php echo $id_pengaduan; ?>">
                    <div class="form-group">
                        <label class="form-label" for="tanggapan">Tanggapan</label>
                        <textarea class="form-control" id="tanggapan" name="tanggapan" rows="4" required></textarea>
                    </div>
                    <button type="submit" name="tambah_tanggapan" value="1" class="btn btn-primary">Kirim Tanggapan</button>
                </form>
            </div>
        </div>

        <!-- Daftar Tanggapan -->
        <div class="card">
            <div class="card-header">
                <h3>Riwayat Tanggapan</h3>
            </div>
            <div class="card-body">
                <?php if (count($tanggapans) > 0): ?>
                    <?php foreach ($tanggapans as $tanggapan): ?>
                        <div class="card" style="margin-bottom: 1rem;">
                            <div class="card-body">
                                <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 0.5rem;">
                                    <strong><?php echo htmlspecialchars($tanggapan['nama_lengkap']); ?></strong>
                                    <small style="margin-left: auto; color: #666;">
                                        <?php echo formatTanggal($tanggapan['created_at']); ?>
                                    </small>
                                </div>
                                <p style="margin: 0;"><?php echo nl2br(htmlspecialchars($tanggapan['tanggapan'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Belum ada tanggapan.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>