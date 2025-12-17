<?php
session_start();
if(empty($_SESSION['id_petugas']) || $_SESSION['level'] != 'admin') {
    echo "<script>
    alert('Anda harus login terlebih dahulu!');
    window.location.assign('../index.php');
    </script>";
    exit();
}

// Include file koneksi database
require_once '../config/database.php';

// Inisialisasi koneksi database
$database = new Database();
$koneksi = $database->getConnection();

if(isset($_POST['backup'])) {
    $tables = array();
    $query = $koneksi->query('SHOW TABLES');
    
    while($row = $query->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $return = '';
    
    foreach($tables as $table) {
        $result = $koneksi->query('SELECT * FROM `'.$table.'`');
        $num_fields = $result->columnCount();
        
        $return .= 'DROP TABLE IF EXISTS `'.$table.'`;';
        $createTable = $koneksi->query('SHOW CREATE TABLE `'.$table.'`');
        $row2 = $createTable->fetch(PDO::FETCH_NUM);
        $return .= "\n\n".$row2[1].";\n\n";
        
        while($row = $result->fetch(PDO::FETCH_NUM)) {
            $return .= 'INSERT INTO `'.$table.'` VALUES(';
            for($j=0; $j < $num_fields; $j++) {
                $row[$j] = addslashes($row[$j]);
                $row[$j] = str_replace("\n","\\\\n",$row[$j]);
                if (isset($row[$j])) { 
                    $return .= '"'.$row[$j].'"' ; 
                } else { 
                    $return .= '""'; 
                }
                if ($j < ($num_fields-1)) { 
                    $return .= ','; 
                }
            }
            $return .= ");\n";
        }
        $return .= "\n\n\n";
    }
    
    // Buat nama file backup
    $backup_file = 'backup-db-'.date("Y-m-d-H-i-s").'.sql';
    
    // Simpan ke file
    $handle = fopen($backup_file,'w+');
    fwrite($handle,$return);
    fclose($handle);
    
    // Download file
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.basename($backup_file));
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($backup_file));
    ob_clean();
    flush();
    readfile($backup_file);
    unlink($backup_file); // Hapus file setelah didownload
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Backup Database</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container mt-4">
        <h2><i class="bi bi-database"></i> Backup Database</h2>
        <p>Klik tombol di bawah ini untuk melakukan backup database.</p>
        
        <div class="card">
            <div class="card-body">
                <form method="post">
                    <button type="submit" name="backup" class="btn btn-primary">
                        <i class="bi bi-download"></i> Backup Database Sekarang
                    </button>
                </form>
                
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle"></i> Backup akan diunduh secara otomatis dalam format SQL.
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
