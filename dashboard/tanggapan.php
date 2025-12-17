<?php
require_once 'header.php';

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Proses tambah tanggapan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_tanggapan'])) {
    $id_pengaduan = $_POST['id_pengaduan'];
    $tanggapan = $_POST['tanggapan'];
    $id_user = $_SESSION['user_id'];
    
    // Debug: cek data sebelum insert
    error_log("Attempting to add tanggapan - User ID: " . $id_user . ", Pengaduan ID: " . $id_pengaduan);
    
    // Validasi: cek apakah user_id valid
    $query_check_user = "SELECT COUNT(*) as total FROM users WHERE id = :user_id";
    $stmt_check_user = $db->prepare($query_check_user);
    $stmt_check_user->bindParam(':user_id', $id_user);
    $stmt_check_user->execute();
    $user_exists = $stmt_check_user->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($user_exists == 0) {
        $_SESSION['error'] = "Error: User ID tidak valid!";
        header("Location: tanggapan.php");
        exit();
    }
    
    // Validasi: cek apakah pengaduan exists
    $query_check_pengaduan = "SELECT COUNT(*) as total FROM pengaduan WHERE id = :pengaduan_id";
    $stmt_check_pengaduan = $db->prepare($query_check_pengaduan);
    $stmt_check_pengaduan->bindParam(':pengaduan_id', $id_pengaduan);
    $stmt_check_pengaduan->execute();
    $pengaduan_exists = $stmt_check_pengaduan->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($pengaduan_exists == 0) {
        $_SESSION['error'] = "Error: Pengaduan tidak ditemukan!";
        header("Location: tanggapan.php");
        exit();
    }
    
    try {
        $query = "INSERT INTO tanggapan (id_pengaduan, id_user, tanggapan) 
                  VALUES (:id_pengaduan, :id_user, :tanggapan)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id_pengaduan', $id_pengaduan);
        $stmt->bindParam(':id_user', $id_user);
        $stmt->bindParam(':tanggapan', $tanggapan);
        
        if ($stmt->execute()) {
            // Update status pengaduan menjadi diproses jika masih menunggu
            $query_update = "UPDATE pengaduan SET status = 'diproses' WHERE id = :id AND status = 'menunggu'";
            $stmt_update = $db->prepare($query_update);
            $stmt_update->bindParam(':id', $id_pengaduan);
            $stmt_update->execute();
            
            $_SESSION['success'] = "Tanggapan berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menambahkan tanggapan!";
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = "Terjadi kesalahan database: " . $e->getMessage();
    }
    
    header("Location: tanggapan.php");
    exit();
}

// Proses ubah status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ubah_status'])) {
    $id_pengaduan = $_POST['id_pengaduan'];
    $status = $_POST['status'];
    
    try {
        $query = "UPDATE pengaduan SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id_pengaduan);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Status pengaduan berhasil diubah!";
        } else {
            $_SESSION['error'] = "Gagal mengubah status pengaduan!";
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = "Terjadi kesalahan database: " . $e->getMessage();
    }
    
    header("Location: tanggapan.php");
    exit();
}

// Get pengaduan yang perlu ditanggapi (status menunggu atau diproses)
try {
    $query = "SELECT p.*, k.nama_kategori, 
              (SELECT COUNT(*) FROM tanggapan t WHERE t.id_pengaduan = p.id) as jumlah_tanggapan
              FROM pengaduan p 
              LEFT JOIN kategori k ON p.id_kategori = k.id 
              WHERE p.status IN ('menunggu', 'diproses')
              ORDER BY p.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $pengaduans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $pengaduans = [];
    $_SESSION['error'] = "Terjadi kesalahan saat mengambil data pengaduan";
}

// Get tanggapan yang sudah diberikan oleh user ini
try {
    $query_tanggapan_saya = "SELECT t.*, p.judul_pengaduan, p.nama_pelapor 
                             FROM tanggapan t 
                             JOIN pengaduan p ON t.id_pengaduan = p.id 
                             WHERE t.id_user = :user_id 
                             ORDER BY t.created_at DESC 
                             LIMIT 10";
    $stmt_tanggapan_saya = $db->prepare($query_tanggapan_saya);
    $stmt_tanggapan_saya->bindParam(':user_id', $_SESSION['user_id']);
    $stmt_tanggapan_saya->execute();
    $tanggapan_saya = $stmt_tanggapan_saya->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $tanggapan_saya = [];
}
?>

