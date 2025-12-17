<?php
require_once 'header.php';

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Proses perubahan status - Hanya admin yang bisa mengubah status
if (isset($_POST['ubah_status'])) {
    // Cek apakah user adalah admin
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $id_pengaduan = $_POST['id_pengaduan'];
        $status = $_POST['status'];
        
        $query = "UPDATE pengaduan SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id_pengaduan);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Status pengaduan berhasil diubah!";
        } else {
            $_SESSION['error'] = "Gagal mengubah status pengaduan!";
        }
    } else {
        $_SESSION['error'] = "Anda tidak memiliki izin untuk mengubah status pengaduan!";
    }
}

// Proses hapus pengaduan
if (isset($_GET['hapus'])) {
    $id_pengaduan = $_GET['hapus'];
    
    // Cek apakah ada tanggapan
    $query_check = "SELECT COUNT(*) as total FROM tanggapan WHERE id_pengaduan = :id";
    $stmt_check = $db->prepare($query_check);
    $stmt_check->bindParam(':id', $id_pengaduan);
    $stmt_check->execute();
    $total_tanggapan = $stmt_check->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($total_tanggapan > 0) {
        $_SESSION['error'] = "Tidak dapat menghapus pengaduan yang sudah memiliki tanggapan!";
    } else {
        $query = "DELETE FROM pengaduan WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id_pengaduan);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Pengaduan berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Gagal menghapus pengaduan!";
        }
    }
    header("Location: pengaduan.php");
    exit();
}

// Filter data
$filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Query data pengaduan dengan filter
$query = "SELECT p.*, k.nama_kategori 
          FROM pengaduan p 
          LEFT JOIN kategori k ON p.id_kategori = k.id 
          WHERE 1=1";

if (!empty($filter_kategori)) {
    $query .= " AND p.id_kategori = :kategori";
}

if (!empty($filter_status)) {
    $query .= " AND p.status = :status";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);

if (!empty($filter_kategori)) {
    $stmt->bindParam(':kategori', $filter_kategori);
}

if (!empty($filter_status)) {
    $stmt->bindParam(':status', $filter_status);
}

$stmt->execute();
$pengaduans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get kategori untuk filter
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori";
$stmt_kategori = $db->prepare($query_kategori);
$stmt_kategori->execute();
$kategories = $stmt_kategori->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h2>Data Pengaduan</h2>
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

        <!-- Filter Form -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>Filter Data</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="pengaduan.php">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="kategori">Kategori</label>
                                <select class="form-control" id="kategori" name="kategori">
                                    <option value="">Semua Kategori</option>
                                    <?php foreach ($kategories as $kategori): ?>
                                        <option value="<?php echo $kategori['id']; ?>" 
                                            <?php echo ($filter_kategori == $kategori['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="status">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="menunggu" <?php echo ($filter_status == 'menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                                    <option value="diproses" <?php echo ($filter_status == 'diproses') ? 'selected' : ''; ?>>Diproses</option>
                                    <option value="selesai" <?php echo ($filter_status == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                    <option value="ditolak" <?php echo ($filter_status == 'ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="pengaduan.php" class="btn btn-warning">Reset</a>
                </form>
            </div>
        </div>

        <!-- Tabel Data Pengaduan -->
        <div class="card">
            <div class="card-header">
                <h3>Daftar Pengaduan</h3>
            </div>
            <div class="card-body">
                <?php if (count($pengaduans) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Judul</th>
                                    <th>Kategori</th>
                                    <th>Pelapor</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>
                                <?php foreach ($pengaduans as $pengaduan): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo formatTanggal($pengaduan['created_at']); ?></td>
                                        <td><?php echo htmlspecialchars($pengaduan['judul_pengaduan']); ?></td>
                                        <td><?php echo htmlspecialchars($pengaduan['nama_kategori']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($pengaduan['nama_pelapor']); ?><br>
                                            <small>NIK: <?php echo $pengaduan['nik']; ?></small>
                                        </td>
                                        <td><?php echo getStatusBadge($pengaduan['status']); ?></td>
                                        <td>
                                            <a href="detail_pengaduan.php?id=<?php echo $pengaduan['id']; ?>" class="btn btn-primary btn-sm">Detail</a>
                                            
                                            <!-- Form Ubah Status -->
                                            <form method="POST" action="pengaduan.php" style="display: inline-block; margin-top: 5px;">
                                                <input type="hidden" name="id_pengaduan" value="<?php echo $pengaduan['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" style="padding: 0.25rem; border-radius: 4px; border: 1px solid #ddd;">
                                                    <option value="menunggu" <?php echo ($pengaduan['status'] == 'menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                                                    <option value="diproses" <?php echo ($pengaduan['status'] == 'diproses') ? 'selected' : ''; ?>>Diproses</option>
                                                    <option value="selesai" <?php echo ($pengaduan['status'] == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                                    <option value="ditolak" <?php echo ($pengaduan['status'] == 'ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                                                </select>
                                                <input type="hidden" name="ubah_status" value="1">
                                            </form>
                                            
                                            <?php if (isAdmin()): ?>
                                                <a href="pengaduan.php?hapus=<?php echo $pengaduan['id']; ?>" 
                                                   class="btn btn-danger btn-sm" 
                                                   onclick="return confirm('Yakin ingin menghapus pengaduan ini?')"
                                                   style="margin-top: 5px;">
                                                    Hapus
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Tidak ada data pengaduan.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>