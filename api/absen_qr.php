<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/fungsi.php';
require_once __DIR__ . '/../config/features.php';
require_once __DIR__ . '/wa_helper.php';

// Cek fitur absensi aktif
if (!fitur_aktif('absensi')) {
    echo json_encode(['status' => 'error', 'message' => 'Fitur Absensi tidak tersedia dalam paket lisensi Anda.']);
    exit;
}

$token = $_POST['token'] ?? $_GET['token'] ?? '';
$device_token = $_POST['device_token'] ?? '';

if (!$token) {
    echo json_encode(['status'=>'error', 'message'=>'ID Kartu/QR tidak ditemukan']);
    exit;
}

// Logika Pencatatan Perangkat (Selalu Catat/Log)
if ($device_token) {
    // Cek di tabel authorized
    $q_dev = $pdo->prepare("SELECT status FROM tbl_authorized_devices WHERE device_token=?");
    $q_dev->execute([$device_token]);
    $dev = $q_dev->fetch();

    if (!$dev) {
        // Daftarkan sebagai pending otomatis
        $pdo->prepare("INSERT INTO tbl_authorized_devices (device_token, status) VALUES (?, 'Pending')")->execute([$device_token]);
        $dev = ['status' => 'Pending'];
    }

    // Hanya Blokir/Tolak jika fitur pembatasan aktif
    $setting_sys = $pdo->query("SELECT is_scanner_restricted FROM tbl_setting WHERE id=1")->fetch();
    if ($setting_sys && $setting_sys['is_scanner_restricted']) {
        if ($dev['status'] != 'Approved') {
            $msg = $dev['status'] == 'Denied' ? 'Perangkat ini telah diblokir oleh Admin.' : 'Perangkat Anda sedang menunggu persetujuan Admin.';
            echo json_encode(['status'=>'unauthorized_device', 'message'=>$msg, 'token' => $device_token]);
            exit;
        }
    }

    // Update last used
    $pdo->prepare("UPDATE tbl_authorized_devices SET last_used=NOW() WHERE device_token=?")->execute([$device_token]);
}

// Cari Siswa berdasarkan rfid_uid atau uuid_kartu
$siswa = $pdo->prepare("SELECT s.*, k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE s.rfid_uid=? OR s.uuid_kartu=?");
$siswa->execute([$token, $token]);
$s = $siswa->fetch();

if (!$s) {
    echo json_encode(['status'=>'error', 'message'=>'QR Code tidak terdaftar di sistem SIPANDA']);
    exit;
}

// Ambil Setting Waktu (Khusus Kelas/Hari)
$today = date('Y-m-d');
$now = date('H:i:s');
$day_name = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w')];

// Cek Libur Nasional
$q_libur = $pdo->prepare("SELECT keterangan FROM tbl_hari_libur WHERE tanggal=?");
$q_libur->execute([$today]);
if ($libur = $q_libur->fetch()) {
    echo json_encode(['status'=>'error', 'message'=>"Hari Libur Nasional: {$libur['keterangan']} - Absensi ditutup"]);
    exit;
}

$q_khusus = $pdo->prepare("SELECT * FROM tbl_setting_absen_kelas WHERE id_kelas=? AND hari=?");
$q_khusus->execute([$s['id_kelas'], $day_name]);
$setting = $q_khusus->fetch();

if (!$setting) {
    echo json_encode(['status'=>'error', 'message'=>"Hari ini libur/tidak ada jadwal absen untuk kelas ". clean($s['nama_kelas'])]);
    exit;
}

$existing = $pdo->prepare("SELECT * FROM tbl_absensi_siswa WHERE id_siswa=? AND tanggal=?");
$existing->execute([$s['id_siswa'], $today]);
$ex = $existing->fetch();

if (!$ex) {
    // Presensi IN
    $ket = $now <= ($setting['batas_telat'] ?? '07:15:00') ? 'Tepat Waktu' : 'Terlambat';
    $pdo->prepare("INSERT INTO tbl_absensi_siswa (id_siswa, tanggal, jam_masuk, status, keterangan, metode) VALUES (?,?,?,?,?,?)")
        ->execute([$s['id_siswa'], $today, $now, 'IN', $ket, 'QR-Code']);
    
    wa_notif_absensi($s['id_siswa'], "MASUK ($ket)", $now);

    echo json_encode([
        'status' => 'ok',
        'type' => 'masuk',
        'nama' => $s['nama'],
        'kelas' => $s['nama_kelas'],
        'jam' => $now,
        'keterangan' => $ket,
        'message' => "Selamat ". (date('H') < 11 ? 'Pagi' : 'Siang') .", ". explode(' ', $s['nama'])[0] ."!"
    ]);
} elseif ($ex['status'] == 'IN') {
    // Presensi PULANG (COMPLETE)
    $jam_pulang = $setting['jam_pulang'] ?? '13:00:00';
    if (!is_jam_pulang_allowed($now, $jam_pulang)) {
        echo json_encode([
            'status' => 'error', 
            'message' => "Belum waktunya pulang (Jadwal: $jam_pulang, Dibuka 2 jam sebelumnya)"
        ]);
        exit;
    }
    
    $pdo->prepare("UPDATE tbl_absensi_siswa SET jam_keluar=?, status='COMPLETE' WHERE id=?")
        ->execute([$now, $ex['id']]);
    
    wa_notif_absensi($s['id_siswa'], "PULANG", $now);

    echo json_encode([
        'status' => 'ok',
        'type' => 'keluar',
        'nama' => $s['nama'],
        'kelas' => $s['nama_kelas'],
        'jam' => $now,
        'message' => "Hati-hati di jalan, ". explode(' ', $s['nama'])[0] ."!"
    ]);
} else {
    echo json_encode([
        'status' => 'already',
        'message' => "Anda sudah melakukan presensi lengkap hari ini."
    ]);
}
