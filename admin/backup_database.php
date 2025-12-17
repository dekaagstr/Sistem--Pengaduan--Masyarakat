<?php
session_start();
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

// Hanya admin yang bisa mengakses halaman ini
if (!isAdmin()) {
    $_SESSION['error'] = 'Anda tidak memiliki akses ke halaman ini';
    header('Location: ../dashboard/');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Fungsi untuk backup database
function backupDatabase($db) {
    $tables = array();
    $result = $db->query('SHOW TABLES');
    
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $return = '';
    
    foreach ($tables as $table) {
        $result = $db->query('SELECT * FROM ' . $table);
        $numFields = $result->columnCount();
        
        $return .= 'DROP TABLE IF EXISTS ' . $table . ';';
        $row2 = $db->query('SHOW CREATE TABLE ' . $table)->fetch(PDO::FETCH_NUM);
        $return .= "\n\n" . $row2[1] . ";\n\n";
        
        for ($i = 0; $i < $numFields; $i++) {
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $return .= 'INSERT INTO ' . $table . ' VALUES(';
                for ($j = 0; $j < $numFields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $return .= '"' . $row[$j] . '"' ;
                    } else {
                        $return .= '""';
                    }
                    if ($j < ($numFields - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n\n";
    }
    
    // Buat folder backup jika belum ada
    $backupDir = '../backups/';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0777, true);
    }
    
    // Simpan file backup
    $backupFile = $backupDir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $handle = fopen($backupFile, 'w+');
    fwrite($handle, $return);
    fclose($handle);
    
    return $backupFile;
}

// Proses backup database
if (isset($_POST['backup'])) {
    try {
        $backupFile = backupDatabase($db);
        $_SESSION['success'] = 'Backup database berhasil dibuat: ' . basename($backupFile);
    } catch (Exception $e) {
        $_SESSION['error'] = 'Gagal membuat backup: ' . $e->getMessage();
    }
    header('Location: backup_database.php');
    exit();
}

// Ambil daftar file backup
$backupFiles = [];
$backupDir = '../backups/';
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $filePath = $backupDir . $file;
            $backupFiles[] = [
                'name' => $file,
                'path' => $filePath,
                'size' => filesize($filePath),
                'date' => date('Y-m-d H:i:s', filemtime($filePath))
            ];
        }
    }
    // Urutkan berdasarkan tanggal terbaru
    usort($backupFiles, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Database - Sistem Pengaduan Masyarakat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
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
            line-height: 1.6;
            color: #333;
            background-color: #f5f7fb;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 15px 20px;
            background: var(--primary);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            margin: 0;
            font-size: 1.25rem;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .btn-success {
            background: var(--success);
        }
        
        .btn-danger {
            background: var(--danger);
        }
        
        .btn-warning {
            background: var(--warning);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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
            color: var(--gray);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: var(--gray);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #e6f7ee;
            color: #0d6832;
            border-left: 4px solid var(--success);
        }
        
        .alert-danger {
            background: #fde8e8;
            color: #9b2c2c;
            border-left: 4px solid var(--danger);
        }
        
        .file-size {
            font-family: monospace;
            color: var(--gray);
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-database"></i> Backup Database</h2>
                <form method="post">
                    <button type="submit" name="backup" class="btn btn-success">
                        <i class="fas fa-plus"></i> Buat Backup Baru
                    </button>
                </form>
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
                
                <h3>Daftar Backup Tersedia</h3>
                
                <?php if (count($backupFiles) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama File</th>
                                    <th>Ukuran</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backupFiles as $file): ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-file-archive"></i> 
                                            <?php echo htmlspecialchars($file['name']); ?>
                                        </td>
                                        <td class="file-size">
                                            <?php 
                                            $size = $file['size'];
                                            if ($size < 1024) {
                                                echo $size . ' B';
                                            } elseif ($size < 1048576) {
                                                echo round($size / 1024, 2) . ' KB';
                                            } else {
                                                echo round($size / 1048576, 2) . ' MB';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i:s', strtotime($file['date'])); ?></td>
                                        <td>
                                            <a href="download_backup.php?file=<?php echo urlencode($file['name']); ?>" class="btn btn-sm">
                                                <i class="fas fa-download"></i> Unduh
                                            </a>
                                            <a href="restore_database.php?file=<?php echo urlencode($file['name']); ?>" class="btn btn-warning btn-sm" onclick="return confirm('Anda yakin ingin memulihkan database dari backup ini? Semua data saat ini akan diganti.')">
                                                <i class="fas fa-undo"></i> Pulihkan
                                            </a>
                                            <a href="delete_backup.php?file=<?php echo urlencode($file['name']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Anda yakin ingin menghapus backup ini?')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center" style="padding: 40px 0;">
                        <i class="fas fa-inbox" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
                        <p class="text-muted">Belum ada backup database yang tersedia</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
