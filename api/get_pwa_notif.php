<?php
// Endpoint API: Mengambil notifikasi PWA yang belum terkirim ke Service Worker
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/koneksi.php';

// Cek autentikasi
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$id_user = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Tarik data notifikasi yang belum dibaca (is_read = 0)
$stmt = $pdo->prepare("SELECT * FROM tbl_notifikasi_pwa WHERE id_user = ? AND role = ? AND is_read = 0 ORDER BY created_at ASC");
$stmt->execute([$id_user, $role]);
$notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($notifs) > 0) {
    // Kumpulkan ID untuk diupdate
    $ids = array_column($notifs, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // Tandai langsung sebagai "Terkirim ke PWA" (is_read = 1)
    $updateStmt = $pdo->prepare("UPDATE tbl_notifikasi_pwa SET is_read = 1 WHERE id IN ($placeholders)");
    $updateStmt->execute($ids);
}

// Kembalikan ke browser
echo json_encode(['status' => 'ok', 'data' => $notifs]);
