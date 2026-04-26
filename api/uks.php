<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/wa_helper.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi berakhir, silakan login kembali.']);
    exit;
}

// Cek fitur UKS aktif
if (!fitur_aktif('uks')) {
    echo json_encode(['status' => 'error', 'message' => 'Fitur UKS tidak tersedia dalam paket lisensi Anda.']);
    exit;
}

$action = $_POST['action'] ?? '';
$id_user = (int)$_SESSION['user_id'];
$role = $_SESSION['role']; 

if ($action === 'lapor_sakit' || $action === 'minta_obat') {
    $keluhan = clean($_POST['keluhan'] ?? '');
    $tipe = ($action === 'minta_obat') ? 'Minta Obat' : 'Kunjungan';

    if (empty($keluhan)) {
        echo json_encode(['status' => 'error', 'message' => 'Keluhan/Permintaan tidak boleh kosong.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO tbl_uks_kunjungan (id_user, role_user, tipe, keluhan, tanggal, jam, status) VALUES (?, ?, ?, ?, CURDATE(), CURTIME(), 'Menunggu')");
        $stmt->execute([$id_user, $role, $tipe, $keluhan]);
        $id_new = $pdo->lastInsertId();

        if ($action === 'minta_obat' || ($action === 'lapor_sakit' && $role === 'guru')) {
            if ($role === 'guru') {
                wa_notif_izin_guru($id_user, $tipe, $keluhan);
            } else {
                wa_notif_uks($id_new, 'request');
            }
        }

        $msg = ($action === 'minta_obat') ? 'Permintaan obat berhasil dikirim. Silakan tunggu konfirmasi petugas UKS di menu Kesehatan.' : 'Laporan berhasil dikirim. Segera hubungi petugas UKS atau tunggu instruksi selanjutnya.';
        echo json_encode(['status' => 'success', 'message' => $msg]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'simpan_fisik') {
    $berat = (float)$_POST['berat'];
    $tinggi = (float)$_POST['tinggi'] / 100; // Convert to meters

    if ($berat <= 0 || $tinggi <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Data berat dan tinggi harus valid.']);
        exit;
    }

    $bmi = $berat / ($tinggi * $tinggi);
    $status_gizi = 'Normal';
    if ($bmi < 18.5) $status_gizi = 'Kurus';
    elseif ($bmi > 25.0) $status_gizi = 'Berlebih (Overweight)';

    try {
        $stmt = $pdo->prepare("INSERT INTO tbl_uks_fisik (id_user, role_user, berat, tinggi, bmi, status_gizi, tanggal) VALUES (?, ?, ?, ?, ?, ?, CURDATE())");
        $stmt->execute([$id_user, $role, $berat, $_POST['tinggi'], $bmi, $status_gizi]);

        echo json_encode(['status' => 'success', 'message' => 'Data fisik berhasil diperbarui! BMI: ' . number_format($bmi, 1) . ' (' . $status_gizi . ')']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data fisik: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid.']);
