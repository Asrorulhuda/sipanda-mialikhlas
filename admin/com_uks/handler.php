<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../api/wa_helper.php';
cek_role(['admin', 'waka_uks']);

$action = $_POST['action'] ?? '';

if ($action === 'update_status') {
    $id = (int)$_POST['id_kunjungan'];
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE tbl_uks_kunjungan SET status=? WHERE id_kunjungan=?");
        $stmt->execute([$status, $id]);
        wa_notif_uks($id, 'status_update');
        echo json_encode(['status' => 'success', 'message' => 'Status berhasil diubah.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} elseif ($action === 'finish_treatment') {
    $id = (int)$_POST['id_kunjungan'];
    $diagnosa = clean($_POST['diagnosa']);
    $id_obat = !empty($_POST['id_obat']) ? (int)$_POST['id_obat'] : null;
    $jumlah_obat = (int)$_POST['jumlah_obat'];
    $status = $_POST['status']; // 'Kembali ke Kelas', 'Pulang', 'Rujukan', 'Disetujui'

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE tbl_uks_kunjungan SET diagnosa=?, id_obat=?, jumlah_obat=?, status=? WHERE id_kunjungan=?");
        $stmt->execute([$diagnosa, $id_obat, $jumlah_obat, $status, $id]);

        if ($id_obat) {
            $upd = $pdo->prepare("UPDATE tbl_uks_obat SET stok = stok - ? WHERE id_obat=?");
            $upd->execute([$jumlah_obat, $id_obat]);
        }

        $pdo->commit();
        wa_notif_uks($id, 'status_update');
        echo json_encode(['status' => 'success', 'message' => 'Penanganan berhasil disimpan.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} elseif ($action === 'simpan_fisik') {
    // Handling physical input for specific targets (students)
    $id_target = (int)$_POST['id_siswa_target'];
    $berat = (float)$_POST['berat'];
    $tinggi = (float)$_POST['tinggi'] / 100;

    $bmi = $berat / ($tinggi * $tinggi);
    $status_gizi = 'Normal';
    if ($bmi < 18.5) $status_gizi = 'Kurus';
    elseif ($bmi > 25.0) $status_gizi = 'Berlebih (Overweight)';

    try {
        $stmt = $pdo->prepare("INSERT INTO tbl_uks_fisik (id_user, role_user, berat, tinggi, bmi, status_gizi, tanggal) VALUES (?, 'siswa', ?, ?, ?, ?, CURDATE())");
        $stmt->execute([$id_target, $berat, $_POST['tinggi'], $bmi, $status_gizi]);
        $id_f = $pdo->lastInsertId();
        wa_notif_fisik($id_target, $id_f);
        echo json_encode(['status' => 'success', 'message' => 'Data fisik berhasil disimpan!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} elseif ($action === 'delete_treatment') {
    $id = (int)$_POST['id_kunjungan'];
    $pdo->prepare("DELETE FROM tbl_uks_kunjungan WHERE id_kunjungan=?")->execute([$id]);
    echo json_encode(['status' => 'success', 'message' => 'Data dihapus.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal.']);
}
