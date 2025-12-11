<?php
require_once 'config/database.php';
$db = new Database();

// Ambil statistik
$total_transaksi = $db->query("SELECT COUNT(*) as total FROM transaksi")->fetch_assoc()['total'];
$total_barang = $db->query("SELECT COUNT(*) as total FROM barang")->fetch_assoc()['total'];
$total_kategori = $db->query("SELECT COUNT(*) as total FROM kategori")->fetch_assoc()['total'];

// Total pemasukan bulan ini
$bulan_ini = date('Y-m');
$pemasukan = $db->query("SELECT SUM(total) as total FROM transaksi 
                         WHERE jenis_transaksi = 'pemasukan' 
                         AND DATE_FORMAT(tanggal_transaksi, '%Y-%m') = '$bulan_ini'")->fetch_assoc()['total'];

// Total pengeluaran bulan ini
$pengeluaran = $db->query("SELECT SUM(total) as total FROM transaksi 
                           WHERE jenis_transaksi = 'pengeluaran' 
                           AND DATE_FORMAT(tanggal_transaksi, '%Y-%m') = '$bulan_ini'")->fetch_assoc()['total'];

$pemasukan = $pemasukan ?: 0;
$pengeluaran = $pengeluaran ?: 0;
$laba = $pemasukan - $pengeluaran;
?>
<div class="row">
    <div class="col-md-12 mb-4">
        <h3>Dashboard Keuangan UMKM</h3>
        <p class="text-muted">Selamat datang, <?php echo $_SESSION['nama_lengkap']; ?>!</p>
    </div>
</div>

<div class="row">
    <!-- Statistik Cards -->
    <div class="col-md-3 mb-4">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Transaksi</h6>
                        <h3><?php echo $total_transaksi; ?></h3>
                    </div>
                    <div class="bg-primary rounded-circle p-3">
                        <i class="bi bi-arrow-left-right text-white fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Barang</h6>
                        <h3><?php echo $total_barang; ?></h3>
                    </div>
                    <div class="bg-success rounded-circle p-3">
                        <i class="bi bi-box text-white fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Kategori</h6>
                        <h3><?php echo $total_kategori; ?></h3>
                    </div>
                    <div class="bg-info rounded-circle p-3">
                        <i class="bi bi-tags text-white fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Laba Bulan Ini</h6>
                        <h3>Rp <?php echo number_format($laba, 0, ',', '.'); ?></h3>
                    </div>
                    <div class="bg-warning rounded-circle p-3">
                        <i class="bi bi-cash text-white fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Grafik Sederhana -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h6>Statistik Keuangan Bulan Ini</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="p-3 bg-success text-white rounded">
                            <h5>Rp <?php echo number_format($pemasukan, 0, ',', '.'); ?></h5>
                            <p>Pemasukan</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-danger text-white rounded">
                            <h5>Rp <?php echo number_format($pengeluaran, 0, ',', '.'); ?></h5>
                            <p>Pengeluaran</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-info text-white rounded">
                            <h5>Rp <?php echo number_format($laba, 0, ',', '.'); ?></h5>
                            <p>Laba Bersih</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h6>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="dashboard.php?page=transaksi&action=form" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Transaksi
                    </a>
                    <a href="dashboard.php?page=stok&action=form" class="btn btn-success">
                        <i class="bi bi-box-arrow-in-down"></i> Tambah Barang
                    </a>
                    <a href="dashboard.php?page=laporan" class="btn btn-info">
                        <i class="bi bi-file-earmark-text"></i> Lihat Laporan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Transaksi Terbaru -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6>Transaksi Terbaru</h6>
                <a href="dashboard.php?page=transaksi" class="btn btn-sm btn-primary">Lihat Semua</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th>Total</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $db->query("SELECT * FROM transaksi ORDER BY id DESC LIMIT 5");
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $row['kode_transaksi']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['tanggal_transaksi'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $row['jenis_transaksi'] == 'pemasukan' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($row['jenis_transaksi']); ?>
                                    </span>
                                </td>
                                <td>Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                                <td><?php echo substr($row['keterangan'] ?: '-', 0, 30); ?>...</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>