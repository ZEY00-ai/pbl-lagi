<?php
require_once 'config/database.php';
$db = new Database();
checkLogin();
checkRole(['admin']); // Hanya admin yang bisa akses

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// CREATE / UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $db->escape_string($_POST['username']);
    $nama_lengkap = $db->escape_string($_POST['nama_lengkap']);
    $role = $db->escape_string($_POST['role']);
    $password = $_POST['password'];
    
    if (isset($_POST['id']) && $_POST['id'] > 0) {
        // Update
        $id = $_POST['id'];
        
        if (!empty($password)) {
            // Hash password baru
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = '$username', nama_lengkap = '$nama_lengkap', 
                    role = '$role', password = '$hashed_password' WHERE id = $id";
        } else {
            $sql = "UPDATE users SET username = '$username', nama_lengkap = '$nama_lengkap', 
                    role = '$role' WHERE id = $id";
        }
        $message = "User berhasil diperbarui!";
    } else {
        // Create - wajib password
        if (empty($password)) {
            $_SESSION['error'] = "Password wajib diisi!";
            redirect('dashboard.php?page=user&action=form');
        }
        
        // Cek username sudah ada
        $check = $db->query("SELECT id FROM users WHERE username = '$username'");
        if ($check->num_rows > 0) {
            $_SESSION['error'] = "Username sudah digunakan!";
            redirect('dashboard.php?page=user&action=form');
        }
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, nama_lengkap, role) 
                VALUES ('$username', '$hashed_password', '$nama_lengkap', '$role')";
        $message = "User berhasil ditambahkan!";
    }
    
    if ($db->query($sql)) {
        $_SESSION['success'] = $message;
        redirect('dashboard.php?page=user');
    } else {
        $_SESSION['error'] = "Terjadi kesalahan!";
    }
}

// DELETE
if ($action == 'delete' && $id > 0) {
    // Tidak boleh hapus user sendiri
    if ($id == $_SESSION['user_id']) {
        $_SESSION['error'] = "Tidak dapat menghapus akun sendiri!";
    } else {
        $sql = "DELETE FROM users WHERE id = $id";
        if ($db->query($sql)) {
            $_SESSION['success'] = "User berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Terjadi kesalahan!";
        }
    }
    redirect('dashboard.php?page=user');
}

// Form Tambah/Edit
if ($action == 'form') {
    $user = null;
    if ($id > 0) {
        $result = $db->query("SELECT * FROM users WHERE id = $id");
        $user = $result->fetch_assoc();
    }
    ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?php echo $id ? 'Edit' : 'Tambah'; ?> User</h5>
            <a href="dashboard.php?page=user" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo $user['username'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="nama_lengkap" class="form-label">Nama Lengkap *</label>
                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                               value="<?php echo $user['nama_lengkap'] ?? ''; ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="role" class="form-label">Role *</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Pilih Role</option>
                            <option value="admin" <?php echo isset($user['role']) && $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="owner" <?php echo isset($user['role']) && $user['role'] == 'owner' ? 'selected' : ''; ?>>Owner</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">
                            Password <?php echo $id ? '(kosongkan jika tidak diubah)' : '*'; ?>
                        </label>
                        <input type="password" class="form-control" id="password" name="password"
                               <?php echo !$id ? 'required' : ''; ?>>
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                </div>
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
        <h5 class="mb-0">Manajemen User</h5>
        <a href="dashboard.php?page=user&action=form" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Tambah User
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="50">No</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Role</th>
                        <th>Tanggal Dibuat</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $db->query("SELECT * FROM users ORDER BY id DESC");
                    $no = 1;
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo $row['username']; ?></td>
                        <td><?php echo $row['nama_lengkap']; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $row['role'] == 'admin' ? 'primary' : 'success'; ?>">
                                <?php echo ucfirst($row['role']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="dashboard.php?page=user&action=form&id=<?php echo $row['id']; ?>" 
                               class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($row['id'] != $_SESSION['user_id']): ?>
                            <a href="dashboard.php?page=user&action=delete&id=<?php echo $row['id']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('Hapus user ini?')">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php else: ?>
                            <button class="btn btn-danger btn-sm" disabled title="Tidak dapat menghapus akun sendiri">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>