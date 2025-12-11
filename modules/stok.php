<?php
require_once 'config/database.php';
$db = new Database();
checkLogin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// CREATE / UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_barang = $db->escape_string($_POST['kode_barang']);
    $nama_barang = $db->escape_string($_POST['nama_barang']);
    $kategori_id = $db->escape_string($_POST['kategori_id']);
    $stok = $db->escape_string($_POST['stok']);
    $harga_beli = str_replace('.', '', $db->escape_string($_POST['harga_beli']));
    $harga_jual = str_replace('.', '', $db->escape_string($_POST['harga_jual']));
    
    // Validasi harga
    if ($harga_jual <= $harga_beli) {
        $_SESSION['error'] = "Harga jual harus lebih besar dari harga beli!";
        redirect('dashboard.php?page=stok&action=form' . ($id ? '&id=' . $id : ''));
    }
    
    if (isset($_POST['id']) && $_POST['id'] > 0) {
        // Update
        $id = $_POST['id'];
        
        // Cek kode barang unik (kecuali untuk dirinya sendiri)
        $check = $db->query("SELECT id FROM barang WHERE kode_barang = '$kode_barang' AND id != $id");
        if ($check->num_rows > 0) {
            $_SESSION['error'] = "Kode barang sudah digunakan!";
            redirect('dashboard.php?page=stok&action=form&id=' . $id);
        }
        
        $sql = "UPDATE barang SET 
                kode_barang = '$kode_barang',
                nama_barang = '$nama_barang',
                kategori_id = " . ($kategori_id ? $kategori_id : 'NULL') . ",
                stok = '$stok',
                harga_beli = '$harga_beli',
                harga_jual = '$harga_jual'
                WHERE id = $id";
        $message = "Barang berhasil diperbarui!";
    } else {
        // Create
        // Cek kode barang unik
        $check = $db->query("SELECT id FROM barang WHERE kode_barang = '$kode_barang'");
        if ($check->num_rows > 0) {
            $_SESSION['error'] = "Kode barang sudah digunakan!";
            redirect('dashboard.php?page=stok&action=form');
        }
        
        $sql = "INSERT INTO barang (kode_barang, nama_barang, kategori_id, stok, harga_beli, harga_jual) 
                VALUES ('$kode_barang', '$nama_barang', " . ($kategori_id ? $kategori_id : 'NULL') . ", 
                '$stok', '$harga_beli', '$harga_jual')";
        $message = "Barang berhasil ditambahkan!";
    }
    
    if ($db->query($sql)) {
        $_SESSION['success'] = $message;
        redirect('dashboard.php?page=stok');
    } else {
        $_SESSION['error'] = "Terjadi kesalahan: " . $db->conn->error;
    }
}

// DELETE
if ($action == 'delete' && $id > 0) {
    // Cek apakah barang sudah digunakan di transaksi
    $check = $db->query("SELECT id FROM transaksi WHERE barang_id = $id LIMIT 1");
    if ($check->num_rows > 0) {
        $_SESSION['error'] = "Tidak dapat menghapus barang yang sudah digunakan dalam transaksi!";
    } else {
        $sql = "DELETE FROM barang WHERE id = $id";
        if ($db->query($sql)) {
            $_SESSION['success'] = "Barang berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Terjadi kesalahan!";
        }
    }
    redirect('dashboard.php?page=stok');
}

