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
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
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
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Proses hapus kategori
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $query = "DELETE FROM kategori WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Kategori berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Gagal menghapus kategori!";
    }
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Ambil data kategori
$query = "SELECT * FROM kategori ORDER BY nama_kategori ASC";
$stmt = $db->query($query);
$kategori_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.875rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
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
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .text-center {
            text-align: center;
        }
        
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        
        .alert-danger {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        
        .action-buttons a {
            margin-right: 8px;
            color: var(--gray);
            text-decoration: none;
        }
        
        .action-buttons a:hover {
            color: var(--primary);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-header h3 {
            margin: 0;
        }
        
        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }
        
        .close:hover {
            color: var(--dark);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-tags"></i> Kelola Kategori</h2>
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <div style="margin-bottom: 20px;">
                    <button class="btn btn-primary" onclick="tambahKategori()">
                        <i class="fas fa-plus"></i> Tambah Kategori
                    </button>
                </div>
                
                <?php if (count($kategori_list) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Kategori</th>
                                    <th>Deskripsi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; ?>
                                <?php foreach ($kategori_list as $kategori): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($kategori['nama_kategori']); ?></td>
                                        <td><?php echo htmlspecialchars($kategori['deskripsi']); ?></td>
                                        <td class="action-buttons">
                                            <a href="#" onclick="editKategori(<?php echo htmlspecialchars(json_encode($kategori)); ?>); return false;" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" onclick="hapusKategori(<?php echo $kategori['id']; ?>)" title="Hapus">
                                                <i class="fas fa-trash-alt text-danger"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center" style="padding: 40px 0;">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                        <p>Tidak ada data kategori</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal Tambah Kategori -->
    <div id="modalTambah" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Tambah Kategori</h3>
                <span class="close" onclick="tutupModal('modalTambah')">&times;</span>
            </div>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="nama_kategori" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group" style="text-align: right;">
                    <button type="button" class="btn" onclick="tutupModal('modalTambah')">Batal</button>
                    <button type="submit" name="tambah_kategori" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Edit Kategori -->
    <div id="modalEdit" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Kategori</h3>
                <span class="close" onclick="tutupModal('modalEdit')">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="nama_kategori" id="edit_nama" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group" style="text-align: right;">
                    <button type="button" class="btn" onclick="tutupModal('modalEdit')">Batal</button>
                    <button type="submit" name="edit_kategori" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Fungsi untuk menampilkan modal tambah kategori
        function tambahKategori() {
            document.getElementById('modalTambah').style.display = 'flex';
        }
        
        // Fungsi untuk menampilkan modal edit kategori
        function editKategori(kategori) {
            document.getElementById('edit_id').value = kategori.id;
            document.getElementById('edit_nama').value = kategori.nama_kategori;
            document.getElementById('edit_deskripsi').value = kategori.deskripsi || '';
            document.getElementById('modalEdit').style.display = 'flex';
        }
        
        // Fungsi untuk menutup modal
        function tutupModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Fungsi untuk konfirmasi hapus kategori
        function hapusKategori(id) {
            if (confirm('Apakah Anda yakin ingin menghapus kategori ini?')) {
                window.location.href = '?hapus=' + id;
            }
        }
        
        // Tutup modal saat mengklik di luar konten modal
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
