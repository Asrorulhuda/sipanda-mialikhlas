<?php
// WhatsApp Notification Helper
require_once __DIR__ . '/../config/koneksi.php';

function send_wa($phone, $message) {
    global $pdo;
    $setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
    $url = $setting['wa_gateway_url'] ?? '';
    $token = $setting['wa_token'] ?? '';
    $sender = $setting['wa_sender'] ?? '';
    if (!$url || !$token) return false;

    $data = ['api_key'=>$token, 'sender'=>$sender, 'number'=>$phone, 'message'=>$message];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>http_build_query($data)]);
    $result = curl_exec($ch); curl_close($ch);

    $pdo->prepare("INSERT INTO tbl_wa_log (phone,message,status,response) VALUES (?,?,?,?)")->execute([$phone,$message,'sent',$result]);
    return true;
}

function wa_notif_pembayaran($id_siswa, $jumlah, $jenis, $metode, $tanggal = null) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT s.*, k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE s.id_siswa = ?");
    $stmt->execute([$id_siswa]);
    $siswa = $stmt->fetch();
    
    $setting = $pdo->query("SELECT nama_sekolah FROM tbl_setting WHERE id=1")->fetch();
    $phone = $siswa['no_hp_ortu'] ?? $siswa['no_hp_siswa'] ?? '';
    if (!$phone) return;
    
    $tgl = $tanggal ? $tanggal : date('Y-m-d');
    
    $msg = "Assalamu'alaikum Bapak/Ibu Wali Murid 🙏\n\n";
    $msg .= "Kami informasikan bahwa pembayaran atas nama:\n\n";
    $msg .= "👤 Nama Siswa : {$siswa['nama']}\n";
    $msg .= "🏫 Kelas      : {$siswa['nama_kelas']}\n";
    $msg .= "💳 Jenis Tagihan : $jenis\n";
    $msg .= "💰 Jumlah     : " . rupiah($jumlah) . "\n";
    $msg .= "📅 Tanggal    : " . tgl_indo($tgl) . "\n";
    $msg .= "💵 Metode     : $metode\n\n";
    $msg .= "✅ *Pembayaran telah DITERIMA dan tercatat.*\n\n";
    $msg .= "Terima kasih atas kepercayaan Bapak/Ibu. Semoga {$siswa['nama']} kita menjadi anak yang berprestasi 🌟\n\n";
    $msg .= "Wassalamu'alaikum\n";
    $msg .= "_{$setting['nama_sekolah']}_";
    
    send_wa($phone, $msg);

    // Push PWA Notifikasi
    $pesan_pendek = "Pembayaran " . $jenis . " sebesar " . rupiah($jumlah) . " telah diterima.";
    $pdo->prepare("INSERT INTO tbl_notifikasi_pwa (id_user, role, judul, pesan) VALUES (?, 'siswa', ?, ?)")->execute([$id_siswa, 'Pembayaran Diterima', $pesan_pendek]);
}

function wa_notif_tabungan($id_siswa, $jenis_transaksi, $jumlah, $keterangan, $saldo_akhir, $biaya_admin = 0) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT s.*, k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE s.id_siswa = ?");
    $stmt->execute([$id_siswa]);
    $siswa = $stmt->fetch();
    $setting = $pdo->query("SELECT nama_sekolah FROM tbl_setting WHERE id=1")->fetch();
    $phone = $siswa['no_hp_ortu'] ?? $siswa['no_hp_siswa'] ?? '';
    if (!$phone) return;
    
    $tgl = date('d/m/Y H:i');
    
    $msg = "Assalamu'alaikum Bapak/Ibu Wali Murid 🙏\n\n";
    $msg .= "Kami informasikan bahwa terdapat mutasi *TABUNGAN* atas nama:\n\n";
    $msg .= "👤 Nama Siswa : {$siswa['nama']}\n";
    $msg .= "🏫 Kelas      : {$siswa['nama_kelas']}\n";
    if ($jenis_transaksi == 'Setor') {
        $msg .= "💳 Jenis      : 🟢 SETORAN\n";
    } else {
        $msg .= "💳 Jenis      : 🔴 PENARIKAN\n";
    }
    $msg .= "💰 Jumlah     : " . rupiah($jumlah) . "\n";
    if ($biaya_admin > 0) {
        $msg .= "⚙️ Biaya Admin: " . rupiah($biaya_admin) . "\n";
    }
    $msg .= "📝 Keterangan : " . ($keterangan ? $keterangan : '-') . "\n";
    $msg .= "📅 Waktu      : $tgl\n\n";
    $msg .= "✅ *Transaksi berhasil dicatat.*\n\n";
    $msg .= "📊 Saldo Akhir saat ini: *" . rupiah($saldo_akhir) . "*\n\n";
    $msg .= "Terima kasih atas perhatian Bapak/Ibu. 🌟\n\n";
    $msg .= "Wassalamu'alaikum\n";
    $msg .= "_{$setting['nama_sekolah']}_";
    
    send_wa($phone, $msg);

    // Push PWA Notifikasi
    $judulPwa = $jenis_transaksi == 'Setor' ? 'Setoran Tabungan Berhasil' : 'Penarikan Tabungan';
    $pesan_pendek = "Mutasi " . $jenis_transaksi . " tunai sebesar " . rupiah($jumlah) . " berhasil. Saldo saat ini: " . rupiah($saldo_akhir);
    $pdo->prepare("INSERT INTO tbl_notifikasi_pwa (id_user, role, judul, pesan) VALUES (?, 'siswa', ?, ?)")->execute([$id_siswa, $judulPwa, $pesan_pendek]);
}

