<?php
require_once '../config/helpers.php';
require_once '../config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Filter tanggal
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Query untuk statistik
$query_stats = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
                SUM(CASE WHEN status = 'diproses' THEN 1 ELSE 0 END) as diproses,
                SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
                FROM pengaduan 
                WHERE DATE(created_at) BETWEEN :start_date AND :end_date";

if (!empty($filter_status)) {
    $query_stats .= " AND status = :status";
}

$stmt_stats = $db->prepare($query_stats);
$stmt_stats->bindParam(':start_date', $start_date);
$stmt_stats->bindParam(':end_date', $end_date);
if (!empty($filter_status)) {
    $stmt_stats->bindParam(':status', $filter_status);
}
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Query untuk data pengaduan
$query_pengaduan = "SELECT p.*, k.nama_kategori, COUNT(t.id) as jumlah_tanggapan
                    FROM pengaduan p 
                    LEFT JOIN kategori k ON p.id_kategori = k.id 
                    LEFT JOIN tanggapan t ON p.id = t.id_pengaduan
                    WHERE DATE(p.created_at) BETWEEN :start_date AND :end_date";

if (!empty($filter_status)) {
    $query_pengaduan .= " AND p.status = :status";
}

$query_pengaduan .= " GROUP BY p.id ORDER BY p.created_at DESC";

$stmt_pengaduan = $db->prepare($query_pengaduan);
$stmt_pengaduan->bindParam(':start_date', $start_date);
$stmt_pengaduan->bindParam(':end_date', $end_date);
if (!empty($filter_status)) {
    $stmt_pengaduan->bindParam(':status', $filter_status);
}
$stmt_pengaduan->execute();
$pengaduans = $stmt_pengaduan->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Pengaduan</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f4f4f4; }
        .stats { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .stat-box { border: 1px solid #ddd; padding: 15px; text-align: center; flex: 1; margin: 0 5px; }
        .footer { text-align: center; margin-top: 30px; font-size: 0.9em; color: #666; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PENGADUAN MASYARAKAT</h1>
        <h3>Periode: <?php echo formatTanggal($start_date); ?> - <?php echo formatTanggal($end_date); ?></h3>
        <p>Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <h3><?php echo $stats['total']; ?></h3>
            <p>Total Pengaduan</p>
        </div>
        <div class="stat-box">
            <h3><?php echo $stats['menunggu']; ?></h3>
            <p>Menunggu</p>
        </div>
        <div class="stat-box">
            <h3><?php echo $stats['diproses']; ?></h3>
            <p>Diproses</p>
        </div>
        <div class="stat-box">
            <h3><?php echo $stats['selesai']; ?></h3>
            <p>Selesai</p>
        </div>
        <div class="stat-box">
            <h3><?php echo $stats['ditolak']; ?></h3>
            <p>Ditolak</p>
        </div>
    </div>

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
                    <td><?php echo htmlspecialchars($pengaduan['nama_pelapor']); ?></td>
                    <td><?php echo ucfirst($pengaduan['status']); ?></td>
                    <td><?php echo $pengaduan['jumlah_tanggapan']; ?> tanggapan</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Sistem Pengaduan Layanan Masyarakat &copy; <?php echo date('Y'); ?></p>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" class="btn btn-primary">Cetak Laporan</button>
        <button onclick="window.close()" class="btn btn-warning">Tutup</button>
    </div>
</body>
</html>