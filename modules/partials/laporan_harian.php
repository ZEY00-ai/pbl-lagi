<?php
// Filter khusus laporan harian
$tanggal_harian = $_GET['tanggal_harian'] ?? date('Y-m-d');
$mode_harian = $_GET['mode_harian'] ?? 'detail'; // detail atau summary

// Data untuk tanggal dropdown (7 hari terakhir)
$tanggal_options = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $tanggal_options[] = $date;
}
?>

<div class="mb-4">
    <form method="GET" action="" class="row g-3">
        <input type="hidden" name="page" value="laporan">
        <input type="hidden" name="type" value="harian">
        
        <div class="col-md-4">
            <label for="tanggal_harian" class="form-label">Pilih Tanggal</label>
            <input type="date" class="form-control" id="tanggal_harian" name="tanggal_harian" 
                   value="<?php echo $tanggal_harian; ?>" max="<?php echo date('Y-m-d'); ?>"
                   onchange="this.form.submit()">
        </div>
        
        <div class="col-md-4">
            <label for="mode_harian" class="form-label">Tampilan</label>
            <select class="form-select" id="mode_harian" name="mode_harian" onchange="this.form.submit()">
                <option value="detail" <?php echo $mode_harian == 'detail' ? 'selected' : ''; ?>>Detail Transaksi</option>
                <option value="summary" <?php echo $mode_harian == 'summary' ? 'selected' : ''; ?>>Ringkasan</option>
            </select>
        </div>
        
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">
                <i class="bi bi-search"></i> Tampilkan
            </button>
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-calendar"></i> Pilihan Cepat
                </button>
                <ul class="dropdown-menu">
                    <?php foreach ($tanggal_options as $tgl): ?>
                    <li>
                        <a class="dropdown-item" href="?page=laporan&type=harian&tanggal_harian=<?php echo $tgl; ?>">
                            <?php echo date('d/m/Y', strtotime($tgl)); ?>
                            <?php if ($tgl == date('Y-m-d')) echo ' (Hari ini)'; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </form>
</div>

<?php
// Query data transaksi harian
$tanggal_format = date('d F Y', strtotime($tanggal_harian));