function wa_notif_absensi($id_siswa, $status, $jam) {
    global $pdo;
    $siswa = $pdo->query("SELECT * FROM tbl_siswa WHERE id_siswa=$id_siswa")->fetch();
    $phone = $siswa['no_hp_ortu'] ?? '';
    if (!$phone) return;
    $msg = "🏫 *SIPANDA - Notifikasi Absensi*\n\n";
    $msg .= "Yth. Orang Tua/Wali dari *{$siswa['nama']}*\n\n";
    $msg .= "Anak Anda telah $status pada jam $jam.\n\n";
    $msg .= "Terima kasih. 🙏";
    send_wa($phone, $msg);

    // Push PWA Notifikasi
    $pesan_pendek = "Siswa telah melakukan tapping/absen " . $status . " pada pukul " . $jam . ".";
    $pdo->prepare("INSERT INTO tbl_notifikasi_pwa (id_user, role, judul, pesan) VALUES (?, 'siswa', ?, ?)")->execute([$id_siswa, 'Notifikasi Kehadiran', $pesan_pendek]);
}

function wa_notif_sertifikasi($id_siswa, $jenis, $predikat) {
    global $pdo;
    $siswa = $pdo->query("SELECT * FROM tbl_siswa WHERE id_siswa=$id_siswa")->fetch();
    $phone = $siswa['no_hp_ortu'] ?? '';
    if (!$phone) return;
    $msg = "🏫 *SIPANDA - Kabar Gembira Keagamaan*\n\n";
    $msg .= "Yth. Orang Tua/Wali dari *{$siswa['nama']}*\n\n";
    $msg .= "Alhamdulillah, putra/putri Bapak/Ibu telah lulus sertifikasi Al-Qur'an/Keagamaan:\n\n";
    $msg .= "📋 Program: *$jenis*\n";
    $msg .= "🏆 Predikat: *$predikat*\n";
    $msg .= "📅 Tanggal: " . tgl_indo(date('Y-m-d')) . "\n\n";
    $msg .= "Semoga menjadi ilmu yang barokah dan bermanfaat. Amin. 🙏";
    send_wa($phone, $msg);

    // Push PWA Notifikasi
    $pesan_pendek = "Lulus Sertifikasi " . $jenis . " dengan predikat " . $predikat . ". Selamat atas pencapaiannya!";
    $pdo->prepare("INSERT INTO tbl_notifikasi_pwa (id_user, role, judul, pesan) VALUES (?, 'siswa', ?, ?)")->execute([$id_siswa, 'Prestasi Keagamaan', $pesan_pendek]);
}

function wa_notif_prestasi($id_siswa, $nama_prestasi, $tingkat, $jenis) {
    global $pdo;
    $siswa = $pdo->query("SELECT * FROM tbl_siswa WHERE id_siswa=$id_siswa")->fetch();
    $setting = $pdo->query("SELECT nama_sekolah FROM tbl_setting WHERE id=1")->fetch();
    $phone = $siswa['no_hp_ortu'] ?? $siswa['no_hp_siswa'] ?? '';
    if (!$phone) return;
    
    $msg = "🏫 *Kabar Gembira Prestasi SIPANDA*\n\n";
    $msg .= "Yth. Bapak/Ibu Wali dari *{$siswa['nama']}*\n\n";
    $msg .= "Alhamdulillah, putra/putri Bapak/Ibu telah menorehkan prestasi gemilang:\n\n";
    $msg .= "🏆 *Nama Prestasi:* $nama_prestasi\n";
    $msg .= "🌟 *Tingkat:* $tingkat\n";
    $msg .= "📋 *Kategori:* $jenis\n\n";
    $msg .= "Semoga prestasi ini menjadi kebanggaan kita bersama dan terus memotivasi ananda. 🙏\n\n";
    $msg .= "Wassalamu'alaikum\n_{$setting['nama_sekolah']}_";
    
    send_wa($phone, $msg);

    // Push PWA Notifikasi
    $pesan_pendek = "Selamat! Anda baru saja meraih prestasi: " . $nama_prestasi . " Tingkat " . $tingkat . ".";
    $pdo->prepare("INSERT INTO tbl_notifikasi_pwa (id_user, role, judul, pesan) VALUES (?, 'siswa', ?, ?)")->execute([$id_siswa, 'Prestasi Baru', $pesan_pendek]);
}

