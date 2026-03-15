<?php
require_once 'config/database.php';
$db = new Database();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $db->escape_string($_POST['username']);
    $password = $_POST['password'];
    
    $result = $db->query("SELECT * FROM users WHERE username = '$username'");
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Untuk testing, gunakan password tanpa hash terlebih dahulu
        // Dalam produksi, gunakan password_verify()
        if ($password == '123456') { // Ganti dengan password_verify() di production
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            
            redirect('dashboard.php');
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Keuangan UMKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4><i class="bi bi-cash-stack"></i> Login UMKM</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                        
                        <hr>
                        <div class="text-center">
                            <small class="text-muted">
                                <strong>Akun Testing:</strong><br>
                                Username: admin / owner<br>
                                Password: 123456
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
