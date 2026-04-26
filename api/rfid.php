<?php
// RFID Attendance API
header('Content-Type: application/json');
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/fungsi.php';
require_once __DIR__ . '/../config/features.php';
require_once __DIR__ . '/wa_helper.php';

// Feature gating helper for API endpoints
function api_cek_fitur($module_id) {
    if (!fitur_aktif($module_id)) {
        echo json_encode(['status' => 'error', 'message' => "Fitur ini tidak tersedia dalam paket lisensi Anda."]);
        exit;
    }
}

$action = $_GET['action'] ?? '';
$rfid = $_POST['rfid'] ?? $_GET['rfid'] ?? '';

switch ($action) {
    case 'absen_siswa':
        api_cek_fitur('absensi');
        if (!$rfid) { echo json_encode(['status'=>'error','message'=>'RFID kosong']); exit; }
        $siswa = $pdo->prepare("SELECT s.*,k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE s.rfid_uid=? OR s.uuid_kartu=?");
        $siswa->execute([$rfid, $rfid]); $s = $siswa->fetch();
        if (!$s) { echo json_encode(['status'=>'error','message'=>'RFID tidak terdaftar']); exit; }

        $today = date('Y-m-d'); $now = date('H:i:s');
        $day_name = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][date('w')];
        
        // Cek Libur Nasional
        $q_libur = $pdo->prepare("SELECT keterangan FROM tbl_hari_libur WHERE tanggal=?");
        $q_libur->execute([$today]);
        if ($libur = $q_libur->fetch()) {
            echo json_encode(['status'=>'error','message'=>"Hari Libur Nasional: {$libur['keterangan']} - Absensi ditutup"]); exit;
        }
        
        // Cari jadwal khusus kelas
        $q_khusus = $pdo->prepare("SELECT * FROM tbl_setting_absen_kelas WHERE id_kelas=? AND hari=?");
        $q_khusus->execute([$s['id_kelas'], $day_name]);
        $setting = $q_khusus->fetch();
        
        if (!$setting) {
            echo json_encode(['status'=>'error','message'=>'Hari ini libur/tidak ada jadwal absen untuk kelas '.clean($s['nama_kelas'])]); exit;
        }

        $existing = $pdo->prepare("SELECT * FROM tbl_absensi_siswa WHERE id_siswa=? AND tanggal=?");
        $existing->execute([$s['id_siswa'],$today]); $ex = $existing->fetch();

        if (!$ex) {
            $ket = $now <= ($setting['batas_telat']??'07:15:00') ? 'Tepat Waktu' : 'Terlambat';
            $pdo->prepare("INSERT INTO tbl_absensi_siswa (id_siswa,tanggal,jam_masuk,status,keterangan,metode) VALUES (?,?,?,?,?,?)")
                ->execute([$s['id_siswa'],$today,$now,'IN',$ket,'RFID']);
            
            // Kirim Notifikasi WA Masuk
            wa_notif_absensi($s['id_siswa'], "MASUK ($ket)", $now);
            
            echo json_encode(['status'=>'ok','type'=>'masuk','nama'=>$s['nama'],'kelas'=>$s['nama_kelas'],'jam'=>$now,'keterangan'=>$ket]);
        } elseif ($ex['status'] == 'IN') {
            // Cek apakah sudah jam pulang sesuai jadwal (Global atau Khusus)
            $jam_pulang = $setting['jam_pulang'] ?? '13:00:00';
            if (!is_jam_pulang_allowed($now, $jam_pulang)) {
                echo json_encode(['status'=>'error','message'=>'Belum waktunya pulang (Jadwal: '.$jam_pulang.', Dibuka 2 jam sebelumnya)']); exit;
            }
            $pdo->prepare("UPDATE tbl_absensi_siswa SET jam_keluar=?, status='COMPLETE' WHERE id=?")->execute([$now,$ex['id']]);
            
            // Kirim Notifikasi WA Pulang
            wa_notif_absensi($s['id_siswa'], "PULANG", $now);
            
            echo json_encode(['status'=>'ok','type'=>'keluar','nama'=>$s['nama'],'kelas'=>$s['nama_kelas'],'jam'=>$now]);
        } else {
            echo json_encode(['status'=>'ok','type'=>'already','nama'=>$s['nama'],'message'=>'Sudah absen lengkap hari ini']);
        }
        break;

    case 'absen_guru':
        api_cek_fitur('absensi');
        if (!$rfid) { echo json_encode(['status'=>'error','message'=>'RFID kosong']); exit; }
        $guru = $pdo->prepare("SELECT * FROM tbl_guru WHERE rfid_uid=? OR uuid_kartu=?"); $guru->execute([$rfid, $rfid]); $g = $guru->fetch();
        if (!$g) { echo json_encode(['status'=>'error','message'=>'RFID guru tidak terdaftar']); exit; }

        $today = date('Y-m-d'); $now = date('H:i:s');
        
        // Cek Libur Nasional
        $q_libur = $pdo->prepare("SELECT keterangan FROM tbl_hari_libur WHERE tanggal=?");
        $q_libur->execute([$today]);
        if ($libur = $q_libur->fetch()) {
            echo json_encode(['status'=>'error','message'=>"Hari Libur Nasional: {$libur['keterangan']} - Absensi ditutup"]); exit;
        }

        $ex = $pdo->prepare("SELECT * FROM tbl_absensi_guru WHERE id_guru=? AND tanggal=?");
        $ex->execute([$g['id_guru'],$today]); $e = $ex->fetch();

        if (!$e) {
            $pdo->prepare("INSERT INTO tbl_absensi_guru (id_guru,tanggal,jam_masuk,status,keterangan,metode) VALUES (?,?,?,?,?,?)")
                ->execute([$g['id_guru'],$today,$now,'IN','Hadir','RFID']);
            
            // Notifikasi Presensi ke Kepsek & TU
            wa_notif_presensi_guru($g['id_guru'], "MASUK", $now);

            echo json_encode(['status'=>'ok','type'=>'masuk','nama'=>$g['nama'],'jam'=>$now]);
        } elseif ($e['status'] == 'IN') {
            $pdo->prepare("UPDATE tbl_absensi_guru SET jam_keluar=?, status='COMPLETE' WHERE id=?")->execute([$now,$e['id']]);
            
            // Notifikasi Presensi ke Kepsek & TU
            wa_notif_presensi_guru($g['id_guru'], "PULANG", $now);

            echo json_encode(['status'=>'ok','type'=>'keluar','nama'=>$g['nama'],'jam'=>$now]);
        } else {
            echo json_encode(['status'=>'ok','type'=>'already','nama'=>$g['nama'],'message'=>'Sudah absen lengkap hari ini']);
        }
        break;

    case 'cek_saldo':
        api_cek_fitur('ekantin');
        if (!$rfid) { echo json_encode(['status'=>'error','message'=>'RFID kosong']); exit; }
        // Cek Siswa
        $siswa = $pdo->prepare("SELECT nama, saldo_jajan, limit_jajan_harian FROM tbl_siswa WHERE rfid_uid=? OR uuid_kartu=?");
        $siswa->execute([$rfid, $rfid]); $s = $siswa->fetch();
        if ($s) {
            echo json_encode(['status'=>'ok','role'=>'siswa','nama'=>$s['nama'],'saldo'=>$s['saldo_jajan'],'limit'=>$s['limit_jajan_harian']]);
            exit;
        }
        // Cek Guru
        $guru = $pdo->prepare("SELECT nama, saldo_jajan FROM tbl_guru WHERE rfid_uid=? OR uuid_kartu=?");
        $guru->execute([$rfid, $rfid]); $g = $guru->fetch();
        if ($g) {
            echo json_encode(['status'=>'ok','role'=>'guru','nama'=>$g['nama'],'saldo'=>$g['saldo_jajan'],'limit'=>0]);
            exit;
        }
        echo json_encode(['status'=>'error','message'=>'Kartu tidak dikenal']);
        break;

    case 'bayar_kantin':
        api_cek_fitur('ekantin');
        $nominal = $_POST['nominal'] ?? $_GET['nominal'] ?? 0;
        if (!$rfid || $nominal <= 0) { echo json_encode(['status'=>'error','message'=>'Data tidak lengkap']); exit; }
        
        // Cek Siswa
        $siswa = $pdo->prepare("SELECT id_siswa, nama, saldo_jajan, limit_jajan_harian FROM tbl_siswa WHERE rfid_uid=? OR uuid_kartu=?");
        $siswa->execute([$rfid, $rfid]); $s = $siswa->fetch();
        
        if ($s) {
            // Cek Limit Harian
            if ($s['limit_jajan_harian'] > 0) {
                $q_spent = $pdo->prepare("SELECT SUM(total) as total_spent FROM tbl_order WHERE id_siswa=? AND DATE(tanggal) = CURDATE()");
                $q_spent->execute([$s['id_siswa']]);
                $spent = $q_spent->fetch()['total_spent'] ?? 0;
                
                if ($spent + $nominal > $s['limit_jajan_harian']) {
                    echo json_encode(['status'=>'error','message'=>'Limit jajan harian tercapai (Unit: '.rupiah($s['limit_jajan_harian']).')']);
                    exit;
                }
            }
            
            if ($s['saldo_jajan'] < $nominal) {
                echo json_encode(['status'=>'error','message'=>'Saldo jajan tidak cukup']);
                exit;
            }
            
            // Proses Bayar Siswa
            $pdo->prepare("UPDATE tbl_siswa SET saldo_jajan = saldo_jajan - ? WHERE id_siswa = ?")->execute([$nominal, $s['id_siswa']]);
            $pdo->prepare("INSERT INTO tbl_order (kode_order, id_siswa, total, cara_bayar, tanggal) VALUES (?, ?, ?, 'RFID', NOW())")
                ->execute(['ORD-'.time().'-'.$s['id_siswa'], $s['id_siswa'], $nominal]);
            
            echo json_encode(['status'=>'ok','nama'=>$s['nama'],'sisa_saldo'=>$s['saldo_jajan']-$nominal,'nominal'=>$nominal]);
            exit;
        }

        // Cek Guru
        $guru = $pdo->prepare("SELECT id_guru, nama, saldo_jajan FROM tbl_guru WHERE rfid_uid=? OR uuid_kartu=?");
        $guru->execute([$rfid, $rfid]); $g = $guru->fetch();
        
        if ($g) {
            if ($g['saldo_jajan'] < $nominal) {
                echo json_encode(['status'=>'error','message'=>'Saldo jajan tidak cukup']);
                exit;
            }
            
            // Proses Bayar Guru
            $pdo->prepare("UPDATE tbl_guru SET saldo_jajan = saldo_jajan - ? WHERE id_guru = ?")->execute([$nominal, $g['id_guru']]);
            $pdo->prepare("INSERT INTO tbl_order (kode_order, total, cara_bayar, tanggal, keterangan) VALUES (?, ?, 'RFID', NOW(), ?)")
                ->execute(['ORD-'.time().'-G'.$g['id_guru'], $nominal, 'Jajan Guru: '.$g['nama']]);
            
            echo json_encode(['status'=>'ok','nama'=>$g['nama'],'sisa_saldo'=>$g['saldo_jajan']-$nominal,'nominal'=>$nominal]);
            exit;
        }

        echo json_encode(['status'=>'error','message'=>'Kartu tidak dikenal']);
        break;

    case 'tamu_tap':
        api_cek_fitur('humas');
        if (!$rfid) { echo json_encode(['status'=>'error','message'=>'RFID kosong']); exit; }
        
        $s_sh = $pdo->query("SELECT nama_sekolah, kepsek, wa_kepsek FROM tbl_setting WHERE id=1")->fetch();

        // Cari kunjungan aktif
        $check_active = $pdo->prepare("SELECT * FROM tbl_humas_tamu WHERE rfid_uid = ? AND status = 'Masuk' ORDER BY id DESC LIMIT 1");
        $check_active->execute([$rfid]);
        $visit = $check_active->fetch();

        if ($visit) {
            // Proses Auto Check-out
            $pdo->prepare("UPDATE tbl_humas_tamu SET status = 'Keluar', waktu_keluar = NOW() WHERE id = ?")
                ->execute([$visit['id']]);
            
            // Kirim WA Terimakasih ke Tamu
            if ($visit['no_hp']) {
                $msg_k = "👋 *CHECK-OUT BERHASIL*\n\nHalo *{$visit['nama_tamu']}*,\nTerima kasih telah berkunjung ke *{$s_sh['nama_sekolah']}*.\n\nSemoga hari Anda menyenangkan! 🙏";
                send_wa($visit['no_hp'], $msg_k);
            }
            
            $res = ['status' => 'checkout', 'nama' => $visit['nama_tamu']];
            $event_type = 'checkout';
            $g_name = $visit['nama_tamu'];
        } else {
            // Cek apakah tamu lama untuk auto-fill (Check-In Baru)
            $past = $pdo->prepare("SELECT nama_tamu, instansi, no_hp FROM tbl_humas_tamu WHERE rfid_uid = ? ORDER BY id DESC LIMIT 1");
            $past->execute([$rfid]);
            $guest = $past->fetch();
            
            $res = ['status' => 'new_visit', 'data' => $guest ?: null];
            $event_type = 'new_visit';
            $g_name = null;
        }

        echo json_encode($res);
        
        // Sync ke tabel monitor Kiosk agar layar otomatis bereaksi
        $pdo->prepare("INSERT INTO tbl_rfid_temp (rfid_uid, status, event_type, guest_name) VALUES (?, 'unused', ?, ?)")
            ->execute([$rfid, $event_type, $g_name]);
        break;

    case 'cek_monitor':
        // Kiosk memanggil ini setiap detik untuk mendengarkan tap luar
        $st = $pdo->query("SELECT * FROM tbl_rfid_temp WHERE status='unused' ORDER BY id DESC LIMIT 1")->fetch();
        if ($st) {
            $pdo->prepare("UPDATE tbl_rfid_temp SET status='used' WHERE id=?")->execute([$st['id']]);
            echo json_encode([
                'status' => 'ok', 
                'rfid' => $st['rfid_uid'],
                'event_type' => $st['event_type'],
                'guest_name' => $st['guest_name']
            ]);
        } else {
            echo json_encode(['status' => 'idle']);
        }
        break;

    case 'clear_monitor':
        // Flush semua antrian lama saat Kiosk baru dibuka
        $pdo->query("UPDATE tbl_rfid_temp SET status='used' WHERE status='unused'");
        echo json_encode(['status' => 'success', 'message' => 'Monitor flushed']);
        break;

    case 'get_kiosk_stats':
        $count_masuk = $pdo->query("SELECT COUNT(*) FROM tbl_humas_tamu WHERE status='Masuk' AND DATE(tanggal) = CURDATE()")->fetchColumn();
        $count_keluar = $pdo->query("SELECT COUNT(*) FROM tbl_humas_tamu WHERE status='Keluar' AND DATE(tanggal) = CURDATE()")->fetchColumn();
        
        $names_in = $pdo->query("SELECT nama_tamu FROM tbl_humas_tamu WHERE status='Masuk' AND DATE(tanggal) = CURDATE() ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
        $names_out = $pdo->query("SELECT nama_tamu FROM tbl_humas_tamu WHERE status='Keluar' AND DATE(tanggal) = CURDATE() ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode([
            'count_masuk' => (int)$count_masuk,
            'count_keluar' => (int)$count_keluar,
            'names_in' => $names_in,
            'names_out' => $names_out
        ]);
        break;
    
    case 'uks_tap':
        api_cek_fitur('uks');
        if (!$rfid) { echo json_encode(['status'=>'error','message'=>'RFID kosong']); exit; }
        
        // Find Student
        $siswa = $pdo->prepare("SELECT id_siswa, nama, id_kelas FROM tbl_siswa WHERE rfid_uid=? OR uuid_kartu=?");
        $siswa->execute([$rfid, $rfid]); 
        $s = $siswa->fetch();
        
        if (!$s) {
            echo json_encode(['status'=>'error','message'=>'Kartu tidak dikenal/tidak terdaftar']);
            exit;
        }

        // Insert into UKS Queue
        try {
            $pdo->prepare("INSERT INTO tbl_uks_kunjungan (id_user, role_user, tipe, keluhan, tanggal, jam, status) VALUES (?, 'siswa', 'Kunjungan', 'Kunjungan Mandiri (RFID Tap)', CURDATE(), CURTIME(), 'Menunggu')")
                ->execute([$s['id_siswa']]);
            $id_new = $pdo->lastInsertId();

            // Trigger Dual Notification
            wa_notif_uks($id_new, 'tap_manual');

            echo json_encode(['status'=>'ok','nama'=>$s['nama'],'message'=>'Antrean UKS Berhasil']);
        } catch (PDOException $e) {
            echo json_encode(['status'=>'error','message'=>'Gagal antre: '.$e->getMessage()]);
        }
        break;

    case 'lib_visit':
        api_cek_fitur('perpustakaan');
        if (!$rfid) { echo json_encode(['status'=>'error','message'=>'RFID kosong']); exit; }
        $siswa = $pdo->prepare("SELECT id_siswa, nama FROM tbl_siswa WHERE rfid_uid=? OR uuid_kartu=?");
        $siswa->execute([$rfid, $rfid]); $s = $siswa->fetch();
        if (!$s) { echo json_encode(['status'=>'error','message'=>'RFID tidak terdaftar']); exit; }

        $today = date('Y-m-d'); $now = date('H:i:s');
        $pdo->prepare("INSERT INTO tbl_lib_kunjung (id_siswa, tanggal, jam) VALUES (?, ?, ?)")
            ->execute([$s['id_siswa'], $today, $now]);
        
        echo json_encode(['status'=>'ok', 'nama'=>$s['nama'], 'message'=>'Kunjungan perpus dicatat']);
        break;

    case 'lib_tap':
        api_cek_fitur('perpustakaan');
        if (!$rfid) { echo json_encode(['status'=>'error','message'=>'RFID kosong']); exit; }
        $siswa = $pdo->prepare("SELECT id_siswa, nama, id_kelas FROM tbl_siswa WHERE rfid_uid=? OR uuid_kartu=?");
        $siswa->execute([$rfid, $rfid]); $s = $siswa->fetch();
        
        $role = 'siswa';
        if (!$s) {
            $guru = $pdo->prepare("SELECT id_guru, nama FROM tbl_guru WHERE rfid_uid=? OR uuid_kartu=?");
            $guru->execute([$rfid, $rfid]); $s = $guru->fetch();
            $role = 'guru';
        }

        if (!$s) { echo json_encode(['status'=>'error','message'=>'Kartu tidak dikenal']); exit; }

        // Sync to temp for Librarian Dashboard
        $pdo->prepare("INSERT INTO tbl_rfid_temp (rfid_uid, status, event_type, guest_name) VALUES (?, 'unused', 'lib_tap', ?)")
            ->execute([$rfid, $s['nama']]);
        
        echo json_encode(['status'=>'ok', 'nama'=>$s['nama'], 'role'=>$role]);
        break;

    default:
        echo json_encode(['status'=>'error','message'=>'Action tidak valid','available'=>['absen_siswa','absen_guru','cek_saldo','bayar_kantin','tamu_tap','cek_monitor','get_kiosk_stats','uks_tap','lib_visit','lib_tap']]);
}
