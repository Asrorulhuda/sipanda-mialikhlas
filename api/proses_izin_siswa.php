<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/wa_helper.php';
cek_role(['siswa']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_siswa = $_SESSION['user_id'];
    $keterangan = $_POST['keterangan'] ?? ''; // 'Izin' or 'Sakit'
    $alasan = $_POST['alasan'] ?? '';
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;
    $tanggal = date('Y-m-d');
    
    // 1. Cek apakah sudah absen hari ini
    $cek = $pdo->prepare("SELECT id FROM tbl_absensi_siswa WHERE id_siswa = ? AND tanggal = ?");
    $cek->execute([$id_siswa, $tanggal]);
    if ($cek->fetch()) {
        header("Location: ../siswa/absensi.php?e=sudah_absen");
        exit;
    }

    // 2. Handle Foto Selfie (Base64)
    $foto_selfie = $_POST['foto_selfie'] ?? '';
    $filename_selfie = '';
    if (!empty($foto_selfie)) {
        $foto_selfie = str_replace('data:image/jpeg;base64,', '', $foto_selfie);
        $foto_selfie = str_replace(' ', '+', $foto_selfie);
        $data = base64_decode($foto_selfie);
        $filename_selfie = 'proof_' . $id_siswa . '_' . time() . '.jpg';
        $path = __DIR__ . '/../assets/uploads/absensi_bukti/';
        if (!is_dir($path)) mkdir($path, 0777, true);
        file_put_contents($path . $filename_selfie, $data);
    }

    // 3. Handle Surat Dokter (Upload File)
    $filename_dokter = '';
    if (isset($_FILES['surat_dokter']) && $_FILES['surat_dokter']['error'] == 0) {
        $ext = pathinfo($_FILES['surat_dokter']['name'], PATHINFO_EXTENSION);
        $filename_dokter = 'med_' . $id_siswa . '_' . time() . '.' . $ext;
        $path = __DIR__ . '/../assets/uploads/absensi_bukti/';
        move_uploaded_file($_FILES['surat_dokter']['tmp_name'], $path . $filename_dokter);
    }

    // 4. Simpan ke Database
    $query = "INSERT INTO tbl_absensi_siswa (id_siswa, tanggal, jam_masuk, status, keterangan, foto, lat, lng, surat_dokter, status_verifikasi) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $id_siswa, 
        $tanggal, 
        date('H:i:s'), 
        'IN', 
        $keterangan, 
        $filename_selfie, 
        $lat, 
        $lng, 
        $filename_dokter, 
        'Pending'
    ]);

    // Kirim Notifikasi ke Wali Kelas
    wa_notif_izin_siswa($id_siswa, $keterangan, $alasan);

    header("Location: ../siswa/absensi.php?s=pengajuan_terkirim");
    exit;
}
