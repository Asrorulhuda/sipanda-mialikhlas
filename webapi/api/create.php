<?php
// Menerima scan RFID dari ESP8266/ESP32
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Cek fitur absensi aktif
if (!fitur_aktif('absensi')) {
    echo json_encode(['status' => 'error', 'message' => 'Fitur Absensi tidak tersedia.']);
    exit;
}

$uuid = trim($_POST['uuid'] ?? '');
if (!$uuid) {
    echo json_encode(['status' => 'error', 'message' => 'UUID missing']);
    exit;
}

$tgl_sekarang = date('Y-m-d');
$waktu_sekarang = date('H:i:s');

// Cari user (Siswa atau Guru)
$tipe = '';
$user = null;

// Cek Siswa
$stmt = $pdo->prepare("SELECT * FROM tbl_siswa WHERE uuid_kartu=? AND status='Aktif'");
$stmt->execute([$uuid]);
if ($s = $stmt->fetch()) {
    $tipe = 'siswa';
    $user = $s;
} else {
    // Cek Guru
    $stmt = $pdo->prepare("SELECT * FROM tbl_guru WHERE uuid_kartu=? AND status='Aktif'");
    $stmt->execute([$uuid]);
    if ($g = $stmt->fetch()) {
        $tipe = 'guru';
        $user = $g;
    }
}

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'Kartu tidak terdaftar!']);
    exit;
}

// Mendapatkan Nama Hari Indonesia
$day_name = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w')];

// Cek Aturan Jam (Khusus Kelas dulu, baru Global)
if ($tipe == 'siswa') {
    $q_khusus = $pdo->prepare("SELECT * FROM tbl_setting_absen_kelas WHERE id_kelas=? AND hari=?");
    $q_khusus->execute([$user['id_kelas'], $day_name]);
    $setting_absen = $q_khusus->fetch();
} else {
    $setting_absen = false; // Guru sementara hanya Global atau bisa ditambahkan nanti
}

if (!$setting_absen) {
    $stmt = $pdo->query("SELECT * FROM tbl_setting_absen WHERE id=1");
    $setting_absen = $stmt->fetch();
}

$batas_masuk = $setting_absen['batas_telat'] ?? '07:15:00';
$jam_pulang = $setting_absen['jam_pulang'] ?? '13:00:00';

if ($tipe == 'siswa') {
    // Cek apakah sudah absen hari ini
    $cek = $pdo->prepare("SELECT * FROM tbl_absensi_siswa WHERE id_siswa=? AND tanggal=?");
    $cek->execute([$user['id_siswa'], $tgl_sekarang]);
    $absen = $cek->fetch();
    
    if (!$absen) {
        // Masuk
        $ket = ($waktu_sekarang > $batas_masuk) ? 'Terlambat' : 'Tepat Waktu';
        $ins = $pdo->prepare("INSERT INTO tbl_absensi_siswa (id_siswa,tanggal,jam_masuk,keterangan,metode) VALUES (?,?,?,?,?)");
        $ins->execute([$user['id_siswa'], $tgl_sekarang, $waktu_sekarang, $ket, 'RFID']);
        echo json_encode(['status' => 'success', 'tipe' => 'Masuk', 'nama' => $user['nama'], 'keterangan' => $ket, 'waktu' => $waktu_sekarang]);
    } else {
        // Cek Pulang
        if ($waktu_sekarang >= $jam_pulang && empty($absen['jam_keluar'])) {
            $upd = $pdo->prepare("UPDATE tbl_absensi_siswa SET jam_keluar=? WHERE id=?");
            $upd->execute([$waktu_sekarang, $absen['id']]);
            echo json_encode(['status' => 'success', 'tipe' => 'Pulang', 'nama' => $user['nama'], 'waktu' => $waktu_sekarang]);
        } else {
            // Sudah absen atau belum waktunya pulang
            if (empty($absen['jam_keluar'])) {
                echo json_encode(['status' => 'warning', 'message' => 'Sudah absen masuk, belum jadwal pulang.']);
            } else {
                echo json_encode(['status' => 'warning', 'message' => 'Anda sudah selesai untuk hari ini.']);
            }
        }
    }
} else {
    // Guru Logic (Mirip tapi table guru)
    $cek = $pdo->prepare("SELECT * FROM tbl_absensi_guru WHERE id_guru=? AND tanggal=?");
    $cek->execute([$user['id_guru'], $tgl_sekarang]);
    $absen = $cek->fetch();
    
    if (!$absen) {
        // Masuk Guru
        $ket = ($waktu_sekarang > $batas_masuk) ? 'Terlambat' : 'Tepat Waktu';
        $ins = $pdo->prepare("INSERT INTO tbl_absensi_guru (id_guru,tanggal,jam_masuk,keterangan,metode) VALUES (?,?,?,?,?)");
        $ins->execute([$user['id_guru'], $tgl_sekarang, $waktu_sekarang, $ket, 'RFID']);
        echo json_encode(['status' => 'success', 'tipe' => 'Masuk', 'nama' => $user['nama'], 'keterangan' => $ket, 'waktu' => $waktu_sekarang]);
    } else {
        // Pulang Guru
        if ($waktu_sekarang >= $jam_pulang && empty($absen['jam_keluar'])) {
            $upd = $pdo->prepare("UPDATE tbl_absensi_guru SET jam_keluar=? WHERE id=?");
            $upd->execute([$waktu_sekarang, $absen['id']]);
            echo json_encode(['status' => 'success', 'tipe' => 'Pulang', 'nama' => $user['nama'], 'waktu' => $waktu_sekarang]);
        } else {
            // Sudah absen atau belum waktunya pulang
             if (empty($absen['jam_keluar'])) {
                echo json_encode(['status' => 'warning', 'message' => 'Sudah absen masuk, belum jadwal pulang.']);
            } else {
                echo json_encode(['status' => 'warning', 'message' => 'Anda sudah selesai untuk hari ini.']);
            }
        }
    }
}
?>
