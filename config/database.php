<?php
session_start();

class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "sistem_keuangan_umkm";
    public $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        
        if ($this->conn->connect_error) {
            die("Koneksi gagal: " . $this->conn->connect_error);
        }
    }

    public function escape_string($string) {
        return $this->conn->real_escape_string($string);
    }

    public function query($sql) {
        return $this->conn->query($sql);
    }

    public function getLastInsertId() {
        return $this->conn->insert_id;
    }
}

// Fungsi helper untuk redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Fungsi untuk memeriksa login
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        redirect('index.php');
    }
}

// Fungsi untuk mendapatkan role user
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

// Fungsi untuk memeriksa role
function checkRole($allowedRoles) {
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        $_SESSION['error'] = "Akses ditolak! Anda tidak memiliki izin.";
        redirect('dashboard.php');
    }
}
?>