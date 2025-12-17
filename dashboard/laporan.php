<?php
require_once 'header.php';

require_once '../config/database.php';
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

// Query untuk chart data (per kategori)
$query_chart = "SELECT k.nama_kategori, COUNT(p.id) as total
                FROM pengaduan p 
                LEFT JOIN kategori k ON p.id_kategori = k.id 
                WHERE DATE(p.created_at) BETWEEN :start_date AND :end_date
                GROUP BY k.id, k.nama_kategori 
                ORDER BY total DESC";
$stmt_chart = $db->prepare($query_chart);
$stmt_chart->bindParam(':start_date', $start_date);
$stmt_chart->bindParam(':end_date', $end_date);
$stmt_chart->execute();
$chart_data = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <h2>Laporan Pengaduan</h2>
    </div>
    <div class="card-body">
        <!-- Filter Form -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>Filter Laporan</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="laporan.php">
                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="start_date">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label" for="end_date">Tanggal Selesai</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                            </div>
                        </div>
                        <div class="col-4">
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
                    <button type="submit" class="btn btn-primary">Tampilkan Laporan</button>
                    <a href="laporan.php" class="btn btn-warning">Reset</a>
                    <button type="button" onclick="cetakLaporan()" class="btn btn-success">Cetak Laporan</button>
                </form>
            </div>
        </div>

        <!-- Statistik -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>Statistik Pengaduan</h3>
                <small>Periode: <?php echo formatTanggal($start_date); ?> - <?php echo formatTanggal($end_date); ?></small>
            </div>
            <div class="card-body">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Pengaduan</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['menunggu']; ?></div>
                        <div class="stat-label">Menunggu</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['diproses']; ?></div>
                        <div class="stat-label">Diproses</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['selesai']; ?></div>
                        <div class="stat-label">Selesai</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['ditolak']; ?></div>
                        <div class="stat-label">Ditolak</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Kategori -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>Pengaduan per Kategori</h3>
            </div>
            <div class="card-body">
                <?php if (count($chart_data) > 0): ?>
                    <div style="max-width: 600px; margin: 0 auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Jumlah Pengaduan</th>
                                    <th>Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($chart_data as $data): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($data['nama_kategori']); ?></td>
                                        <td><?php echo $data['total']; ?></td>
                                        <td>
                                            <?php 
                                            $percentage = $stats['total'] > 0 ? round(($data['total'] / $stats['total']) * 100, 1) : 0;
                                            echo $percentage . '%';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Tidak ada data untuk ditampilkan.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Detail Laporan -->
        <div class="card">
            <div class="card-header">
                <h3>Detail Pengaduan</h3>
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
                                        <td><?php echo getStatusBadge($pengaduan['status']); ?></td>
                                        <td><?php echo $pengaduan['jumlah_tanggapan']; ?> tanggapan</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Tidak ada data pengaduan untuk periode yang dipilih.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function cetakLaporan() {
    var start_date = document.getElementById('start_date').value;
    var end_date = document.getElementById('end_date').value;
    var status = document.getElementById('status').value;
    
    var url = 'cetak_laporan.php?start_date=' + start_date + '&end_date=' + end_date;
    if (status) {
        url += '&status=' + status;
    }
    
    window.open(url, '_blank');
}
</script>

<?php require_once 'footer.php'; ?>