// IMPORT FROM EXCEL (Sederhana)
if ($action == 'import' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] == 0) {
        $file = $_FILES['file_excel']['tmp_name'];
        
        // Baca file CSV sederhana
        if (($handle = fopen($file, "r")) !== FALSE) {
            $row = 0;
            $success_count = 0;
            $error_count = 0;
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row++;
                if ($row == 1) continue; // Skip header
                
                if (count($data) >= 6) {
                    $kode_barang = $db->escape_string($data[0]);
                    $nama_barang = $db->escape_string($data[1]);
                    $kategori_nama = $db->escape_string($data[2]);
                    $stok = (int)$data[3];
                    $harga_beli = (int)str_replace('.', '', $data[4]);
                    $harga_jual = (int)str_replace('.', '', $data[5]);
                    
                    // Cari kategori ID
                    $kategori_id = null;
                    if (!empty($kategori_nama)) {
                        $kat_result = $db->query("SELECT id FROM kategori WHERE nama_kategori = '$kategori_nama'");
                        if ($kat_result->num_rows > 0) {
                            $kategori = $kat_result->fetch_assoc();
                            $kategori_id = $kategori['id'];
                        }
                    }
                    
                    // Cek apakah barang sudah ada
                    $check = $db->query("SELECT id FROM barang WHERE kode_barang = '$kode_barang'");
                    
                    if ($check->num_rows > 0) {
                        // Update jika sudah ada
                        $barang = $check->fetch_assoc();
                        $sql = "UPDATE barang SET 
                                nama_barang = '$nama_barang',
                                kategori_id = " . ($kategori_id ? $kategori_id : 'NULL') . ",
                                stok = stok + $stok,
                                harga_beli = '$harga_beli',
                                harga_jual = '$harga_jual'
                                WHERE id = " . $barang['id'];
                    } else {
                        // Insert baru
                        $sql = "INSERT INTO barang (kode_barang, nama_barang, kategori_id, stok, harga_beli, harga_jual) 
                                VALUES ('$kode_barang', '$nama_barang', " . ($kategori_id ? $kategori_id : 'NULL') . ", 
                                '$stok', '$harga_beli', '$harga_jual')";
                    }
                    
                    if ($db->query($sql)) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
            }
            fclose($handle);
            
            $_SESSION['success'] = "Import berhasil! $success_count barang berhasil diimport" . 
                                  ($error_count > 0 ? ", $error error" : "");
        }
    } else {
        $_SESSION['error'] = "File tidak valid atau kosong!";
    }
    redirect('dashboard.php?page=stok');
}

// EXPORT TO EXCEL
if ($action == 'export') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="data_stok_' . date('Ymd') . '.xls"');
    
    $result = $db->query("SELECT 
        b.*, 
        k.nama_kategori,
        (SELECT SUM(jumlah) FROM transaksi WHERE barang_id = b.id AND jenis_transaksi = 'pemasukan') as total_terjual,
        (SELECT SUM(jumlah) FROM transaksi WHERE barang_id = b.id AND jenis_transaksi = 'pengeluaran') as total_dibeli
        FROM barang b
        LEFT JOIN kategori k ON b.kategori_id = k.id
        ORDER BY b.nama_barang");
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid black; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h2>Data Stok Barang</h2>
        <p>Tanggal Export: <?php echo date('d/m/Y H:i:s'); ?></p>
        
        <table>
            <tr>
                <th>No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Stok</th>
                <th>Harga Beli</th>
                <th>Harga Jual</th>
                <th>Terjual</th>
                <th>Dibeli</th>
                <th>Nilai Stok</th>
            </tr>
            <?php
            $no = 1;
            $total_nilai_stok = 0;
            $total_stok = 0;
            while ($row = $result->fetch_assoc()):
                $nilai_stok = $row['stok'] * $row['harga_jual'];
                $total_nilai_stok += $nilai_stok;
                $total_stok += $row['stok'];
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo $row['kode_barang']; ?></td>
                <td><?php echo $row['nama_barang']; ?></td>
                <td><?php echo $row['nama_kategori'] ?: '-'; ?></td>
                <td><?php echo number_format($row['stok'], 0, ',', '.'); ?></td>
                <td>Rp <?php echo number_format($row['harga_beli'], 0, ',', '.'); ?></td>
                <td>Rp <?php echo number_format($row['harga_jual'], 0, ',', '.'); ?></td>
                <td><?php echo number_format($row['total_terjual'] ?? 0, 0, ',', '.'); ?></td>
                <td><?php echo number_format($row['total_dibeli'] ?? 0, 0, ',', '.'); ?></td>
                <td>Rp <?php echo number_format($nilai_stok, 0, ',', '.'); ?></td>
            </tr>
            <?php endwhile; ?>
            <tr style="font-weight: bold; background-color: #e8f4f8;">
                <td colspan="4">TOTAL</td>
                <td><?php echo number_format($total_stok, 0, ',', '.'); ?></td>
                <td colspan="3"></td>
                <td colspan="2">Rp <?php echo number_format($total_nilai_stok, 0, ',', '.'); ?></td>
            </tr>
        </table>
    </body>
    </html>
    <?php
    exit;
}

