<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/wa_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guru') {
    echo json_encode(['status' => 'error', 'message' => 'Sesi berakhir atau akses ditolak.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_guru = (int)$_SESSION['user_id'];
    $status = $_POST['status'] ?? 'Izin';
    $keterangan = clean($_POST['keterangan'] ?? '');
    $tanggal = date('Y-m-d');
    
    if (empty($keterangan)) {
        echo json_encode(['status' => 'error', 'message' => 'Alasan izin tidak boleh kosong.']);
        exit;
    }

    // 1. Cek apakah sudah ada absensi hari ini (Hadir/Izin/Sakit)
    $cek = $pdo->prepare("SELECT id FROM tbl_absensi_guru WHERE id_guru = ? AND tanggal = ?");
    $cek->execute([$id_guru, $tanggal]);
    if ($cek->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Anda sudah memiliki catatan absensi (Hadir/Izin) untuk hari ini.']);
        exit;
    }

    // 2. Handle Upload Bukti
    $filename_bukti = '';
    if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] == 0) {
        $ext = pathinfo($_FILES['foto_bukti']['name'], PATHINFO_EXTENSION);
        $filename_bukti = 'guru_izin_' . $id_guru . '_' . time() . '.' . $ext;
        $path = __DIR__ . '/../assets/uploads/absensi_bukti/';
        if (!is_dir($path)) mkdir($path, 0777, true);
        move_uploaded_file($_FILES['foto_bukti']['tmp_name'], $path . $filename_bukti);
    }

    // 3. Simpan ke Database
    try {
        $query = "INSERT INTO tbl_absensi_guru (id_guru, tanggal, jam_masuk, status, keterangan, foto_bukti, metode) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            $id_guru, 
            $tanggal, 
            date('H:i:s'), 
            $status, 
            $keterangan, 
            $filename_bukti, 
            'Mandiri'
        ]);

        // 4. Kirim Notifikasi WA ke Kepala Sekolah
        wa_notif_izin_guru($id_guru, $status, $keterangan);

        echo json_encode(['status' => 'success', 'message' => 'Pengajuan izin berhasil terkirim. Semoga lekas membaik jika sedang sakit!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal simpan database: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
