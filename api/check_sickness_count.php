<?php
require_once __DIR__ . '/../config/init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$id_siswa = $_SESSION['user_id'];

// Hitung hari sakit berturut-turut ke belakang dari hari ini
// Untuk simulasi sederhana, kita hitung berapa kali 'Sakit' dalam 7 hari terakhir
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM tbl_absensi_siswa 
    WHERE id_siswa = ? 
    AND keterangan = 'Sakit' 
    AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");
$stmt->execute([$id_siswa]);
$count = $stmt->fetchColumn();

echo json_encode(['count' => (int)$count]);