function wa_notif_bk($id_siswa, $jenis, $deskripsi) {
    global $pdo;
    $siswa = $pdo->query("SELECT * FROM tbl_siswa WHERE id_siswa=$id_siswa")->fetch();
    $setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
    $ta_aktif = $pdo->query("SELECT id_ta FROM tbl_tahun_ajaran WHERE status='aktif' LIMIT 1")->fetchColumn();
    $id_ta = $ta_aktif ?: 0;
    
    // Hitung Poin Kumulatif Tahun Ini
    $stmt = $pdo->prepare("SELECT SUM(poin) FROM tbl_bk WHERE id_siswa=? AND id_ta=?");
    $stmt->execute([$id_siswa, $id_ta]);
    $total_poin = $stmt->fetchColumn() ?: 0;

    $phone = $siswa['no_hp_ortu'] ?? $siswa['no_hp_siswa'] ?? '';
    if (!$phone) return;
    
    $emoji = ($jenis == 'Pelanggaran') ? '⚠️' : 'ℹ️';
    $msg = "🏫 *Informasi Bimbingan Konseling SIPANDA*\n\n";
    $msg .= "Yth. Bapak/Ibu Wali dari *{$siswa['nama']}*\n\n";
    $msg .= "Menginformasikan perkembangan ananda sebagai berikut:\n\n";
    $msg .= "$emoji *Jenis:* $jenis\n";
    $msg .= "📝 *Keterangan:* $deskripsi\n";
    $msg .= "📊 *Total Poin Tahun Ini:* {$total_poin} Poin\n\n";

    // Cek Status SP
    if ($total_poin >= ($setting['bk_sp3_min']??100)) {
        $msg .= "🔴 *STATUS: SURAT PERINGATAN 3 (SP 3)*\n";
        $msg .= "_Mohon segera menghadap ke sekolah untuk tindak lanjut._\n\n";
    } elseif ($total_poin >= ($setting['bk_sp2_min']??75)) {
        $msg .= "🟠 *STATUS: SURAT PERINGATAN 2 (SP 2)*\n\n";
    } elseif ($total_poin >= ($setting['bk_sp1_min']??50)) {
        $msg .= "🟡 *STATUS: SURAT PERINGATAN 1 (SP 1)*\n\n";
    }

    $msg .= "Demikian informasi ini kami sampaikan untuk menjadi perhatian bersama. 🙏\n\n";
    $msg .= "Wassalamu'alaikum\n_{$setting['nama_sekolah']}_";
    
    send_wa($phone, $msg);

    // Push PWA Notifikasi
    $pwa_msg = "Catatan BK Baru: $deskripsi. Total Poin: $total_poin.";
    $pdo->prepare("INSERT INTO tbl_notifikasi_pwa (id_user, role, judul, pesan) VALUES (?, 'siswa', ?, ?)")->execute([$id_siswa, 'Bimbingan Konseling', $pwa_msg]);
}

