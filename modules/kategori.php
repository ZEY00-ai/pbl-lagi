<?php
require_once 'config/database.php';
$db = new Database();
checkLogin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// CREATE / UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_kategori = $db->escape_string($_POST['nama_kategori']);
    $deskripsi = $db->escape_string($_POST['deskripsi']);
    
    if (isset($_POST['id']) && $_POST['id'] > 0) {
        // Update
        $id = $_POST['id'];
        $sql = "UPDATE kategori SET nama_kategori = '$nama_kategori', deskripsi = '$deskripsi' WHERE id = $id";
        $message = "Kategori berhasil diperbarui!";
    } else {
        // Create
        $sql = "INSERT INTO kategori (nama_kategori, deskripsi) VALUES ('$nama_kategori', '$deskripsi')";
        $message = "Kategori berhasil ditambahkan!";
    }
    
    if ($db->query($sql)) {
        $_SESSION['success'] = $message;
        redirect('dashboard.php?page=kategori');
    } else {
        $_SESSION['error'] = "Terjadi kesalahan!";
    }
}

// DELETE
if ($action == 'delete' && $id > 0) {
    $sql = "DELETE FROM kategori WHERE id = $id";
    if ($db->query($sql)) {
        $_SESSION['success'] = "Kategori berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Tidak dapat menghapus kategori yang masih digunakan!";
    }
    redirect('dashboard.php?page=kategori');
}

// Form Tambah/Edit
if ($action == 'form') {
    $kategori = null;
    if ($id > 0) {
        $result = $db->query("SELECT * FROM kategori WHERE id = $id");
        $kategori = $result->fetch_assoc();
    }
    ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?php echo $id ? 'Edit' : 'Tambah'; ?> Kategori</h5>
            <a href="dashboard.php?page=kategori" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <div class="mb-3">
                    <label for="nama_kategori" class="form-label">Nama Kategori</label>
                    <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" 
                           value="<?php echo $kategori['nama_kategori'] ?? ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?php echo $kategori['deskripsi'] ?? ''; ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Simpan
                </button>
            </form>
        </div>
    </div>
    <?php
    return;
}

// List Data
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Daftar Kategori</h5>
        <a href="dashboard.php?page=kategori&action=form" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Tambah Kategori
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="50">No</th>
                        <th>Nama Kategori</th>
                        <th>Deskripsi</th>
                        <th>Tanggal Dibuat</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $db->query("SELECT * FROM kategori ORDER BY id DESC");
                    $no = 1;
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo $row['nama_kategori']; ?></td>
                        <td><?php echo $row['deskripsi'] ?: '-'; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="dashboard.php?page=kategori&action=form&id=<?php echo $row['id']; ?>" 
                               class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="dashboard.php?page=kategori&action=delete&id=<?php echo $row['id']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('Hapus kategori ini?')">
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