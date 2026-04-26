<?php
require_once __DIR__ . '/asr_license.php';
// Koneksi Database SIPANDA v2.0.1
date_default_timezone_set('Asia/Jakarta');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sipanda');
// Deteksi Base URL Otomatis (Local vs Hosting)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_local = ($host === 'localhost' || $host === '127.0.0.1');
$base_url = ($is_local) ? "/sipanda2/" : "/"; // Ganti "/" jika di hosting diletakkan di subfolder

define('BASE_URL', $base_url);
define('APP_NAME', 'SIPANDA');
define('APP_VERSION', '2.0.9.3');
define('GITHUB_REPO_OWNER', 'Asrorulhuda');
define('GITHUB_REPO_NAME', 'sipanda-mialikhlas');
define('GITHUB_BRANCH', 'main');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Tambahan Keamanan: Menonaktifkan emulasi prepared statement untuk mencegah serangan SQLi tertentu
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("<div style='padding:40px;text-align:center;font-family:sans-serif'><h2>❌ Database Error</h2><p>Terjadi kegagalan koneksi ke database. Pastikan pengaturan database sudah benar sesuai dengan lingkungan hosting/server Anda.</p></div>");
}

// Load setting sekolah
$q_setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1");
$setting = $q_setting->fetch();
