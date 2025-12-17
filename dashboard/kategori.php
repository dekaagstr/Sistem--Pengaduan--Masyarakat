<?php
require_once 'header.php';

if (!isAdmin()) {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini!";
    header("Location: index.php");
    exit();
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Proses tambah kategori
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_kategori'])) {
    $nama_kategori = $_POST['nama_kategori'];
    $deskripsi = $_POST['deskripsi'];
    
    $query = "INSERT INTO kategori (nama_kategori, deskripsi) VALUES (:nama_kategori, :deskripsi)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nama_kategori', $nama_kategori);
    $stmt->bindParam(':deskripsi', $deskripsi);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Kategori berhasil ditambahkan!";
    } else {
        $_SESSION['error'] = "Gagal menambahkan kategori!";
    }
}

// Proses edit kategori
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_kategori'])) {
    $id = $_POST['id'];
    $nama_kategori = $_POST['nama_kategori'];
    $deskripsi = $_POST['deskripsi'];
    
    $query = "UPDATE kategori SET nama_kategori = :nama_kategori, deskripsi = :deskripsi WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':nama_kategori', $nama_kategori);
    $stmt->bindParam(':deskripsi', $deskripsi);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Kategori berhasil diupdate!";
    } else {
        $_SESSION['error'] = "Gagal mengupdate kategori!";
    }
}

// Proses hapus kategori
if (isset($_GET['hapus'])) {
    $id_kategori = $_GET['hapus'];
    
    // Cek apakah kategori digunakan di pengaduan
    $query_check = "SELECT COUNT(*) as total FROM pengaduan WHERE id_kategori = :id";
    $stmt_check = $db->prepare($query_check);
    $stmt_check->bindParam(':id', $id_kategori);
    $stmt_check->execute();
    $total_pengaduan = $stmt_check->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($total_pengaduan > 0) {
        $_SESSION['error'] = "Tidak dapat menghapus kategori yang sudah digunakan dalam pengaduan!";
    } else {
        $query = "DELETE FROM kategori WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id_kategori);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Kategori berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Gagal menghapus kategori!";
        }
    }
    header("Location: kategori.php");
    exit();
}

// Get data kategori
$query = "SELECT * FROM kategori ORDER BY nama_kategori";
$stmt = $db->prepare($query);
$stmt->execute();
$kategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h2>Management Kategori</h2>
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

        <!-- Form Tambah Kategori -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>Tambah Kategori Baru</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="kategori.php">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="nama_kategori">Nama Kategori *</label>
                                <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="deskripsi">Deskripsi</label>
                                <input type="text" class="form-control" id="deskripsi" name="deskripsi">
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="tambah_kategori" value="1" class="btn btn-primary">Tambah Kategori</button>
                </form>
            </div>
        </div>

        <!-- Daftar Kategori -->
        <div class="card">
            <div class="card-header">
                <h3>Daftar Kategori</h3>
            </div>
            <div class="card-body">
                <?php if (count($kategories) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Kategori</th>
                                    <th>Deskripsi</th>
                                    <th>Tanggal Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>
                                <?php foreach ($kategories as $kategori): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($kategori['nama_kategori']); ?></td>
                                        <td><?php echo htmlspecialchars($kategori['deskripsi'] ?: '-'); ?></td>
                                        <td><?php echo formatTanggal($kategori['created_at']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-warning btn-sm" 
                                                    onclick="editKategori(<?php echo $kategori['id']; ?>, '<?php echo htmlspecialchars($kategori['nama_kategori']); ?>', '<?php echo htmlspecialchars($kategori['deskripsi']); ?>')">
                                                Edit
                                            </button>
                                            <a href="kategori.php?hapus=<?php echo $kategori['id']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                                Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Belum ada kategori.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div id="modalEdit" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px;">
        <h3>Edit Kategori</h3>
        <form method="POST" action="kategori.php" id="formEdit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label class="form-label" for="edit_nama_kategori">Nama Kategori</label>
                <input type="text" class="form-control" id="edit_nama_kategori" name="nama_kategori" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="edit_deskripsi">Deskripsi</label>
                <input type="text" class="form-control" id="edit_deskripsi" name="deskripsi">
            </div>
            <div style="display: flex; gap: 1rem;">
                <button type="submit" name="edit_kategori" value="1" class="btn btn-primary">Update</button>
                <button type="button" onclick="closeModal()" class="btn btn-warning">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function editKategori(id, nama, deskripsi) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama_kategori').value = nama;
    document.getElementById('edit_deskripsi').value = deskripsi;
    document.getElementById('modalEdit').style.display = 'block';
}

function closeModal() {
    document.getElementById('modalEdit').style.display = 'none';
}

// Close modal ketika klik di luar
window.onclick = function(event) {
    var modal = document.getElementById('modalEdit');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php require_once 'footer.php'; ?>