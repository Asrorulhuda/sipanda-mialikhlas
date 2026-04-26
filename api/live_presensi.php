<?php
// Live Presensi API - Returns latest attendance data
header('Content-Type: application/json');
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/features.php';

// Cek fitur absensi aktif
if (!fitur_aktif('absensi')) {
    echo json_encode(['status' => 'error', 'message' => 'Fitur Absensi tidak tersedia.']);
    exit;
}

$today = date('Y-m-d');
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
$last_guru_id = isset($_GET['last_guru_id']) ? (int)$_GET['last_guru_id'] : 0;

// Stats
$stats = [
    'siswa_masuk' => $pdo->prepare("SELECT COUNT(*) FROM tbl_absensi_siswa WHERE tanggal=? AND status IN ('IN','MASUK')"),
    'siswa_keluar' => $pdo->prepare("SELECT COUNT(*) FROM tbl_absensi_siswa WHERE tanggal=? AND status='COMPLETE'"),
    'siswa_telat' => $pdo->prepare("SELECT COUNT(*) FROM tbl_absensi_siswa WHERE tanggal=? AND keterangan='Terlambat'"),
    'guru_masuk' => $pdo->prepare("SELECT COUNT(*) FROM tbl_absensi_guru WHERE tanggal=?"),
    'total' => $pdo->prepare("SELECT COUNT(*) FROM tbl_absensi_siswa WHERE tanggal=?"),
];

$stat_result = [];
foreach ($stats as $key => $stmt) {
    $stmt->execute([$today]);
    $stat_result[$key] = (int)$stmt->fetchColumn();
}

// New siswa attendance since last_id
$siswa_stmt = $pdo->prepare("SELECT a.id, a.jam_masuk, a.jam_keluar, a.status, a.keterangan, s.nama, k.nama_kelas as kelas 
    FROM tbl_absensi_siswa a 
    JOIN tbl_siswa s ON a.id_siswa=s.id_siswa 
    LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas 
    WHERE a.tanggal=? AND a.id > ? 
    ORDER BY a.id ASC LIMIT 20");
$siswa_stmt->execute([$today, $last_id]);
$siswa_data = [];
while ($r = $siswa_stmt->fetch()) {
    $siswa_data[] = [
        'id' => (int)$r['id'],
        'nama' => $r['nama'],
        'kelas' => $r['kelas'],
        'jam' => $r['status'] === 'COMPLETE' ? $r['jam_keluar'] : $r['jam_masuk'],
        'status' => $r['status'],
        'keterangan' => $r['keterangan']
    ];
}

// Guru attendance
$guru_stmt = $pdo->prepare("SELECT a.id, a.id_guru, a.jam_masuk, a.jam_keluar, a.status, g.nama 
    FROM tbl_absensi_guru a 
    JOIN tbl_guru g ON a.id_guru=g.id_guru 
    WHERE a.tanggal=? 
    ORDER BY a.id DESC LIMIT 30");
$guru_stmt->execute([$today]);
$guru_data = [];
while ($r = $guru_stmt->fetch()) {
    $guru_data[] = [
        'id' => (int)$r['id'],
        'id_guru' => (int)$r['id_guru'],
        'nama' => $r['nama'],
        'jam_masuk' => substr($r['jam_masuk'],0,5),
        'jam_keluar' => $r['jam_keluar'] ? substr($r['jam_keluar'],0,5) : null,
        'status' => $r['status']
    ];
}

echo json_encode([
    'stats' => $stat_result,
    'siswa' => $siswa_data,
    'guru' => $guru_data,
    'time' => date('H:i:s')
]);
