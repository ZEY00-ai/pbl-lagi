<?php
require_once 'config/database.php';
$db = new Database();
checkLogin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// CREATE / UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_transaksi = $db->escape_string($_POST['kode_transaksi']);
    $tanggal_transaksi = $db->escape_string($_POST['tanggal_transaksi']);
    $jenis_transaksi = $db->escape_string($_POST['jenis_transaksi']);
    $barang_id = $db->escape_string($_POST['barang_id']);
    $jumlah = $db->escape_string($_POST['jumlah']);
    $harga_satuan = $db->escape_string($_POST['harga_satuan']);
    $total = $db->escape_string($_POST['total']);
    $keterangan = $db->escape_string($_POST['keterangan']);
    
    // Generate kode transaksi jika kosong
    if (empty($kode_transaksi)) {
        $prefix = $jenis_transaksi == 'pemasukan' ? 'INV-' : 'EXP-';
        $kode_transaksi = $prefix . date('Ymd') . '-' . rand(1000, 9999);
    }
    
    if (isset($_POST['id']) && $_POST['id'] > 0) {
        // Update - perlu handle stok dengan hati-hati
        $id = $_POST['id'];
        // Ambil data transaksi lama
        $old_transaksi = $db->query("SELECT * FROM transaksi WHERE id = $id")->fetch_assoc();
        
        // Update transaksi
        $sql = "UPDATE transaksi SET 
                kode_transaksi = '$kode_transaksi',
                tanggal_transaksi = '$tanggal_transaksi',
                jenis_transaksi = '$jenis_transaksi',
                barang_id = " . ($barang_id ? $barang_id : 'NULL') . ",
                jumlah = " . ($jumlah ? $jumlah : 'NULL') . ",
                total = '$total',
                keterangan = '$keterangan'
                WHERE id = $id";
        
        $message = "Transaksi berhasil diperbarui!";
    } else {
        // Create
        $user_id = $_SESSION['user_id'];
        $sql = "INSERT INTO transaksi (kode_transaksi, tanggal_transaksi, jenis_transaksi, 
                barang_id, jumlah, total, keterangan, user_id) 
                VALUES ('$kode_transaksi', '$tanggal_transaksi', '$jenis_transaksi', 
                " . ($barang_id ? $barang_id : 'NULL') . ", 
                " . ($jumlah ? $jumlah : 'NULL') . ", 
                '$total', '$keterangan', $user_id)";
        
        $message = "Transaksi berhasil ditambahkan!";
    }
    
    if ($db->query($sql)) {
        // Update stok barang jika transaksi terkait barang
        if ($barang_id && $jumlah) {
            $barang = $db->query("SELECT stok FROM barang WHERE id = $barang_id")->fetch_assoc();
            
            if ($jenis_transaksi == 'pemasukan') {
                // Penjualan - kurangi stok
                $new_stok = $barang['stok'] - $jumlah;
            } else {
                // Pembelian/pengeluaran - tambah stok
                $new_stok = $barang['stok'] + $jumlah;
            }
            
            $db->query("UPDATE barang SET stok = $new_stok WHERE id = $barang_id");
        }
        
        $_SESSION['success'] = $message;
        redirect('dashboard.php?page=transaksi');
    } else {
        $_SESSION['error'] = "Terjadi kesalahan: " . $db->conn->error;
    }
}

// DELETE
if ($action == 'delete' && $id > 0) {
    // Ambil data transaksi untuk update stok
    $transaksi = $db->query("SELECT * FROM transaksi WHERE id = $id")->fetch_assoc();
    
    $sql = "DELETE FROM transaksi WHERE id = $id";
    if ($db->query($sql)) {
        // Kembalikan stok jika transaksi terkait barang
        if ($transaksi['barang_id'] && $transaksi['jumlah']) {
            $barang = $db->query("SELECT stok FROM barang WHERE id = " . $transaksi['barang_id'])->fetch_assoc();
            
            if ($transaksi['jenis_transaksi'] == 'pemasukan') {
                // Kembalikan stok yang dijual
                $new_stok = $barang['stok'] + $transaksi['jumlah'];
            } else {
                // Kurangi stok yang dibeli
                $new_stok = $barang['stok'] - $transaksi['jumlah'];
            }
            
            $db->query("UPDATE barang SET stok = $new_stok WHERE id = " . $transaksi['barang_id']);
        }
        
        $_SESSION['success'] = "Transaksi berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Terjadi kesalahan!";
    }
    redirect('dashboard.php?page=transaksi');
}