// Total untuk hari ini
$total_harian = $db->query("SELECT 
    SUM(CASE WHEN jenis_transaksi = 'pemasukan' THEN total ELSE 0 END) as total_pemasukan,
    SUM(CASE WHEN jenis_transaksi = 'pengeluaran' THEN total ELSE 0 END) as total_pengeluaran,
    COUNT(*) as jumlah_transaksi
    FROM transaksi 
    WHERE DATE(tanggal_transaksi) = '$tanggal_harian'")->fetch_assoc();

$laba_harian = ($total_harian['total_pemasukan'] ?? 0) - ($total_harian['total_pengeluaran'] ?? 0);

// Data transaksi per jam
$transaksi_per_jam = $db->query("SELECT 
    HOUR(created_at) as jam,
    COUNT(*) as jumlah,
    SUM(CASE WHEN jenis_transaksi = 'pemasukan' THEN total ELSE 0 END) as pemasukan,
    SUM(CASE WHEN jenis_transaksi = 'pengeluaran' THEN total ELSE 0 END) as pengeluaran
    FROM transaksi 
    WHERE DATE(tanggal_transaksi) = '$tanggal_harian'
    GROUP BY HOUR(created_at)
    ORDER BY jam");

// Top produk terjual hari ini
$top_produk = $db->query("SELECT 
    b.nama_barang,
    SUM(t.jumlah) as total_terjual,
    SUM(t.total) as total_penjualan
    FROM transaksi t
    JOIN barang b ON t.barang_id = b.id
    WHERE t.jenis_transaksi = 'pemasukan'
    AND DATE(t.tanggal_transaksi) = '$tanggal_harian'
    GROUP BY t.barang_id
    ORDER BY total_terjual DESC
    LIMIT 5");

// Kategori pengeluaran
$kategori_pengeluaran = $db->query("SELECT 
    COALESCE(k.nama_kategori, 'Lainnya') as kategori,
    SUM(t.total) as total
    FROM transaksi t
    LEFT JOIN barang b ON t.barang_id = b.id
    LEFT JOIN kategori k ON b.kategori_id = k.id
    WHERE t.jenis_transaksi = 'pengeluaran'
    AND DATE(t.tanggal_transaksi) = '$tanggal_harian'
    GROUP BY kategori
    ORDER BY total DESC");
?>

<!-- Statistik Harian -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h4 class="card-title mb-1">Laporan Harian</h4>
                <h5 class="card-subtitle mb-3"><?php echo $tanggal_format; ?></h5>
                <div class="row">
                    <div class="col-md-3">
                        <div class="p-2">
                            <h6>Total Transaksi</h6>
                            <h3><?php echo number_format($total_harian['jumlah_transaksi'] ?? 0, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-2">
                            <h6>Pemasukan</h6>
                            <h3 class="text-success">Rp <?php echo number_format($total_harian['total_pemasukan'] ?? 0, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-2">
                            <h6>Pengeluaran</h6>
                            <h3 class="text-danger">Rp <?php echo number_format($total_harian['total_pengeluaran'] ?? 0, 0, ',', '.'); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-2">
                            <h6>Laba Harian</h6>
                            <h3 class="<?php echo $laba_harian >= 0 ? 'text-warning' : 'text-danger'; ?>">
                                Rp <?php echo number_format($laba_harian, 0, ',', '.'); ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($mode_harian == 'detail'): ?>
<!-- Detail Transaksi Harian -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6>Detail Transaksi <?php echo $tanggal_format; ?></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu</th>
                                <th>Kode</th>
                                <th>Jenis</th>
                                <th>Barang</th>
                                <th>Jumlah</th>
                                <th>Total</th>
                                <th>Keterangan</th>
                                <th>Kasir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $detail_transaksi = $db->query("SELECT 
                                t.*,
                                b.nama_barang,
                                u.nama_lengkap as kasir,
                                TIME(t.created_at) as waktu
                                FROM transaksi t
                                LEFT JOIN barang b ON t.barang_id = b.id
                                LEFT JOIN users u ON t.user_id = u.id
                                WHERE DATE(t.tanggal_transaksi) = '$tanggal_harian'
                                ORDER BY t.created_at DESC");
                            
                            if ($detail_transaksi->num_rows == 0) {
                                echo '<tr><td colspan="8" class="text-center">Tidak ada transaksi pada tanggal ini</td></tr>';
                            }
                            
                            while ($transaksi = $detail_transaksi->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $transaksi['waktu']; ?></td>
                                <td><?php echo $transaksi['kode_transaksi']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $transaksi['jenis_transaksi'] == 'pemasukan' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($transaksi['jenis_transaksi']); ?>
                                    </span>
                                </td>
                                <td><?php echo $transaksi['nama_barang'] ?: '-'; ?></td>
                                <td><?php echo $transaksi['jumlah'] ?: '-'; ?></td>
                                <td>Rp <?php echo number_format($transaksi['total'], 0, ',', '.'); ?></td>
                                <td><?php echo $transaksi['keterangan'] ?: '-'; ?></td>
                                <td><?php echo $transaksi['kasir']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Grafik Transaksi Per Jam -->
        <div class="card mb-4">
            <div class="card-header">
                <h6>Transaksi Per Jam</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Jam</th>
                                <th>Jumlah</th>
                                <th>Pemasukan</th>
                                <th>Pengeluaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $jam_data = [];
                            while ($jam = $transaksi_per_jam->fetch_assoc()) {
                                $jam_data[$jam['jam']] = $jam;
                            }
                            
                            for ($jam = 0; $jam < 24; $jam++):
                                $data = $jam_data[$jam] ?? ['jumlah' => 0, 'pemasukan' => 0, 'pengeluaran' => 0];
                            ?>
                            <tr>
                                <td><?php echo sprintf('%02d:00', $jam); ?></td>
                                <td><?php echo $data['jumlah']; ?></td>
                                <td class="text-success">
                                    <?php if ($data['pemasukan'] > 0): ?>
                                    Rp <?php echo number_format($data['pemasukan'], 0, ',', '.'); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-danger">
                                    <?php if ($data['pengeluaran'] > 0): ?>
                                    Rp <?php echo number_format($data['pengeluaran'], 0, ',', '.'); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Top Produk -->
        <div class="card">
            <div class="card-header">
                <h6>Top 5 Produk Terjual</h6>
            </div>
            <div class="card-body">
                <?php if ($top_produk->num_rows > 0): ?>
                <div class="list-group">
                    <?php while ($produk = $top_produk->fetch_assoc()): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?php echo $produk['nama_barang']; ?></h6>
                            <small><?php echo number_format($produk['total_terjual'], 0, ',', '.'); ?> unit</small>
                        </div>
                        <span class="badge bg-primary rounded-pill">
                            Rp <?php echo number_format($produk['total_penjualan'], 0, ',', '.'); ?>
                        </span>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">Tidak ada data penjualan</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Ringkasan Harian -->
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h6>Ringkasan Keuangan</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h6>Rata-rata Pemasukan per Transaksi</h6>
                                <h4>
                                    <?php
                                    $avg_pemasukan = ($total_harian['jumlah_transaksi'] > 0) ? 
                                        ($total_harian['total_pemasukan'] / $total_harian['jumlah_transaksi']) : 0;
                                    echo 'Rp ' . number_format($avg_pemasukan, 0, ',', '.');
                                    ?>
                                </h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h6>Rata-rata Pengeluaran per Transaksi</h6>
                                <h4>
                                    <?php
                                    $avg_pengeluaran = ($total_harian['jumlah_transaksi'] > 0) ? 
                                        ($total_harian['total_pengeluaran'] / $total_harian['jumlah_transaksi']) : 0;
                                    echo 'Rp ' . number_format($avg_pengeluaran, 0, ',', '.');
                                    ?>
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th width="60%">Item</th>
                            <th class="text-end">Jumlah</th>
                        </tr>
                        <tr>
                            <td>Total Transaksi</td>
                            <td class="text-end">
                                <?php echo number_format($total_harian['jumlah_transaksi'] ?? 0, 0, ',', '.'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Total Pemasukan</td>
                            <td class="text-end text-success">
                                Rp <?php echo number_format($total_harian['total_pemasukan'] ?? 0, 0, ',', '.'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Total Pengeluaran</td>
                            <td class="text-end text-danger">
                                Rp <?php echo number_format($total_harian['total_pengeluaran'] ?? 0, 0, ',', '.'); ?>
                            </td>
                        </tr>
                        <tr class="table-<?php echo $laba_harian >= 0 ? 'info' : 'danger'; ?>">
                            <td><strong>Laba/Rugi Bersih</strong></td>
                            <td class="text-end fw-bold">
                                Rp <?php echo number_format($laba_harian, 0, ',', '.'); ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Kategori Pengeluaran -->
        <div class="card">
            <div class="card-header">
                <h6>Kategori Pengeluaran</h6>
            </div>
            <div class="card-body">
                <?php if ($kategori_pengeluaran->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="text-end">Total</th>
                                <th width="100">Persentase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_pengeluaran_kategori = $total_harian['total_pengeluaran'] ?? 1;
                            while ($kategori = $kategori_pengeluaran->fetch_assoc()):
                                $percentage = ($kategori['total'] / $total_pengeluaran_kategori) * 100;
                            ?>
                            <tr>
                                <td><?php echo $kategori['kategori']; ?></td>
                                <td class="text-end">Rp <?php echo number_format($kategori['total'], 0, ',', '.'); ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-danger" role="progressbar" 
                                             style="width: <?php echo $percentage; ?>%">
                                            <?php echo number_format($percentage, 1); ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">Tidak ada pengeluaran</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <!-- Perbandingan dengan Hari Sebelumnya -->
        <div class="card mb-4">
            <div class="card-header">
                <h6>Perbandingan dengan Hari Sebelumnya</h6>
            </div>
            <div class="card-body">
                <?php
                $kemarin = date('Y-m-d', strtotime($tanggal_harian . ' -1 day'));
                $total_kemarin = $db->query("SELECT 
                    SUM(CASE WHEN jenis_transaksi = 'pemasukan' THEN total ELSE 0 END) as total_pemasukan,
                    SUM(CASE WHEN jenis_transaksi = 'pengeluaran' THEN total ELSE 0 END) as total_pengeluaran,
                    COUNT(*) as jumlah_transaksi
                    FROM transaksi 
                    WHERE DATE(tanggal_transaksi) = '$kemarin'")->fetch_assoc();
                
                $laba_kemarin = ($total_kemarin['total_pemasukan'] ?? 0) - ($total_kemarin['total_pengeluaran'] ?? 0);
                
                // Hitung perubahan
                $perubahan_pemasukan = $total_harian['total_pemasukan'] - $total_kemarin['total_pemasukan'];
                $perubahan_pengeluaran = $total_harian['total_pengeluaran'] - $total_kemarin['total_pengeluaran'];
                $perubahan_laba = $laba_harian - $laba_kemarin;
                
                function getChangeClass($value) {
                    if ($value > 0) return 'text-success';
                    if ($value < 0) return 'text-danger';
                    return 'text-muted';
                }
                
                function getChangeIcon($value) {
                    if ($value > 0) return '<i class="bi bi-arrow-up-right"></i>';
                    if ($value < 0) return '<i class="bi bi-arrow-down-right"></i>';
                    return '';
                }
                ?>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Metrik</th>
                                <th class="text-end"><?php echo date('d/m', strtotime($tanggal_harian)); ?></th>
                                <th class="text-end"><?php echo date('d/m', strtotime($kemarin)); ?></th>
                                <th class="text-end">Perubahan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Transaksi</td>
                                <td class="text-end"><?php echo number_format($total_harian['jumlah_transaksi'] ?? 0, 0, ',', '.'); ?></td>
                                <td class="text-end"><?php echo number_format($total_kemarin['jumlah_transaksi'] ?? 0, 0, ',', '.'); ?></td>
                                <td class="text-end <?php echo getChangeClass($total_harian['jumlah_transaksi'] - $total_kemarin['jumlah_transaksi']); ?>">
                                    <?php 
                                    $change = $total_harian['jumlah_transaksi'] - $total_kemarin['jumlah_transaksi'];
                                    echo getChangeIcon($change) . ' ' . number_format($change, 0, ',', '.');
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Pemasukan</td>
                                <td class="text-end">Rp <?php echo number_format($total_harian['total_pemasukan'] ?? 0, 0, ',', '.'); ?></td>
                                <td class="text-end">Rp <?php echo number_format($total_kemarin['total_pemasukan'] ?? 0, 0, ',', '.'); ?></td>
                                <td class="text-end <?php echo getChangeClass($perubahan_pemasukan); ?>">
                                    <?php 
                                    echo getChangeIcon($perubahan_pemasukan) . ' Rp ' . number_format(abs($perubahan_pemasukan), 0, ',', '.');
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Pengeluaran</td>
                                <td class="text-end">Rp <?php echo number_format($total_harian['total_pengeluaran'] ?? 0, 0, ',', '.'); ?></td>
                                <td class="text-end">Rp <?php echo number_format($total_kemarin['total_pengeluaran'] ?? 0, 0, ',', '.'); ?></td>
                                <td class="text-end <?php echo getChangeClass(-$perubahan_pengeluaran); ?>">
                                    <?php 
                                    echo getChangeIcon(-$perubahan_pengeluaran) . ' Rp ' . number_format(abs($perubahan_pengeluaran), 0, ',', '.');
                                    ?>
                                </td>
                            </tr>
                            <tr class="table-<?php echo $laba_harian >= 0 ? 'info' : 'danger'; ?>">
                                <td><strong>Laba/Rugi</strong></td>
                                <td class="text-end fw-bold">Rp <?php echo number_format($laba_harian, 0, ',', '.'); ?></td>
                                <td class="text-end">Rp <?php echo number_format($laba_kemarin, 0, ',', '.'); ?></td>
                                <td class="text-end fw-bold <?php echo getChangeClass($perubahan_laba); ?>">
                                    <?php 
                                    echo getChangeIcon($perubahan_laba) . ' Rp ' . number_format(abs($perubahan_laba), 0, ',', '.');
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Analisis Harian -->
        <div class="card">
            <div class="card-header">
                <h6>Analisis Harian</h6>
            </div>
            <div class="card-body">
                <?php
                // Hitung beberapa metrik analisis
                $transaksi_per_jam_avg = ($total_harian['jumlah_transaksi'] > 0) ? 
                    $total_harian['jumlah_transaksi'] / 24 : 0;
                
                $pemasukan_per_transaksi = ($total_harian['jumlah_transaksi'] > 0) ? 
                    $total_harian['total_pemasukan'] / $total_harian['jumlah_transaksi'] : 0;
                
                $margin_keuntungan = ($total_harian['total_pemasukan'] > 0) ? 
                    ($laba_harian / $total_harian['total_pemasukan']) * 100 : 0;
                ?>
                
                <div class="list-group">
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Transaksi per Jam (Rata-rata)</span>
                            <span class="badge bg-primary rounded-pill">
                                <?php echo number_format($transaksi_per_jam_avg, 2, ',', '.'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Rata-rata Nilai per Transaksi</span>
                            <span class="badge bg-success rounded-pill">
                                Rp <?php echo number_format($pemasukan_per_transaksi, 0, ',', '.'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Margin Keuntungan</span>
                            <span class="badge bg-<?php echo $margin_keuntungan >= 0 ? 'info' : 'danger'; ?> rounded-pill">
                                <?php echo number_format($margin_keuntungan, 1, ',', '.'); ?>%
                            </span>
                        </div>
                    </div>
                    
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Efisiensi Operasional</span>
                            <span class="badge bg-<?php echo ($total_harian['total_pengeluaran'] / ($total_harian['total_pemasukan'] ?: 1) * 100) < 50 ? 'success' : 'warning'; ?> rounded-pill">
                                <?php echo number_format(($total_harian['total_pengeluaran'] / ($total_harian['total_pemasukan'] ?: 1) * 100), 1, ',', '.'); ?>%
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Rekomendasi -->
                <div class="mt-3">
                    <h6>Rekomendasi:</h6>
                    <ul class="list-unstyled">
                        <?php
                        $rekomendasi = [];
                        
                        if ($laba_harian < 0) {
                            $rekomendasi[] = '💡 <strong>Perhatian:</strong> Kerugian hari ini. Tinjau pengeluaran operasional.';
                        }
                        
                        if ($total_harian['jumlah_transaksi'] < 5) {
                            $rekomendasi[] = '📊 <strong>Saran:</strong> Volume transaksi rendah. Pertimbangkan promosi.';
                        }
                        
                        if (($total_harian['total_pengeluaran'] / ($total_harian['total_pemasukan'] ?: 1) * 100) > 70) {
                            $rekomendasi[] = '💰 <strong>Efisiensi:</strong> Rasio pengeluaran terhadap pemasukan tinggi (>70%).';
                        }
                        
                        if (empty($rekomendasi)) {
                            $rekomendasi[] = '✅ <strong>Bagus!</strong> Performa hari ini baik. Pertahankan!';
                        }
                        
                        foreach ($rekomendasi as $rek):
                        ?>
                        <li class="mb-1"><?php echo $rek; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Tombol Aksi -->
<div class="mt-4">
    <div class="d-flex justify-content-between">
        <div>
            <a href="dashboard.php?page=laporan&type=harian&tanggal_harian=<?php echo date('Y-m-d', strtotime($tanggal_harian . ' -1 day')); ?>&mode_harian=<?php echo $mode_harian; ?>" 
               class="btn btn-outline-primary">
                <i class="bi bi-chevron-left"></i> Sebelumnya
            </a>
            <a href="dashboard.php?page=laporan&type=harian&tanggal_harian=<?php echo date('Y-m-d'); ?>&mode_harian=<?php echo $mode_harian; ?>" 
               class="btn btn-outline-secondary">
                <i class="bi bi-calendar-day"></i> Hari Ini
            </a>
            <?php
            $tomorrow = date('Y-m-d', strtotime($tanggal_harian . ' +1 day'));
            $tomorrow_disabled = $tomorrow > date('Y-m-d') ? 'disabled' : '';
            ?>
            <a href="dashboard.php?page=laporan&type=harian&tanggal_harian=<?php echo $tomorrow; ?>&mode_harian=<?php echo $mode_harian; ?>" 
               class="btn btn-outline-primary <?php echo $tomorrow_disabled; ?>">
                Berikutnya <i class="bi bi-chevron-right"></i>
            </a>
        </div>
        
        <div>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Cetak Laporan Harian
            </button>
            <a href="dashboard.php?page=laporan&type=harian&export=1&tanggal_harian=<?php echo $tanggal_harian; ?>" 
               class="btn btn-success">
                <i class="bi bi-download"></i> Export Excel
            </a>
        </div>
    </div>
</div>

<!-- Script untuk Chart (opsional) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const urlParams = new URLSearchParams(window.location.search);
    const reportType = urlParams.get('type') || 'keuangan';
    
    // Set active tab based on URL
    const tabs = document.querySelectorAll('.nav-link');
    tabs.forEach(tab => {
        tab.classList.remove('active');
        if (tab.id === reportType + '-tab') {
            tab.classList.add('active');
        }
    });
    
    // Handle tab click - update URL without reload
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            const tabId = this.id.replace('-tab', '');
            const url = new URL(window.location);
            url.searchParams.set('type', tabId);
            window.history.pushState({}, '', url);
        });
    });
});
</script>