function wa_notif_uks($id_kunjungan, $event_type) {
    global $pdo;
    $setting = $pdo->query("SELECT nama_sekolah FROM tbl_setting WHERE id=1")->fetch();
    
    $k = $pdo->query("
        SELECT k.*, s.nama as nama_user, kl.nama_kelas, o.nama_obat 
        FROM tbl_uks_kunjungan k 
        LEFT JOIN tbl_siswa s ON k.id_user = s.id_siswa AND k.role_user = 'siswa'
        LEFT JOIN tbl_kelas kl ON s.id_kelas = kl.id_kelas
        LEFT JOIN tbl_uks_obat o ON k.id_obat = o.id_obat
        WHERE k.id_kunjungan = " . (int)$id_kunjungan
    )->fetch();

    if (!$k) return;

    if ($event_type === 'request') {
        // To UKS Teachers
        $teachers = $pdo->query("SELECT no_hp, nama FROM tbl_guru WHERE tugas_tambahan = 'Waka UKS' AND status = 'Aktif'")->fetchAll();
        $msg = "🏥 *UKS SIPANDA: Permintaan Obat Baru*\n\n";
        $msg .= "👤 Pengaju: *{$k['nama_user']}*\n";
        $msg .= "🏫 Kelas: {$k['nama_kelas']}\n";
        $msg .= "📝 Keluhan: {$k['keluhan']}\n";
        $msg .= "📅 Waktu: " . date('H:i', strtotime($k['jam'])) . "\n\n";
        $msg .= "Mohon segera diproses melalui dashboard UKS. 🙏";
        
        foreach ($teachers as $t) {
            if ($t['no_hp']) send_wa($t['no_hp'], $msg);
        }
    } elseif ($event_type === 'status_update') {
        // To Parent/Student
        $siswa = $pdo->query("SELECT no_hp_siswa, no_hp_ortu FROM tbl_siswa WHERE id_siswa = " . $k['id_user'])->fetch();
        $phone = $siswa['no_hp_ortu'] ?: $siswa['no_hp_siswa'] ?: '';
        if (!$phone) return;

        $msg = "🏫 *SIPANDA - Update Layanan UKS*\n\n";
        $msg .= "Yth. Orang Tua/Wali dari *{$k['nama_user']}*\n\n";
        $msg .= "Informasi penanganan kesehatan ananda:\n";
        $msg .= "📋 Jenis: " . ($k['tipe'] == 'Kunjungan' ? 'Penanganan di UKS' : 'Permintaan Obat') . "\n";
        
        $status_label = $k['status'];
        if ($status_label === 'Dirawat') $status_label = 'Sedang Diperiksa';
        if ($status_label === 'Menunggu') $status_label = 'Menunggu Antrean';

        $msg .= "📊 Status: *{$status_label}*\n";
        if ($k['nama_obat']) $msg .= "💊 Obat: {$k['nama_obat']} ({$k['jumlah_obat']} pcs)\n";
        if ($k['diagnosa']) $msg .= "📝 Instruksi: {$k['diagnosa']}\n";
        $msg .= "\nSemoga lekas sembuh. 🙏\n_{$setting['nama_sekolah']}_";

        send_wa($phone, $msg);
    } elseif ($event_type === 'tap_manual') {
        // Dual Notification: To Teacher & Parent
        // 1. To Waka UKS
        $teachers = $pdo->query("SELECT no_hp FROM tbl_guru WHERE tugas_tambahan = 'Waka UKS' AND status = 'Aktif'")->fetchAll();
        $msg_t = "🏥 *UKS SIPANDA: Kunjungan Mandiri (RFID)*\n\n";
        $msg_t .= "👤 Nama: *{$k['nama_user']}*\n";
        $msg_t .= "🏫 Kelas: {$k['nama_kelas']}\n";
        $msg_t .= "⌚ Waktu: " . date('H:i') . " WIB\n\n";
        $msg_t .= "Ananda baru saja melakukan Tap Kartu di terminal UKS. Mohon dilakukan pemeriksaan. 🙏";
        foreach ($teachers as $t) { if ($t['no_hp']) send_wa($t['no_hp'], $msg_t); }

        // 2. To Parent
        $siswa = $pdo->query("SELECT no_hp_ortu FROM tbl_siswa WHERE id_siswa = " . $k['id_user'])->fetch();
        if ($siswa['no_hp_ortu']) {
            $msg_p = "🏫 *Informasi UKS SIPANDA*\n\n";
            $msg_p .= "Yth. Orang Tua/Wali dari *{$k['nama_user']}*,\n\n";
            $msg_p .= "Menginformasikan bahwa putra/putri Bapak/Ibu saat ini sedang berada di UKS untuk pemeriksaan kesehatan (Kunjungan Mandiri via RFID).\n\n";
            $msg_p .= "Petugas UKS akan segera melakukan tindak lanjut. Terima kasih. 🙏\n_{$setting['nama_sekolah']}_";
            send_wa($siswa['no_hp_ortu'], $msg_p);
        }
    }
}