// Form Tambah/Edit
if ($action == 'form') {
    $transaksi = null;
    if ($id > 0) {
        $result = $db->query("SELECT * FROM transaksi WHERE id = $id");
        $transaksi = $result->fetch_assoc();
    }
    
    // Ambil data barang untuk dropdown
    $barang_result = $db->query("SELECT id, nama_barang, harga_jual, stok FROM barang ORDER BY nama_barang");
    ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?php echo $id ? 'Edit' : 'Tambah'; ?> Transaksi</h5>
            <a href="dashboard.php?page=transaksi" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="" id="transaksiForm">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="kode_transaksi" class="form-label">Kode Transaksi</label>
                        <input type="text" class="form-control" id="kode_transaksi" name="kode_transaksi" 
                               value="<?php echo $transaksi['kode_transaksi'] ?? ''; ?>" 
                               placeholder="Otomatis terisi">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="tanggal_transaksi" class="form-label">Tanggal Transaksi *</label>
                        <input type="date" class="form-control" id="tanggal_transaksi" name="tanggal_transaksi" 
                               value="<?php echo $transaksi['tanggal_transaksi'] ?? date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="jenis_transaksi" class="form-label">Jenis Transaksi *</label>
                        <select class="form-select" id="jenis_transaksi" name="jenis_transaksi" required>
                            <option value="">Pilih Jenis</option>
                            <option value="pemasukan" <?php echo isset($transaksi['jenis_transaksi']) && $transaksi['jenis_transaksi'] == 'pemasukan' ? 'selected' : ''; ?>>Pemasukan (Penjualan)</option>
                            <option value="pengeluaran" <?php echo isset($transaksi['jenis_transaksi']) && $transaksi['jenis_transaksi'] == 'pengeluaran' ? 'selected' : ''; ?>>Pengeluaran (Pembelian)</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="barang_id" class="form-label">Barang (Opsional)</label>
                        <select class="form-select" id="barang_id" name="barang_id">
                            <option value="">Pilih Barang (Opsional)</option>
                            <?php while ($barang = $barang_result->fetch_assoc()): ?>
                            <option value="<?php echo $barang['id']; ?>" 
                                    data-harga="<?php echo $barang['harga_jual']; ?>"
                                    data-stok="<?php echo $barang['stok']; ?>"
                                    <?php echo isset($transaksi['barang_id']) && $transaksi['barang_id'] == $barang['id'] ? 'selected' : ''; ?>>
                                <?php echo $barang['nama_barang']; ?> (Stok: <?php echo $barang['stok']; ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="jumlah" class="form-label">Jumlah</label>
                        <input type="number" class="form-control" id="jumlah" name="jumlah" 
                               value="<?php echo $transaksi['jumlah'] ?? 1; ?>" min="1">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="harga_satuan" class="form-label">Harga Satuan (Rp)</label>
                        <input type="number" class="form-control" id="harga_satuan" name="harga_satuan" 
                               value="<?php echo isset($transaksi['total']) && $transaksi['jumlah'] ? $transaksi['total'] / $transaksi['jumlah'] : 0; ?>" 
                               step="100">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="total" class="form-label">Total (Rp) *</label>
                        <input type="number" class="form-control" id="total" name="total" 
                               value="<?php echo $transaksi['total'] ?? 0; ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="keterangan" class="form-label">Keterangan</label>
                    <textarea class="form-control" id="keterangan" name="keterangan" rows="3"><?php echo $transaksi['keterangan'] ?? ''; ?></textarea>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    <small>
                        <strong>Keterangan:</strong><br>
                        - Pemasukan: Penjualan barang/jasa (akan mengurangi stok)<br>
                        - Pengeluaran: Pembelian barang/biaya operasional (akan menambah stok)
                    </small>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan Transaksi
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <?php
    return;
}

// List Data
$search = $_GET['search'] ?? '';
$jenis = $_GET['jenis'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$where = "WHERE tanggal_transaksi BETWEEN '$start_date' AND '$end_date'";
if ($jenis) {
    $where .= " AND jenis_transaksi = '$jenis'";
}
if ($search) {
    $where .= " AND (kode_transaksi LIKE '%$search%' OR keterangan LIKE '%$search%')";
}
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Daftar Transaksi</h5>
        <div>
            <a href="dashboard.php?page=transaksi&action=form" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Tambah Transaksi
            </a>
            <a href="dashboard.php?page=transaksi&action=export&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
               class="btn btn-success btn-sm">
                <i class="bi bi-download"></i> Export
            </a>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="card-body border-bottom">
        <form method="GET" action="" class="row g-3">
            <input type="hidden" name="page" value="transaksi">
            
            <div class="col-md-3">
                <label for="start_date" class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" id="start_date" name="start_date" 
                       value="<?php echo $start_date; ?>">
            </div>
            
            <div class="col-md-3">
                <label for="end_date" class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" id="end_date" name="end_date" 
                       value="<?php echo $end_date; ?>">
            </div>
            
            <div class="col-md-3">
                <label for="jenis" class="form-label">Jenis Transaksi</label>
                <select class="form-select" id="jenis" name="jenis">
                    <option value="">Semua</option>
                    <option value="pemasukan" <?php echo $jenis == 'pemasukan' ? 'selected' : ''; ?>>Pemasukan</option>
                    <option value="pengeluaran" <?php echo $jenis == 'pengeluaran' ? 'selected' : ''; ?>>Pengeluaran</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="search" class="form-label">Cari</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo $search; ?>" placeholder="Kode/Keterangan">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="dashboard.php?page=transaksi" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="card-body">
        <?php
        // Hitung total pemasukan dan pengeluaran
        $total_result = $db->query("SELECT 
            SUM(CASE WHEN jenis_transaksi = 'pemasukan' THEN total ELSE 0 END) as total_pemasukan,
            SUM(CASE WHEN jenis_transaksi = 'pengeluaran' THEN total ELSE 0 END) as total_pengeluaran,
            COUNT(*) as jumlah_transaksi
            FROM transaksi $where");
        $totals = $total_result->fetch_assoc();
        ?>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Pemasukan</h6>
                        <h4>Rp <?php echo number_format($totals['total_pemasukan'] ?? 0, 0, ',', '.'); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Pengeluaran</h6>
                        <h4>Rp <?php echo number_format($totals['total_pengeluaran'] ?? 0, 0, ',', '.'); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Laba/Rugi</h6>
                        <h4>Rp <?php echo number_format(($totals['total_pemasukan'] ?? 0) - ($totals['total_pengeluaran'] ?? 0), 0, ',', '.'); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="50">No</th>
                        <th>Kode</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Barang</th>
                        <th>Jumlah</th>
                        <th>Total</th>
                        <th>Keterangan</th>
                        <th>User</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $db->query("SELECT t.*, b.nama_barang, u.nama_lengkap as user_nama 
                                         FROM transaksi t 
                                         LEFT JOIN barang b ON t.barang_id = b.id 
                                         LEFT JOIN users u ON t.user_id = u.id 
                                         $where ORDER BY t.tanggal_transaksi DESC, t.id DESC");
                    
                    if ($result->num_rows == 0) {
                        echo '<tr><td colspan="10" class="text-center">Tidak ada data transaksi</td></tr>';
                    }
                    
                    $no = 1;
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo $row['kode_transaksi']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['tanggal_transaksi'])); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $row['jenis_transaksi'] == 'pemasukan' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($row['jenis_transaksi']); ?>
                            </span>
                        </td>
                        <td><?php echo $row['nama_barang'] ?: '-'; ?></td>
                        <td><?php echo $row['jumlah'] ?: '-'; ?></td>
                        <td>Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></td>
                        <td><?php echo $row['keterangan'] ?: '-'; ?></td>
                        <td><?php echo $row['user_nama']; ?></td>
                        <td>
                            <a href="dashboard.php?page=transaksi&action=form&id=<?php echo $row['id']; ?>" 
                               class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="dashboard.php?page=transaksi&action=delete&id=<?php echo $row['id']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('Hapus transaksi ini?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>