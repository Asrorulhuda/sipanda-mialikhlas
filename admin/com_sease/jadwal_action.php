<?php
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin', 'waka_kurikulum']);
cek_fitur('akademik');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id_ta = get_ta_aktif($pdo)['id_ta'] ?? null;

    try {
        $pdo->beginTransaction();

        if ($action === 'move') {
            $id_jadwal = (int)$_POST['id_jadwal'];
            $target_hari = $_POST['hari'];
            $target_id_jam = (int)$_POST['id_jam'];
            
            $stmt = $pdo->prepare("SELECT j.*, g.nama as nama_guru FROM tbl_jadwal j LEFT JOIN tbl_guru g ON j.id_guru = g.id_guru WHERE j.id_jadwal = ?");
            $stmt->execute([$id_jadwal]);
            $source = $stmt->fetch();
            
            if (!$source) throw new Exception('Jadwal tidak ditemukan.');
            
            $id_kelas = $source['id_kelas'];
            $source_hari = $source['hari'];
            $source_id_jam = $source['id_jam'];
            $source_id_guru = $source['id_guru'];
            
            // Check target
            $stmt = $pdo->prepare("SELECT j.*, g.nama as nama_guru FROM tbl_jadwal j LEFT JOIN tbl_guru g ON j.id_guru = g.id_guru WHERE j.id_kelas = ? AND j.hari = ? AND j.id_jam = ? AND j.id_jadwal != ?");
            $stmt->execute([$id_kelas, $target_hari, $target_id_jam, $id_jadwal]);
            $target = $stmt->fetch();
            
            // Collision Detection Source Guru at Target Location
            if ($source_id_guru) {
                $stmt = $pdo->prepare("SELECT k.nama_kelas FROM tbl_jadwal j JOIN tbl_kelas k ON j.id_kelas = k.id_kelas WHERE j.id_guru = ? AND j.hari = ? AND j.id_jam = ? AND j.id_kelas != ?");
                $stmt->execute([$source_id_guru, $target_hari, $target_id_jam, $id_kelas]);
                $b = $stmt->fetch();
                if ($b) throw new Exception("Guru {$source['nama_guru']} bentrok! Sedang mengajar di kelas {$b['nama_kelas']}.");
            }
            
            if ($target) {
                $target_id_jadwal = $target['id_jadwal'];
                $target_id_guru = $target['id_guru'];
                
                // Collision Detection Target Guru at Source Location
                if ($target_id_guru) {
                    $stmt = $pdo->prepare("SELECT k.nama_kelas FROM tbl_jadwal j JOIN tbl_kelas k ON j.id_kelas = k.id_kelas WHERE j.id_guru = ? AND j.hari = ? AND j.id_jam = ? AND j.id_kelas != ?");
                    $stmt->execute([$target_id_guru, $source_hari, $source_id_jam, $id_kelas]);
                    $b = $stmt->fetch();
                    if ($b) throw new Exception("Tukar gagal! Guru {$target['nama_guru']} bentrok di kelas {$b['nama_kelas']}.");
                }
                
                $stmt = $pdo->prepare("UPDATE tbl_jadwal SET hari = ?, id_jam = ? WHERE id_jadwal = ?");
                $stmt->execute([$source_hari, $source_id_jam, $target_id_jadwal]);
            }
            
            $stmt = $pdo->prepare("UPDATE tbl_jadwal SET hari = ?, id_jam = ? WHERE id_jadwal = ?");
            $stmt->execute([$target_hari, $target_id_jam, $id_jadwal]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Jadwal dipindahkan.']);
            exit;

        } elseif ($action === 'add_mapel') {
            $id_mapel = (int)$_POST['id_mapel'];
            $hari = $_POST['hari'];
            $id_jam = (int)$_POST['id_jam'];
            $id_kelas = (int)$_POST['id_kelas'];

            // Check if slot occupied
            $stmt = $pdo->prepare("SELECT id_jadwal FROM tbl_jadwal WHERE id_kelas = ? AND hari = ? AND id_jam = ?");
            $stmt->execute([$id_kelas, $hari, $id_jam]);
            $exist = $stmt->fetch();

            if ($exist) {
                // Update mapel only
                $stmt = $pdo->prepare("UPDATE tbl_jadwal SET id_mapel = ? WHERE id_jadwal = ?");
                $stmt->execute([$id_mapel, $exist['id_jadwal']]);
                $msg = 'Mata Pelajaran diperbarui.';
            } else {
                // Insert new
                $stmt = $pdo->prepare("INSERT INTO tbl_jadwal (id_kelas, id_mapel, hari, id_jam, id_ta) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id_kelas, $id_mapel, $hari, $id_jam, $id_ta]);
                $msg = 'Mata Pelajaran ditambahkan.';
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => $msg]);
            exit;

        } elseif ($action === 'assign_guru') {
            $id_guru = (int)$_POST['id_guru'];
            $id_jadwal = (int)$_POST['id_jadwal'];

            $stmt = $pdo->prepare("SELECT * FROM tbl_jadwal WHERE id_jadwal = ?");
            $stmt->execute([$id_jadwal]);
            $jadwal = $stmt->fetch();

            if (!$jadwal) throw new Exception('Jadwal tidak ditemukan.');

            // Collision check
            $stmt = $pdo->prepare("SELECT k.nama_kelas FROM tbl_jadwal j JOIN tbl_kelas k ON j.id_kelas = k.id_kelas WHERE j.id_guru = ? AND j.hari = ? AND j.id_jam = ? AND j.id_kelas != ?");
            $stmt->execute([$id_guru, $jadwal['hari'], $jadwal['id_jam'], $jadwal['id_kelas']]);
            $b = $stmt->fetch();

            if ($b) {
                // Ambil nama guru untuk pesan error yang bagus
                $stmtG = $pdo->prepare("SELECT nama FROM tbl_guru WHERE id_guru = ?");
                $stmtG->execute([$id_guru]);
                $guru = $stmtG->fetch();
                throw new Exception("Guru {$guru['nama']} BENTROK! Sedang mengajar di kelas {$b['nama_kelas']} pada waktu tersebut.");
            }

            // Aman, update
            $stmt = $pdo->prepare("UPDATE tbl_jadwal SET id_guru = ? WHERE id_jadwal = ?");
            $stmt->execute([$id_guru, $id_jadwal]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Guru berhasil ditugaskan.']);
            exit;
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);
exit;