function wa_notif_fisik($id_user, $id_fisik) {
    global $pdo;
    $setting = $pdo->query("SELECT nama_sekolah FROM tbl_setting WHERE id=1")->fetch();
    $s = $pdo->query("SELECT s.*, kl.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas kl ON s.id_kelas=kl.id_kelas WHERE s.id_siswa=$id_user")->fetch();
    $f = $pdo->query("SELECT * FROM tbl_uks_fisik WHERE id_fisik=$id_fisik")->fetch();
    if (!$s || !$f) return;

    $phone = $s['no_hp_ortu'] ?: $s['no_hp_siswa'] ?: '';
    if (!$phone) return;

    $msg = "📐 *SIPANDA - Update Fisik Berkala*\n\n";
    $msg .= "Yth. Orang Tua/Wali dari *{$s['nama']}*\n\n";
    $msg .= "Baru saja dilakukan penimbangan berkala:\n";
    $msg .= "⚖️ Berat: {$f['berat']} Kg\n";
    $msg .= "📏 Tinggi: {$f['tinggi']} Cm\n";
    $msg .= "📊 BMI: " . number_format($f['bmi']??0, 1) . "\n";
    $msg .= "✨ Status: *{$f['status_gizi']}*\n\n";
    $msg .= "Mari terus pantau pertumbuhan ananda. 🙏\n_{$setting['nama_sekolah']}_";

    send_wa($phone, $msg);
}

function wa_notif_izin_siswa($id_siswa, $keterangan, $alasan) {
    global $pdo;
    $siswa = $pdo->query("SELECT s.nama, k.id_kelas, k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE s.id_siswa=$id_siswa")->fetch();
    if (!$siswa) return;

    // Cari Wali Kelas
    $wali = $pdo->prepare("SELECT no_hp, nama FROM tbl_guru WHERE id_kelas_wali = ? AND status = 'Aktif' LIMIT 1");
    $wali->execute([$siswa['id_kelas']]);
    $w = $wali->fetch();

    if ($w && !empty($w['no_hp'])) {
        $msg = "🏫 *SIPANDA - Pemberitahuan Izin/Sakit Siswa*\n\n";
        $msg .= "Yth. Bapak/Ibu Wali Kelas *{$w['nama']}*,\n\n";
        $msg .= "Menginformasikan bahwa siswa Anda:\n";
        $msg .= "👤 Nama: *{$siswa['nama']}*\n";
        $msg .= "🏫 Kelas: {$siswa['nama_kelas']}\n";
        $msg .= "📝 Status: *$keterangan*\n";
        $msg .= "📄 Alasan: $alasan\n";
        $msg .= "📅 Tanggal: " . tgl_indo(date('Y-m-d')) . "\n\n";
        $msg .= "Demikian informasi ini kami sampaikan. Terima kasih. 🙏";
        send_wa($w['no_hp'], $msg);
    }
}

function wa_notif_izin_guru($id_guru, $keterangan, $alasan) {
    global $pdo;
    $guru = $pdo->query("SELECT nama FROM tbl_guru WHERE id_guru=$id_guru")->fetch();
    $setting = $pdo->query("SELECT wa_kepsek, kepsek FROM tbl_setting WHERE id=1")->fetch();
    
    if ($setting && !empty($setting['wa_kepsek'])) {
        $msg = "🏫 *SIPANDA - Laporan Kesehatan Guru*\n\n";
        $msg .= "Yth. Bapak Kepala Sekolah (*{$setting['kepsek']}*),\n\n";
        $msg .= "Menginformasikan laporan kesehatan dari:\n";
        $msg .= "👤 Nama Guru: *{$guru['nama']}*\n";
        $msg .= "📝 Keterangan: *$keterangan*\n";
        $msg .= "📄 Detail: $alasan\n";
        $msg .= "📅 Waktu: " . date('d/m/Y H:i') . " WIB\n\n";
        $msg .= "Mohon menjadi maklum. Terima kasih. 🙏";
        send_wa($setting['wa_kepsek'], $msg);
    }
}

function wa_notif_presensi_guru($id_guru, $status, $jam) {
    global $pdo;
    $guru = $pdo->query("SELECT nama FROM tbl_guru WHERE id_guru=$id_guru")->fetch();
    $sh = $pdo->query("SELECT wa_kepsek, wa_tu, nama_sekolah FROM tbl_setting WHERE id=1")->fetch();

    $msg = "🏫 *SIPANDA - Presensi Guru & Staf*\n\n";
    $msg .= "Menginformasikan kehadiran GTK:\n";
    $msg .= "👤 Nama: *{$guru['nama']}*\n";
    $msg .= "🔘 Status: *$status*\n";
    $msg .= "⌚ Jam: $jam WIB\n";
    $msg .= "📅 Tanggal: " . tgl_indo(date('Y-m-d')) . "\n\n";
    $msg .= "_{$sh['nama_sekolah']}_";

    if (!empty($sh['wa_kepsek'])) send_wa($sh['wa_kepsek'], $msg);
    if (!empty($sh['wa_tu'])) send_wa($sh['wa_tu'], $msg);
}
