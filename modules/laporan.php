<?php
require_once 'config/database.php';
$db = new Database();
checkLogin();

$report_type = $_GET['type'] ?? 'keuangan';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$kategori_id = $_GET['kategori_id'] ?? '';

// Export to Excel
if (isset($_GET['export'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="laporan_keuangan_' . date('Ymd') . '.xls"');
    
    // Output excel content
    echo "<table border='1'>";
    echo "<tr><th colspan='4'>Laporan Keuangan UMKM</th></tr>";
    echo "<tr><th colspan='4'>Periode: $start_date s/d $end_date</th></tr>";
    // ... tambahkan konten laporan
    echo "</table>";
    exit;
}
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Laporan Keuangan</h5>
    </div>
    
    <!-- Filter Section -->
    <div class="card-body border-bottom">
        <form method="GET" action="" class="row g-3">
            <input type="hidden" name="page" value="laporan">
            
            <div class="col-md-3">
                <label for="start_date" class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" id="start_date" name="start_date" 
                       value="<?php echo $start_date; ?>" onchange="this.form.submit()">
            </div>
            
            <div class="col-md-3">
                <label for="end_date" class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" id="end_date" name="end_date" 
                       value="<?php echo $end_date; ?>" onchange="this.form.submit()">
            </div>
            
            <div class="col-md-3">
                <label for="type" class="form-label">Jenis Laporan</label>
                <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                    <option value="keuangan" <?php echo $report_type == 'keuangan' ? 'selected' : ''; ?>>Laporan Keuangan</option>
                    <option value="penjualan" <?php echo $report_type == 'penjualan' ? 'selected' : ''; ?>>Laporan Penjualan</option>
                    <option value="pembelian" <?php echo $report_type == 'pembelian' ? 'selected' : ''; ?>>Laporan Pembelian</option>
                    <option value="stok" <?php echo $report_type == 'stok' ? 'selected' : ''; ?>>Laporan Stok Barang</option>
                </select>
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                    <a href="dashboard.php?page=laporan&export=1&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=<?php echo $report_type; ?>" 
                       class="btn btn-success">
                        <i class="bi bi-download"></i> Export
                    </a>
                    <a href="dashboard.php?page=laporan" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    
    
    <div class="card-body">
        <?php if ($report_type == 'keuangan'): ?>
            <!-- Laporan Keuangan -->
            <?php
            // Total pemasukan dan pengeluaran per periode
            $keuangan_result = $db->query("SELECT 
                DATE(tanggal_transaksi) as tanggal,
                SUM(CASE WHEN jenis_transaksi = 'pemasukan' THEN total ELSE 0 END) as pemasukan,
                SUM(CASE WHEN jenis_transaksi = 'pengeluaran' THEN total ELSE 0 END) as pengeluaran
                FROM transaksi 
                WHERE tanggal_transaksi BETWEEN '$start_date' AND '$end_date'
                GROUP BY DATE(tanggal_transaksi)
                ORDER BY tanggal DESC");
            
            // Total keseluruhan
            $total_result = $db->query("SELECT 
                SUM(CASE WHEN jenis_transaksi = 'pemasukan' THEN total ELSE 0 END) as total_pemasukan,
                SUM(CASE WHEN jenis_transaksi = 'pengeluaran' THEN total ELSE 0 END) as total_pengeluaran
                FROM transaksi 
                WHERE tanggal_transaksi BETWEEN '$start_date' AND '$end_date'");
            $total = $total_result->fetch_assoc();
            
            $laba_rugi = ($total['total_pemasukan'] ?? 0) - ($total['total_pengeluaran'] ?? 0);
            ?>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title">Periode Laporan</h6>
                            <h5><?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total Pemasukan</h6>
                            <h4>Rp <?php echo number_format($total['total_pemasukan'] ?? 0, 0, ',', '.'); ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card <?php echo $laba_rugi >= 0 ? 'bg-info' : 'bg-danger'; ?> text-white">
                        <div class="card-body">
                            <h6 class="card-title">Laba/Rugi</h6>
                            <h4>Rp <?php echo number_format($laba_rugi, 0, ',', '.'); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Pemasukan</th>
                            <th>Pengeluaran</th>
                            <th>Laba/Rugi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $keuangan_result->fetch_assoc()): 
                            $daily_laba = $row['pemasukan'] - $row['pengeluaran'];
                        ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            <td class="text-success">Rp <?php echo number_format($row['pemasukan'], 0, ',', '.'); ?></td>
                            <td class="text-danger">Rp <?php echo number_format($row['pengeluaran'], 0, ',', '.'); ?></td>
                            <td class="<?php echo $daily_laba >= 0 ? 'text-info' : 'text-danger'; ?>">
                                Rp <?php echo number_format($daily_laba, 0, ',', '.'); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr>
                            <th>TOTAL</th>
                            <th class="text-success">Rp <?php echo number_format($total['total_pemasukan'] ?? 0, 0, ',', '.'); ?></th>
                            <th class="text-danger">Rp <?php echo number_format($total['total_pengeluaran'] ?? 0, 0, ',', '.'); ?></th>
                            <th class="<?php echo $laba_rugi >= 0 ? 'text-info' : 'text-danger'; ?>">
                                Rp <?php echo number_format($laba_rugi, 0, ',', '.'); ?>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
        <?php elseif ($report_type == 'penjualan'): ?>
            <!-- Laporan Penjualan -->
            <?php
            $penjualan_result = $db->query("SELECT 
                t.*, b.nama_barang, b.kode_barang, k.nama_kategori
                FROM transaksi t
                LEFT JOIN barang b ON t.barang_id = b.id
                LEFT JOIN kategori k ON b.kategori_id = k.id
                WHERE t.jenis_transaksi = 'pemasukan'
                AND t.tanggal_transaksi BETWEEN '$start_date' AND '$end_date'
                ORDER BY t.tanggal_transaksi DESC");
            
            // Total penjualan
            $total_penjualan = $db->query("SELECT 
                SUM(total) as total,
                SUM(jumlah) as total_qty
                FROM transaksi 
                WHERE jenis_transaksi = 'pemasukan'
                AND tanggal_transaksi BETWEEN '$start_date' AND '$end_date'")->fetch_assoc();
            ?>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total Penjualan</h6>
                            <h4>Rp <?php echo number_format($total_penjualan['total'] ?? 0, 0, ',', '.'); ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total Terjual</h6>
                            <h4><?php echo number_format($total_penjualan['total_qty'] ?? 0, 0, ',', '.'); ?> Unit</h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Kode Transaksi</th>
                            <th>Barang</th>
                            <th>Kategori</th>
                            <th>Jumlah</th>
                            <th>Harga Satuan</th>
                            <th>Total</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $penjualan_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal_transaksi'])); ?></td>
                            <td><?php echo $row['kode_transaksi']; ?></td>
                            <td><?php echo $row['nama_barang'] ?: '-'; ?></td>
                            <td><?php echo $row['nama_kategori'] ?: '-'; ?></td>
                            <td><?php echo $row['jumlah'] ?: '-'; ?></td>
                            <td>Rp <?php echo $row['jumlah'] ? number_format($row['total'] / $row['jumlah'], 0, ',', '.') : '-'; ?></td>
                            <td class="text-success">Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                            <td><?php echo $row['keterangan'] ?: '-'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ($report_type == 'pembelian'): ?>
            <!-- Laporan Pembelian -->
            <?php
            $pembelian_result = $db->query("SELECT 
                t.*, b.nama_barang, b.kode_barang, k.nama_kategori
                FROM transaksi t
                LEFT JOIN barang b ON t.barang_id = b.id
                LEFT JOIN kategori k ON b.kategori_id = k.id
                WHERE t.jenis_transaksi = 'pengeluaran'
                AND t.tanggal_transaksi BETWEEN '$start_date' AND '$end_date'
                ORDER BY t.tanggal_transaksi DESC");
            
            // Total pembelian
            $total_pembelian = $db->query("SELECT 
                SUM(total) as total,
                SUM(jumlah) as total_qty
                FROM transaksi 
                WHERE jenis_transaksi = 'pengeluaran'
                AND tanggal_transaksi BETWEEN '$start_date' AND '$end_date'")->fetch_assoc();
            ?>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total Pengeluaran</h6>
                            <h4>Rp <?php echo number_format($total_pembelian['total'] ?? 0, 0, ',', '.'); ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total Pembelian</h6>
                            <h4><?php echo number_format($total_pembelian['total_qty'] ?? 0, 0, ',', '.'); ?> Unit</h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Kode Transaksi</th>
                            <th>Barang</th>
                            <th>Kategori</th>
                            <th>Jumlah</th>
                            <th>Harga Satuan</th>
                            <th>Total</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $pembelian_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal_transaksi'])); ?></td>
                            <td><?php echo $row['kode_transaksi']; ?></td>
                            <td><?php echo $row['nama_barang'] ?: '-'; ?></td>
                            <td><?php echo $row['nama_kategori'] ?: '-'; ?></td>
                            <td><?php echo $row['jumlah'] ?: '-'; ?></td>
                            <td>Rp <?php echo $row['jumlah'] ? number_format($row['total'] / $row['jumlah'], 0, ',', '.') : '-'; ?></td>
                            <td class="text-danger">Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                            <td><?php echo $row['keterangan'] ?: '-'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ($report_type == 'stok'): ?>
            <!-- Laporan Stok Barang -->
            <?php
            $stok_result = $db->query("SELECT 
                b.*, k.nama_kategori,
                (SELECT SUM(jumlah) FROM transaksi WHERE barang_id = b.id AND jenis_transaksi = 'pemasukan') as total_terjual,
                (SELECT SUM(jumlah) FROM transaksi WHERE barang_id = b.id AND jenis_transaksi = 'pengeluaran') as total_dibeli
                FROM barang b
                LEFT JOIN kategori k ON b.kategori_id = k.id
                ORDER BY b.stok ASC");
            
            // Statistik stok
            $stok_stats = $db->query("SELECT 
                COUNT(*) as total_barang,
                SUM(stok) as total_stok,
                SUM(stok * harga_jual) as nilai_stok
                FROM barang")->fetch_assoc();
            ?>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total Barang</h6>
                            <h4><?php echo number_format($stok_stats['total_barang'] ?? 0, 0, ',', '.'); ?> Jenis</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title">Total Stok</h6>
                            <h4><?php echo number_format($stok_stats['total_stok'] ?? 0, 0, ',', '.'); ?> Unit</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title">Nilai Stok</h6>
                            <h4>Rp <?php echo number_format($stok_stats['nilai_stok'] ?? 0, 0, ',', '.'); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> 
                <strong>Barang dengan stok rendah:</strong>
                <?php
                $low_stock = $db->query("SELECT COUNT(*) as count FROM barang WHERE stok <= 5")->fetch_assoc();
                echo $low_stock['count'] . " barang memiliki stok ≤ 5 unit";
                ?>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Terjual</th>
                            <th>Dibeli</th>
                            <th>Harga Beli</th>
                            <th>Harga Jual</th>
                            <th>Nilai Stok</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stok_result->fetch_assoc()): 
                            $nilai_stok = $row['stok'] * $row['harga_jual'];
                            $status_class = $row['stok'] <= 5 ? 'danger' : ($row['stok'] <= 10 ? 'warning' : 'success');
                            $status_text = $row['stok'] <= 5 ? 'Rendah' : ($row['stok'] <= 10 ? 'Menipis' : 'Aman');
                        ?>
                        <tr>
                            <td><?php echo $row['kode_barang']; ?></td>
                            <td><?php echo $row['nama_barang']; ?></td>
                            <td><?php echo $row['nama_kategori'] ?: '-'; ?></td>
                            <td class="<?php echo $row['stok'] <= 5 ? 'text-danger fw-bold' : ''; ?>">
                                <?php echo number_format($row['stok'], 0, ',', '.'); ?>
                            </td>
                            <td><?php echo number_format($row['total_terjual'] ?? 0, 0, ',', '.'); ?></td>
                            <td><?php echo number_format($row['total_dibeli'] ?? 0, 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($row['harga_beli'], 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($row['harga_jual'], 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($nilai_stok, 0, ',', '.'); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Grafik Stok -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6>Ringkasan Stok Barang</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="list-group">
                                <?php
                                $kategori_stok = $db->query("SELECT 
                                    k.nama_kategori,
                                    COUNT(b.id) as jumlah_barang,
                                    SUM(b.stok) as total_stok
                                    FROM barang b
                                    LEFT JOIN kategori k ON b.kategori_id = k.id
                                    GROUP BY k.id
                                    ORDER BY total_stok DESC");
                                
                                while ($kat = $kategori_stok->fetch_assoc()):
                                ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo $kat['nama_kategori'] ?: 'Tanpa Kategori'; ?>
                                    <span class="badge bg-primary rounded-pill">
                                        <?php echo number_format($kat['total_stok'] ?? 0, 0, ',', '.'); ?> unit
                                    </span>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle"></i> Informasi Stok</h6>
                                <ul class="mb-0">
                                    <li><span class="badge bg-danger">Rendah</span>: Stok ≤ 5 unit</li>
                                    <li><span class="badge bg-warning">Menipis</span>: Stok 6-10 unit</li>
                                    <li><span class="badge bg-success">Aman</span>: Stok > 10 unit</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-success">
                                <h6><i class="bi bi-bar-chart"></i> Statistik Stok</h6>
                                <p class="mb-1">Rata-rata stok per barang: 
                                    <?php echo $stok_stats['total_barang'] ? 
                                        number_format($stok_stats['total_stok'] / $stok_stats['total_barang'], 1, ',', '.') : 0; ?> unit
                                </p>
                                <p class="mb-0">Nilai total stok: 
                                    <strong>Rp <?php echo number_format($stok_stats['nilai_stok'] ?? 0, 0, ',', '.'); ?></strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Action Buttons -->
    <div class="card-footer">
        <div class="d-flex justify-content-between">
            <div>
                <small class="text-muted">
                    Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?> oleh <?php echo $_SESSION['nama_lengkap']; ?>
                </small>
            </div>
            <div>
                <button onclick="window.print()" class="btn btn-primary btn-sm">
                    <i class="bi bi-printer"></i> Cetak Laporan
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .card-header, .card-footer, .btn, .navbar, .sidebar, .no-print {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    body {
        font-size: 12pt;
        background: white !important;
        color: black !important;
    }
    
    .table {
        font-size: 10pt;
    }
}
</style>