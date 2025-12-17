<?php
require_once 'header.php';

if (!isAdmin()) {
    $_SESSION['error'] = "Anda tidak memiliki akses ke halaman ini!";
    header("Location: index.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/helpers.php';
$database = new Database();
$db = $database->getConnection();

// Proses tambah user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $level = $_POST['level'];
    
    // Cek username sudah ada
    $query_check = "SELECT COUNT(*) as total FROM users WHERE username = :username";
    $stmt_check = $db->prepare($query_check);
    $stmt_check->bindParam(':username', $username);
    $stmt_check->execute();
    $total_user = $stmt_check->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($total_user > 0) {
        $_SESSION['error'] = "Username sudah digunakan!";
    } else {
        $hashed_password = hashPassword($password);
        
        $query = "INSERT INTO users (username, password, nama_lengkap, level) 
                  VALUES (:username, :password, :nama_lengkap, :level)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':nama_lengkap', $nama_lengkap);
        $stmt->bindParam(':level', $level);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "User berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menambahkan user!";
        }
    }
}

// Proses edit user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $level = $_POST['level'];
    $password = $_POST['password'];
    
    // Cek username sudah ada (kecuali untuk user yang sama)
    $query_check = "SELECT COUNT(*) as total FROM users WHERE username = :username AND id != :id";
    $stmt_check = $db->prepare($query_check);
    $stmt_check->bindParam(':username', $username);
    $stmt_check->bindParam(':id', $id);
    $stmt_check->execute();
    $total_user = $stmt_check->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($total_user > 0) {
        $_SESSION['error'] = "Username sudah digunakan!";
    } else {
        if (!empty($password)) {
            $hashed_password = hashPassword($password);
            $query = "UPDATE users SET username = :username, nama_lengkap = :nama_lengkap, level = :level, password = :password WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':password', $hashed_password);
        } else {
            $query = "UPDATE users SET username = :username, nama_lengkap = :nama_lengkap, level = :level WHERE id = :id";
            $stmt = $db->prepare($query);
        }
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':nama_lengkap', $nama_lengkap);
        $stmt->bindParam(':level', $level);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "User berhasil diupdate!";
        } else {
            $_SESSION['error'] = "Gagal mengupdate user!";
        }
    }
}

// Proses hapus user
if (isset($_GET['hapus'])) {
    $id_user = $_GET['hapus'];
    
    // Cek apakah user sedang login
    if ($id_user == $_SESSION['user_id']) {
        $_SESSION['error'] = "Tidak dapat menghapus user yang sedang login!";
    } else {
        // Cek apakah user memiliki tanggapan
        $query_check = "SELECT COUNT(*) as total FROM tanggapan WHERE id_user = :id";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(':id', $id_user);
        $stmt_check->execute();
        $total_tanggapan = $stmt_check->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($total_tanggapan > 0) {
            $_SESSION['error'] = "Tidak dapat menghapus user yang sudah memberikan tanggapan!";
        } else {
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id_user);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "User berhasil dihapus!";
            } else {
                $_SESSION['error'] = "Gagal menghapus user!";
            }
        }
    }
    header("Location: users.php");
    exit();
}

// Get data users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h2>Management Users</h2>
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

        <!-- Form Tambah User -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>Tambah User Baru</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="users.php">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="username">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="password">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="nama_lengkap">Nama Lengkap *</label>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="level">Level *</label>
                                <select class="form-control" id="level" name="level" required>
                                    <option value="petugas">Petugas</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="tambah_user" value="1" class="btn btn-primary">Tambah User</button>
                </form>
            </div>
        </div>

        <!-- Daftar Users -->
        <div class="card">
            <div class="card-header">
                <h3>Daftar Users</h3>
            </div>
            <div class="card-body">
                <?php if (count($users) > 0): ?>
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Username</th>
                                    <th>Nama Lengkap</th>
                                    <th>Level</th>
                                    <th>Tanggal Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $user['level'] == 'admin' ? 'diproses' : 'menunggu'; ?>">
                                                <?php echo ucfirst($user['level']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatTanggal($user['created_at']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-warning btn-sm" 
                                                    onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['nama_lengkap']); ?>', '<?php echo $user['level']; ?>')">
                                                Edit
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="users.php?hapus=<?php echo $user['id']; ?>" 
                                                   class="btn btn-danger btn-sm" 
                                                   onclick="return confirm('Yakin ingin menghapus user ini?')">
                                                    Hapus
                                                </a>
                                            <?php else: ?>
                                                <span class="btn btn-secondary btn-sm" title="User sedang login">Hapus</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Belum ada user.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit User -->
<div id="modalEditUser" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px;">
        <h3>Edit User</h3>
        <form method="POST" action="users.php" id="formEditUser">
            <input type="hidden" name="id" id="edit_user_id">
            <div class="form-group">
                <label class="form-label" for="edit_username">Username</label>
                <input type="text" class="form-control" id="edit_username" name="username" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="edit_nama_lengkap">Nama Lengkap</label>
                <input type="text" class="form-control" id="edit_nama_lengkap" name="nama_lengkap" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="edit_level">Level</label>
                <select class="form-control" id="edit_level" name="level" required>
                    <option value="petugas">Petugas</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="edit_password">Password Baru (Kosongkan jika tidak diubah)</label>
                <input type="password" class="form-control" id="edit_password" name="password">
            </div>
            <div style="display: flex; gap: 1rem;">
                <button type="submit" name="edit_user" value="1" class="btn btn-primary">Update</button>
                <button type="button" onclick="closeUserModal()" class="btn btn-warning">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(id, username, nama_lengkap, level) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_nama_lengkap').value = nama_lengkap;
    document.getElementById('edit_level').value = level;
    document.getElementById('edit_password').value = '';
    document.getElementById('modalEditUser').style.display = 'block';
}

function closeUserModal() {
    document.getElementById('modalEditUser').style.display = 'none';
}

// Close modal ketika klik di luar
window.onclick = function(event) {
    var modal = document.getElementById('modalEditUser');
    if (event.target == modal) {
        closeUserModal();
    }
}
</script>

<?php require_once 'footer.php'; ?>