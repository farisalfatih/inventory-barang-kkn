<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '20062004');
define('DB_NAME', 'inventaris_kkn2');

// Koneksi Database
function getConnection()
{
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Start session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Fungsi untuk mengecek apakah user sudah login
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && isset($_SESSION['npm']);
}

// Fungsi untuk redirect jika belum login
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Fungsi untuk logout
function logout()
{
    session_destroy();
    header('Location: login.php');
    exit();
}

// Fungsi untuk format rupiah
function formatRupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Fungsi untuk format tanggal Indonesia
function formatTanggal($tanggal)
{
    if (!$tanggal) return '-';
    $bulan = array(
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    $pecahkan = explode('-', $tanggal);
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}

// Fungsi untuk sanitasi input
function sanitize($input)
{
    return htmlspecialchars(strip_tags(trim($input)));
}

// Fungsi untuk validasi NPM
function validateNPM($npm)
{
    return preg_match('/^[0-9]{11}$/', $npm);
}

// Fungsi untuk mendapatkan nama user berdasarkan ID
function getUserName($user_id)
{
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT nama FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    return $user ? $user['nama'] : 'Unknown';
}

// Fungsi untuk mendapatkan semua users untuk dropdown
function getAllUsers()
{
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT id, nama FROM users ORDER BY nama");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Fungsi untuk mendapatkan semua proker untuk dropdown
function getAllProker()
{
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT id, nama_proker FROM proker ORDER BY nama_proker");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Fungsi untuk mendapatkan semua vendor untuk dropdown
function getAllVendor()
{
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT id, nama_vendor FROM vendor ORDER BY nama_vendor");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Base URL aplikasi
define('BASE_URL', 'http://localhost/inventaris-kkn/');