<!-- HTML tetap sama seperti sebelumnya -->
<div class="card">
    <div class="card-header">
        <h2>Management Tanggapan</h2>
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

        <!-- Daftar Pengaduan yang Perlu Ditanggapi -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>Pengaduan yang Perlu Ditanggapi</h3>
            </div>
            <div class="card-body">
                <?php if (count($pengaduans) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Judul Pengaduan</th>
                                    <th>Kategori</th>
                                    <th>Pelapor</th>
                                    <th>Status</th>
                                    <th>Tanggapan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>
                                <?php foreach ($pengaduans as $pengaduan): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo formatTanggal($pengaduan['created_at']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($pengaduan['judul_pengaduan']); ?></strong>
                                            <br>
                                            <small><?php echo substr(htmlspecialchars($pengaduan['isi_pengaduan']), 0, 100); ?>...</small>
                                        </td>
                                        <td><?php echo htmlspecialchars($pengaduan['nama_kategori']); ?></td>
                                        <td><?php echo htmlspecialchars($pengaduan['nama_pelapor']); ?></td>
                                        <td><?php echo getStatusBadge($pengaduan['status']); ?></td>
                                        <td><?php echo $pengaduan['jumlah_tanggapan']; ?> tanggapan</td>
                                        <td>
                                            <!-- Button untuk lihat detail dan beri tanggapan -->
                                            <button type="button" class="btn btn-primary btn-sm" 
                                                    onclick="bukaModalTanggapan(<?php echo $pengaduan['id']; ?>, '<?php echo htmlspecialchars($pengaduan['judul_pengaduan']); ?>')">
                                                Beri Tanggapan
                                            </button>
                                            
                                            <!-- Form ubah status -->
                                            <form method="POST" action="tanggapan.php" style="margin-top: 5px;">
                                                <input type="hidden" name="id_pengaduan" value="<?php echo $pengaduan['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" style="padding: 0.25rem; border-radius: 4px; border: 1px solid #ddd; font-size: 0.875rem;">
                                                    <option value="menunggu" <?php echo ($pengaduan['status'] == 'menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                                                    <option value="diproses" <?php echo ($pengaduan['status'] == 'diproses') ? 'selected' : ''; ?>>Diproses</option>
                                                    <option value="selesai" <?php echo ($pengaduan['status'] == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                                    <option value="ditolak" <?php echo ($pengaduan['status'] == 'ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                                                </select>
                                                <input type="hidden" name="ubah_status" value="1">
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Tidak ada pengaduan yang perlu ditanggapi.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tanggapan yang Sudah Diberikan -->
        <div class="card">
            <div class="card-header">
                <h3>Tanggapan yang Sudah Saya Berikan</h3>
            </div>
            <div class="card-body">
                <?php if (count($tanggapan_saya) > 0): ?>
                    <?php foreach ($tanggapan_saya as $tanggapan): ?>
                        <div class="card" style="margin-bottom: 1rem;">
                            <div class="card-body">
                                <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 0.5rem;">
                                    <strong>Pengaduan: <?php echo htmlspecialchars($tanggapan['judul_pengaduan']); ?></strong>
                                    <small style="margin-left: auto; color: #666;">
                                        <?php echo formatTanggal($tanggapan['created_at']); ?>
                                    </small>
                                </div>
                                <p style="margin: 0; font-style: italic;">
                                    "<?php echo nl2br(htmlspecialchars($tanggapan['tanggapan'])); ?>"
                                </p>
                                <small style="color: #666; margin-top: 0.5rem; display: block;">
                                    Kepada: <?php echo htmlspecialchars($tanggapan['nama_pelapor']); ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Anda belum memberikan tanggapan apapun.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Beri Tanggapan -->
<div id="modalTanggapan" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto;">
        <h3>Beri Tanggapan</h3>
        <p id="judulPengaduan" style="font-weight: bold; margin-bottom: 1rem;"></p>
        
        <form method="POST" action="tanggapan.php" id="formTanggapan">
            <input type="hidden" name="id_pengaduan" id="id_pengaduan">
            
            <div class="form-group">
                <label class="form-label" for="tanggapan">Isi Tanggapan *</label>
                <textarea class="form-control" id="tanggapan" name="tanggapan" rows="6" required 
                          placeholder="Tulis tanggapan Anda untuk pengaduan ini..."></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" name="tambah_tanggapan" value="1" class="btn btn-primary">Kirim Tanggapan</button>
                <button type="button" onclick="tutupModalTanggapan()" class="btn btn-warning">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModalTanggapan(id_pengaduan, judul_pengaduan) {
    document.getElementById('id_pengaduan').value = id_pengaduan;
    document.getElementById('judulPengaduan').textContent = 'Untuk pengaduan: ' + judul_pengaduan;
    document.getElementById('tanggapan').value = '';
    document.getElementById('modalTanggapan').style.display = 'block';
}

function tutupModalTanggapan() {
    document.getElementById('modalTanggapan').style.display = 'none';
}

// Close modal ketika klik di luar
window.onclick = function(event) {
    var modal = document.getElementById('modalTanggapan');
    if (event.target == modal) {
        tutupModalTanggapan();
    }
}
</script>

<?php require_once 'footer.php'; ?>