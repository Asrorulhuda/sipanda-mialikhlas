<?php
/*
 * FEATURE GATING SYSTEM
 * Helper functions untuk mengecek status fitur/modul yang aktif.
 * ASR Production
 */

/**
 * Install tabel tbl_features jika belum ada & seed data default.
 * Dipanggil otomatis saat pertama kali.
 */
function _features_install($pdo) {
    // Buat tabel jika belum ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS tbl_features (
        id INT AUTO_INCREMENT PRIMARY KEY,
        module_id VARCHAR(50) NOT NULL UNIQUE,
        module_name VARCHAR(100) NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        icon VARCHAR(50) DEFAULT 'fa-puzzle-piece',
        description TEXT,
        sort_order INT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Cek apakah sudah ada data
    $count = $pdo->query("SELECT COUNT(*) FROM tbl_features")->fetchColumn();
    if ($count == 0) {
        // Seed semua modul — default ON (Opsi A)
        $modules = [
            ['keuangan',      'Keuangan (SPP & Pembayaran)',     1, 'fa-wallet',                'Pos bayar, jenis bayar, tarif, pembayaran SPP, hutang piutang, cetak kwitansi, cek tagihan publik', 1],
            ['kas',           'Kas Sekolah',                     1, 'fa-box-open',              'Realisasi kas, penerimaan, pengeluaran, jenis masuk/keluar', 2],
            ['tabungan',      'Tabungan Siswa',                  1, 'fa-piggy-bank',            'Nasabah, setor/tarik, laporan transaksi, saldo akhir', 3],
            ['akademik',      'Akademik & E-Learning',           1, 'fa-graduation-cap',        'Mapel, jadwal, journal mengajar, bahan tugas, quiz/ujian, rapor/nilai', 4],
            ['kesiswaan',     'Kesiswaan',                       1, 'fa-users',                 'Prestasi, bimbingan konseling (BK), ekstrakurikuler', 5],
            ['sarpras',       'Sarana & Prasarana',               1, 'fa-boxes',                 'Inventaris barang, pemeliharaan aset', 6],
            ['humas',         'Humas (Hubungan Masyarakat)',      1, 'fa-handshake',             'Data MOU/kemitraan, buku tamu digital, kiosk mode', 7],
            ['uks',           'Unit Kesehatan Sekolah (UKS)',     1, 'fa-hand-holding-medical',  'Dashboard UKS, stok obat, lapor sakit, notifikasi WA petugas', 8],
            ['agama',         'Bina Agama & Karakter',            1, 'fa-mosque',                'Jadwal kegiatan, rekap infaq, sertifikasi tahfidz', 9],
            ['absensi',       'Absensi (QR, RFID & Manual)',       1, 'fa-user-check',            'Absensi siswa & guru via QR Code, RFID, atau manual. Setting absen, rekap, live presensi', 10],
            ['perpustakaan',  'Perpustakaan Digital',              1, 'fa-book-open',             'Kategori buku, koleksi, sirkulasi pinjam/kembali, kunjungan RFID', 11],
            ['ekantin',       'E-Kantin (Cashless Canteen)',       1, 'fa-store',                 'POS kasir, produk, top-up saldo, limit jajan, titip jualan guru', 12],
            ['payroll',       'Payroll Guru',                      1, 'fa-file-invoice-dollar',   'Setting gaji, config per guru, generate payroll, slip gaji', 13],
            ['ai_rpp',        'AI RPP Generator',                  1, 'fa-robot',                 'Pembuatan RPP otomatis dengan AI, KBC & PEDATTI', 14],
            ['ai_soal',       'AI Generator Soal',                 1, 'fa-magic',                 'Generate bank soal otomatis menggunakan AI', 15],
            ['ai_chat',       'Guru AI Chatbot',                   1, 'fa-comments',              'Widget chat AI interaktif untuk guru & siswa', 16],
            ['laporan',       'Laporan & Cetak',                   1, 'fa-file-alt',              'Semua jenis laporan, rekap, cetak dokumen resmi', 17],
            ['wa_notif',      'Notifikasi WhatsApp',               1, 'fa-whatsapp',              'WA absensi, tagihan, top-up, tamu, UKS, PWA push', 18],
        ];

        $stmt = $pdo->prepare("INSERT INTO tbl_features (module_id, module_name, is_active, icon, description, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($modules as $m) {
            $stmt->execute($m);
        }
    }
}

/**
 * Ambil semua fitur dari database (with cache per request)
 */
function get_all_features($pdo) {
    static $cache = null;
    if ($cache !== null) return $cache;

    try {
        // Auto-install jika tabel belum ada
        $tables = $pdo->query("SHOW TABLES LIKE 'tbl_features'")->fetchColumn();
        if (!$tables) {
            _features_install($pdo);
        }

        $cache = $pdo->query("SELECT * FROM tbl_features ORDER BY sort_order")->fetchAll();
    } catch (Exception $e) {
        $cache = [];
    }
    return $cache;
}

/**
 * Cek apakah sebuah fitur/modul aktif
 * @param string $module_id ID modul (contoh: 'ekantin', 'payroll', 'ai_rpp')
 * @return bool
 */
function fitur_aktif($module_id) {
    global $pdo;
    $features = get_all_features($pdo);
    foreach ($features as $f) {
        if ($f['module_id'] === $module_id) {
            return (bool)$f['is_active'];
        }
    }
    // Modul tidak ditemukan di database → default aktif (safe fallback)
    return true;
}

/**
 * Block akses halaman jika fitur tidak aktif — redirect ke dashboard
 * Panggil di awal file halaman modul: cek_fitur('ekantin');
 * @param string $module_id
 */
function cek_fitur($module_id) {
    if (!fitur_aktif($module_id)) {
        $role = $_SESSION['role'] ?? 'admin';
        $dashboard_map = [
            'admin'     => 'index-admin.php',
            'guru'      => 'index-guru.php',
            'siswa'     => 'index-siswa.php',
            'bendahara' => 'index-bendahara.php',
            'kepsek'    => 'index-kepsek.php',
            'kasir'     => 'index-kasir.php',
            'petugas'   => 'index-petugas.php',
        ];
        $_SESSION['flash']['fitur'] = [
            'msg' => '🔒 Modul ini tidak tersedia dalam paket lisensi Anda. Hubungi ASR Production untuk mengaktifkan.',
            'type' => 'warning'
        ];
        header('Location: ' . BASE_URL . ($dashboard_map[$role] ?? 'index.php'));
        exit;
    }
}

/**
 * Get array of active module IDs
 * @return array
 */
function get_active_features() {
    global $pdo;
    $features = get_all_features($pdo);
    $active = [];
    foreach ($features as $f) {
        if ($f['is_active']) {
            $active[] = $f['module_id'];
        }
    }
    return $active;
}