// Form Tambah/Edit
if ($action == 'form') {
    $barang = null;
    if ($id > 0) {
        $result = $db->query("SELECT * FROM barang WHERE id = $id");
        $barang = $result->fetch_assoc();
    }
    
    // Ambil data kategori untuk dropdown
    $kategori_result = $db->query("SELECT * FROM kategori ORDER BY nama_kategori");
    ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?php echo $id ? 'Edit' : 'Tambah'; ?> Barang</h5>
            <a href="dashboard.php?page=stok" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="" id="barangForm">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="kode_barang" class="form-label">Kode Barang *</label>
                        <input type="text" class="form-control" id="kode_barang" name="kode_barang" 
                               value="<?php echo $barang['kode_barang'] ?? ''; ?>" required
                               placeholder="Contoh: BRG001">
                        <small class="text-muted">Kode unik untuk identifikasi barang</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="nama_barang" class="form-label">Nama Barang *</label>
                        <input type="text" class="form-control" id="nama_barang" name="nama_barang" 
                               value="<?php echo $barang['nama_barang'] ?? ''; ?>" required
                               placeholder="Nama lengkap barang">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="kategori_id" class="form-label">Kategori</label>
                        <select class="form-select" id="kategori_id" name="kategori_id">
                            <option value="">Pilih Kategori (Opsional)</option>
                            <?php while ($kategori = $kategori_result->fetch_assoc()): ?>
                            <option value="<?php echo $kategori['id']; ?>"
                                <?php echo isset($barang['kategori_id']) && $barang['kategori_id'] == $kategori['id'] ? 'selected' : ''; ?>>
                                <?php echo $kategori['nama_kategori']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="stok" class="form-label">Stok Awal *</label>
                        <input type="number" class="form-control" id="stok" name="stok" 
                               value="<?php echo $barang['stok'] ?? 0; ?>" required min="0" step="1">
                        <small class="text-muted">Jumlah barang di gudang saat ini</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="harga_beli" class="form-label">Harga Beli (Rp) *</label>
                        <input type="text" class="form-control money-input" id="harga_beli" name="harga_beli" 
                               value="<?php echo isset($barang['harga_beli']) ? number_format($barang['harga_beli'], 0, ',', '.') : ''; ?>" 
                               required>
                        <small class="text-muted">Harga ketika membeli barang</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="harga_jual" class="form-label">Harga Jual (Rp) *</label>
                        <input type="text" class="form-control money-input" id="harga_jual" name="harga_jual" 
                               value="<?php echo isset($barang['harga_jual']) ? number_format($barang['harga_jual'], 0, ',', '.') : ''; ?>" 
                               required>
                        <small class="text-muted">Harga ketika menjual barang</small>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    <small>
                        <strong>Catatan:</strong><br>
                        - Harga jual harus lebih besar dari harga beli<br>
                        - Kode barang harus unik<br>
                        - Stok akan otomatis terupdate saat transaksi
                    </small>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan Barang
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

// IMPORT FORM
if ($action == 'import_form') {
    ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Import Data Barang</h5>
            <a href="dashboard.php?page=stok" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Perhatian:</strong><br>
                1. File harus dalam format CSV (Comma Separated Values)<br>
                2. Format kolom: Kode Barang, Nama Barang, Kategori, Stok, Harga Beli, Harga Jual<br>
                3. Baris pertama adalah header<br>
                4. Untuk barang yang sudah ada, stok akan ditambahkan
            </div>
            
            <form method="POST" action="dashboard.php?page=stok&action=import" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="file_excel" class="form-label">File CSV</label>
                    <input type="file" class="form-control" id="file_excel" name="file_excel" 
                           accept=".csv, .xls, .xlsx" required>
                </div>
                
                <div class="mb-3">
                    <h6>Contoh Format:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Kode Barang</th>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    <th>Stok</th>
                                    <th>Harga Beli</th>
                                    <th>Harga Jual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>BRG001</td>
                                    <td>Buku Tulis</td>
                                    <td>ATK</td>
                                    <td>100</td>
                                    <td>3000</td>
                                    <td>4500</td>
                                </tr>
                                <tr>
                                    <td>BRG002</td>
                                    <td>Pulpen</td>
                                    <td>ATK</td>
                                    <td>50</td>
                                    <td>2000</td>
                                    <td>3500</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Import Data
                    </button>
                    <a href="<?php echo $_SERVER['HTTP_REFERER'] ?? 'dashboard.php?page=stok'; ?>" 
                       class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php
    return;
}

// AJAX: GET DETAIL BARANG
if ($action == 'get_detail' && isset($_GET['barang_id'])) {
    $barang_id = (int)$_GET['barang_id'];
    $result = $db->query("SELECT * FROM barang WHERE id = $barang_id");
    
    if ($result->num_rows > 0) {
        $barang = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'data' => [
                'nama_barang' => $barang['nama_barang'],
                'stok' => $barang['stok'],
                'harga_jual' => $barang['harga_jual'],
                'harga_beli' => $barang['harga_beli']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Barang tidak ditemukan']);
    }
    exit;
}

// List Data dengan Filter
$search = $_GET['search'] ?? '';
$kategori_filter = $_GET['kategori'] ?? '';
$stok_min = $_GET['stok_min'] ?? '';
$stok_max = $_GET['stok_max'] ?? '';
$sort = $_GET['sort'] ?? 'nama_barang';
$order = $_GET['order'] ?? 'asc';

// Build WHERE clause
$where = "WHERE 1=1";
if ($search) {
    $where .= " AND (b.kode_barang LIKE '%$search%' OR b.nama_barang LIKE '%$search%')";
}
if ($kategori_filter) {
    $where .= " AND b.kategori_id = $kategori_filter";
}
if ($stok_min !== '') {
    $where .= " AND b.stok >= $stok_min";
}
if ($stok_max !== '') {
    $where .= " AND b.stok <= $stok_max";
}

// Ambil data kategori untuk filter
$kategori_list = $db->query("SELECT * FROM kategori ORDER BY nama_kategori");

// Hitung statistik
$stats = $db->query("SELECT 
    COUNT(*) as total_barang,
    SUM(stok) as total_stok,
    SUM(stok * harga_jual) as total_nilai,
    AVG(harga_jual - harga_beli) as rata_margin
    FROM barang b $where")->fetch_assoc();

// Barang dengan stok rendah
$low_stock = $db->query("SELECT COUNT(*) as count FROM barang WHERE stok <= 5")->fetch_assoc();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Manajemen Stok Barang</h5>
        <div>
            <a href="dashboard.php?page=stok&action=form" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Tambah Barang
            </a>
            <a href="dashboard.php?page=stok&action=import_form" class="btn btn-success btn-sm">
                <i class="bi bi-upload"></i> Import
            </a>
            <a href="dashboard.php?page=stok&action=export" class="btn btn-info btn-sm">
                <i class="bi bi-download"></i> Export
            </a>
        </div>
    </div>
    
    <!-- Statistik Cepat -->
    <div class="card-body border-bottom bg-light">
        <div class="row">
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary p-3 me-3">
                        <i class="bi bi-box text-white fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Barang</h6>
                        <h4 class="mb-0"><?php echo number_format($stats['total_barang'] ?? 0, 0, ',', '.'); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success p-3 me-3">
                        <i class="bi bi-stack text-white fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Stok</h6>
                        <h4 class="mb-0"><?php echo number_format($stats['total_stok'] ?? 0, 0, ',', '.'); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning p-3 me-3">
                        <i class="bi bi-cash-coin text-white fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Nilai Stok</h6>
                        <h4 class="mb-0">Rp <?php echo number_format($stats['total_nilai'] ?? 0, 0, ',', '.'); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-danger p-3 me-3">
                        <i class="bi bi-exclamation-triangle text-white fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Stok Rendah</h6>
                        <h4 class="mb-0"><?php echo number_format($low_stock['count'] ?? 0, 0, ',', '.'); ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="card-body border-bottom">
        <form method="GET" action="" class="row g-3">
            <input type="hidden" name="page" value="stok">
            
            <div class="col-md-3">
                <label for="search" class="form-label">Cari Barang</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" placeholder="Kode/Nama Barang">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <label for="kategori" class="form-label">Kategori</label>
                <select class="form-select" id="kategori" name="kategori">
                    <option value="">Semua Kategori</option>
                    <?php while ($kategori = $kategori_list->fetch_assoc()): ?>
                    <option value="<?php echo $kategori['id']; ?>" 
                        <?php echo $kategori_filter == $kategori['id'] ? 'selected' : ''; ?>>
                        <?php echo $kategori['nama_kategori']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="stok_min" class="form-label">Stok Min</label>
                <input type="number" class="form-control" id="stok_min" name="stok_min" 
                       value="<?php echo $stok_min; ?>" min="0" placeholder="Minimal">
            </div>
            
            <div class="col-md-2">
                <label for="stok_max" class="form-label">Stok Max</label>
                <input type="number" class="form-control" id="stok_max" name="stok_max" 
                       value="<?php echo $stok_max; ?>" min="0" placeholder="Maksimal">
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <div class="btn-group w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                    <a href="dashboard.php?page=stok" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="card-body">
        <?php if ($low_stock['count'] > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>Peringatan:</strong> <?php echo $low_stock['count']; ?> barang memiliki stok rendah (≤ 5 unit)
            <a href="dashboard.php?page=stok&stok_max=5" class="alert-link">Lihat semua</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="50">No</th>
                        <th>
                            <a href="?page=stok&sort=kode_barang&order=<?php echo $sort == 'kode_barang' && $order == 'asc' ? 'desc' : 'asc'; ?>&search=<?php echo $search; ?>">
                                Kode Barang
                                <?php if ($sort == 'kode_barang'): ?>
                                <i class="bi bi-arrow-<?php echo $order == 'asc' ? 'up' : 'down'; ?>"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?page=stok&sort=nama_barang&order=<?php echo $sort == 'nama_barang' && $order == 'asc' ? 'desc' : 'asc'; ?>&search=<?php echo $search; ?>">
                                Nama Barang
                                <?php if ($sort == 'nama_barang'): ?>
                                <i class="bi bi-arrow-<?php echo $order == 'asc' ? 'up' : 'down'; ?>"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>Kategori</th>
                        <th>
                            <a href="?page=stok&sort=stok&order=<?php echo $sort == 'stok' && $order == 'asc' ? 'desc' : 'asc'; ?>&search=<?php echo $search; ?>">
                                Stok
                                <?php if ($sort == 'stok'): ?>
                                <i class="bi bi-arrow-<?php echo $order == 'asc' ? 'up' : 'down'; ?>"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>Harga Beli</th>
                        <th>Harga Jual</th>
                        <th>Margin</th>
                        <th>Nilai Stok</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Query data dengan sorting
                    $order_by = "ORDER BY $sort $order";
                    $result = $db->query("SELECT 
                        b.*, 
                        k.nama_kategori,
                        (b.harga_jual - b.harga_beli) as margin,
                        (b.stok * b.harga_jual) as nilai_stok
                        FROM barang b
                        LEFT JOIN kategori k ON b.kategori_id = k.id
                        $where
                        $order_by");
                    
                    if ($result->num_rows == 0) {
                        echo '<tr><td colspan="10" class="text-center">Tidak ada data barang</td></tr>';
                    }
                    
                    $no = 1;
                    $total_nilai_stok = 0;
                    while ($row = $result->fetch_assoc()):
                        $nilai_stok = $row['stok'] * $row['harga_jual'];
                        $total_nilai_stok += $nilai_stok;
                        $margin_percent = $row['harga_beli'] > 0 ? (($row['margin'] / $row['harga_beli']) * 100) : 0;
                        $stok_class = $row['stok'] <= 5 ? 'danger' : ($row['stok'] <= 10 ? 'warning' : 'success');
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><strong><?php echo $row['kode_barang']; ?></strong></td>
                        <td><?php echo $row['nama_barang']; ?></td>
                        <td><?php echo $row['nama_kategori'] ?: '-'; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $stok_class; ?>">
                                <?php echo number_format($row['stok'], 0, ',', '.'); ?>
                            </span>
                        </td>
                        <td>Rp <?php echo number_format($row['harga_beli'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($row['harga_jual'], 0, ',', '.'); ?></td>
                        <td>
                            <span class="badge bg-info">
                                Rp <?php echo number_format($row['margin'], 0, ',', '.'); ?>
                                (<?php echo number_format($margin_percent, 1, ',', '.'); ?>%)
                            </span>
                        </td>
                        <td>Rp <?php echo number_format($nilai_stok, 0, ',', '.'); ?></td>
                        <td>
                            <a href="dashboard.php?page=stok&action=form&id=<?php echo $row['id']; ?>" 
                               class="btn btn-warning btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-info btn-sm" 
                                    onclick="showDetail(<?php echo $row['id']; ?>)" title="Detail">
                                <i class="bi bi-eye"></i>
                            </button>
                            <a href="dashboard.php?page=stok&action=delete&id=<?php echo $row['id']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('Hapus barang <?php echo addslashes($row['nama_barang']); ?>?')"
                               title="Hapus">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot class="table-secondary">
                    <tr>
                        <th colspan="4">TOTAL</th>
                        <th>
                            <?php 
                            $total_stok = $db->query("SELECT SUM(stok) as total FROM barang $where")->fetch_assoc()['total'] ?? 0;
                            echo number_format($total_stok, 0, ',', '.');
                            ?>
                        </th>
                        <th colspan="2"></th>
                        <th>Rp <?php echo number_format($total_nilai_stok, 0, ',', '.'); ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
    <!-- Modal Detail Barang -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailContent">
                    Loading...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>

<link rel="stylesheet" href="assets/css/style.css">