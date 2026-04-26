<?php
// Sidebar Navigation - Dynamic per role
$role = $_SESSION['role'] ?? 'admin';
$current = basename($_SERVER['PHP_SELF']);
$dir = basename(dirname($_SERVER['PHP_SELF']));

$menus = [
    'admin' => [
        ['Dashboard', 'index-admin.php', 'fa-home'],
        ['MASTER DATA', 'fa-database', [
            ['Tahun Ajaran', 'admin/tahun_ajaran.php'],
            ['Kelas', 'admin/kelas.php'],
            ['Siswa', 'admin/siswa.php'],
            ['Guru', 'admin/guru.php'],
            ['Guru BK', 'admin/guru_bk.php'],
            ['Jam Pelajaran', 'admin/jam.php'],
            ['Kenaikan Kelas', 'admin/kenaikan_kelas.php'],
            ['Kelulusan', 'admin/kelulusan.php'],
            ['SKL', 'admin/skl.php'],
            ['Setting Absen', 'admin/setting_absen.php'],
        ]],
        ['KEUANGAN', 'fa-wallet', [
            ['Pos Bayar', 'admin/pos_bayar.php'],
            ['Jenis Bayar', 'admin/jenis_bayar.php'],
            ['Tarif', 'admin/tarif.php'],
            ['Pembayaran', 'admin/pembayaran.php'],
            ['Hutang Piutang', 'admin/hutang.php'],
        ]],
        ['KAS', 'fa-box-open', [
            ['Realisasi', 'admin/com_kas/realisasi.php'],
            ['Jenis Masuk', 'admin/com_kas/jenis_masuk.php'],
            ['Jenis Keluar', 'admin/com_kas/jenis_keluar.php'],
            ['Penerimaan Kas', 'admin/com_kas/penerimaan.php'],
            ['Pengeluaran Kas', 'admin/com_kas/pengeluaran.php'],
        ]],
        ['TABUNGAN', 'fa-piggy-bank', [
            ['Nasabah', 'admin/com_transaksi/nasabah.php'],
            ['Transaksi', 'admin/com_transaksi/transaksi.php'],
            ['Lap. Transaksi', 'admin/com_transaksi/laporan.php'],
            ['Lap. Saldo Akhir', 'admin/com_transaksi/laporan_saldo.php'],
        ]],
        ['AKADEMIK', 'fa-graduation-cap', [
            ['Mata Pelajaran', 'admin/com_sease/mapel.php'],
            ['Jadwal', 'admin/com_sease/jadwal.php'],
            ['Bahan & Tugas', 'admin/com_sease/bahan_tugas.php'],
            ['Journal', 'admin/com_sease/journal.php'],
            ['Quiz/Ujian', 'admin/com_sease/quiz.php'],
            ['Nilai Siswa', 'admin/com_sease/raport.php'],
        ]],
        ['KESISWAAN', 'fa-users', [
            ['Prestasi', 'admin/prestasi.php'],
            ['Bimbingan (BK)', 'admin/bk.php'],
            ['Eskul', 'admin/eskul.php'],
        ]],
        ['SARPRAS', 'fa-boxes', [
            ['Data Barang', 'admin/com_sarpras/barang.php'],
            ['Pemeliharaan', 'admin/com_sarpras/pemeliharaan.php'],
        ]],
        ['HUMAS', 'fa-handshake', [
            ['Data MOU', 'admin/com_humas/kemitraan.php'],
            ['Buku Tamu', 'admin/com_humas/tamu.php'],
        ]],
        ['UNIT KESEHATAN', 'fa-hand-holding-medical', [
            ['Dashboard UKS', 'admin/com_uks/uks.php'],
            ['Stok Obat', 'admin/com_uks/obat.php'],
        ]],
        ['BINA AGAMA', 'fa-mosque', [
            ['Jadwal Kegiatan', 'admin/com_agama/kegiatan.php'],
            ['Rekap Infaq', 'admin/com_agama/infaq.php'],
            ['Sertifikasi', 'admin/com_agama/sertifikasi.php'],
        ]],
        ['ABSENSI', 'fa-user-check', [
            ['Absensi Siswa', 'admin/absensi_siswa.php'],
            ['Absensi Guru', 'admin/absensi_guru.php'],
            ['Absensi Eskul', 'admin/absensi_eskul.php'],
        ]],
        ['LAPORAN', 'fa-file-alt', [
            ['Lap. Data Siswa', 'admin/com_laporan/lap_siswa.php'],
            ['Lap. Per Kelas', 'admin/com_laporan/lap_per_kelas.php'],
            ['Lap. Per Bulan', 'admin/com_laporan/lap_per_bulan.php'],
            ['Lap. Per Pos', 'admin/com_laporan/lap_per_pos.php'],
            ['Lap. Tagihan', 'admin/com_laporan/lap_tagihan.php'],
            ['Rekap Bayar', 'admin/com_laporan/rekap_pembayaran.php'],
            ['Lap. Harian', 'admin/com_laporan/lap_harian.php'],
            ['Rekap Absensi Siswa', 'admin/com_laporan/lap_rekap_absensi.php'],
            ['Rekap Absensi Guru', 'admin/com_laporan/lap_rekap_absensi_guru.php'],
            ['Rekap Keluar', 'admin/com_laporan/rekap_pengeluaran.php'],
            ['Kondisi Keuangan', 'admin/com_laporan/kondisi_keuangan.php'],
        ]],
        ['E-KANTIN', 'fa-store', [
            ['Kasir (POS)', 'admin/kasir.php'],
            ['Produk Kantin', 'admin/produk.php'],
            ['Kategori Produk', 'admin/kategori.php'],
            ['Top-up Manual', 'admin/topup_manual.php'],
            ['Konfirmasi Top-up', 'admin/topup_konfirmasi.php'],
            ['Penarikan Guru', 'admin/penarikan_guru.php'],
        ]],
        ['LAINNYA', 'fa-cogs', [
            ['Galeri Sekolah', 'admin/gallery.php'],
            ['Kelola Kerjasama', 'admin/kerjasama.php'],
            ['Kelola Keunggulan', 'admin/keunggulan.php'],
            ['Display Info', 'admin/display_info.php'],
            ['Pengguna', 'admin/pengguna.php'],
            ['Pengaturan', 'admin/pengaturan.php'],
            ['Backup DB', 'admin/backup.php'],
            ['Update Sistem', 'admin/update.php'],
        ]],
        ['PERPUSTAKAAN', 'fa-book-open', [
            ['Kategori Buku', 'admin/lib_kategori.php'],
            ['Koleksi Buku', 'admin/lib_buku.php'],
            ['Sirkulasi (Pinjam)', 'admin/lib_transaksi.php'],
        ]],
        ['PAYROLL GURU', 'fa-file-invoice-dollar', [
            ['Setting Payroll', 'admin/payroll_settings.php'],
            ['Config Gaji Guru', 'admin/payroll_config.php'],
            ['Generate Gaji', 'admin/payroll_generate.php'],
            ['Riwayat Gaji', 'admin/payroll_history.php'],
        ]],
    ],
    'kasir' => [
        ['Dashboard', 'index-kasir.php', 'fa-home'],
        ['E-KANTIN', 'fa-store', [
            ['Kasir (POS)', 'admin/kasir.php'],
            ['Produk Kantin', 'admin/produk.php'],
            ['Kategori Produk', 'admin/kategori.php'],
            ['Top-up Manual', 'admin/topup_manual.php'],
            ['Konfirmasi Top-up', 'admin/topup_konfirmasi.php'],
        ]],
        ['PROFIL', 'fa-user', [
            ['Ubah Profil', 'kasir/profil.php'],
        ]],
    ],
    'petugas' => [
        ['Dashboard', 'index-petugas.php', 'fa-home'],
        ['PERPUSTAKAAN', 'fa-book-open', [
            ['Kategori Buku', 'admin/lib_kategori.php'],
            ['Koleksi Buku', 'admin/lib_buku.php'],
            ['Sirkulasi (Pinjam)', 'admin/lib_transaksi.php'],
        ]],
        ['PROFIL', 'fa-user', [
            ['Ubah Profil', 'admin/profil.php'],
        ]],
    ],
    'bendahara' => [
        ['Dashboard', 'index-bendahara.php', 'fa-home'],
        ['KEUANGAN', 'fa-wallet', [
            ['Pembayaran', 'admin/pembayaran.php'],
            ['Pos Bayar', 'admin/pos_bayar.php'],
            ['Jenis Bayar', 'admin/jenis_bayar.php'],
            ['Tarif', 'admin/tarif.php'],
        ]],
        ['KAS', 'fa-box-open', [
            ['Penerimaan Kas', 'admin/com_kas/penerimaan.php'],
            ['Pengeluaran Kas', 'admin/com_kas/pengeluaran.php'],
        ]],
        ['TABUNGAN', 'fa-piggy-bank', [
            ['Nasabah', 'admin/com_transaksi/nasabah.php'],
            ['Transaksi', 'admin/com_transaksi/transaksi.php'],
            ['Lap. Transaksi', 'admin/com_transaksi/laporan.php'],
            ['Lap. Saldo Akhir', 'admin/com_transaksi/laporan_saldo.php'],
        ]],
        ['KESISWAAN', 'fa-users', [
            ['Prestasi', 'admin/prestasi.php'],
        ]],
        ['LAPORAN', 'fa-file-alt', [
            ['Lap. Per Kelas', 'admin/com_laporan/lap_per_kelas.php'],
            ['Lap. Per Bulan', 'admin/com_laporan/lap_per_bulan.php'],
            ['Rekap Bayar', 'admin/com_laporan/rekap_pembayaran.php'],
            ['Rekap Absensi Siswa', 'admin/com_laporan/lap_rekap_absensi.php'],
            ['Rekap Absensi Guru', 'admin/com_laporan/lap_rekap_absensi_guru.php'],
            ['Kondisi Keuangan', 'admin/com_laporan/kondisi_keuangan.php'],
        ]],
        ['E-KANTIN', 'fa-store', [
            ['Penarikan Guru', 'admin/penarikan_guru.php'],
            ['Konfirmasi Top-up', 'admin/topup_konfirmasi.php'],
        ]],
        ['PAYROLL GURU', 'fa-file-invoice-dollar', [
            ['Generate Gaji', 'admin/payroll_generate.php'],
            ['Riwayat Gaji', 'admin/payroll_history.php'],
        ]],
    ],
    'kepsek' => [
        ['Dashboard', 'index-kepsek.php', 'fa-home'],
        ['MONITORING', 'fa-desktop', [
            ['Lap. Per Kelas', 'admin/com_laporan/lap_per_kelas.php'],
            ['Lap. Per Bulan', 'admin/com_laporan/lap_per_bulan.php'],
            ['Rekap Bayar', 'admin/com_laporan/rekap_pembayaran.php'],
            ['Rekap Absensi Siswa', 'admin/com_laporan/lap_rekap_absensi.php'],
            ['Rekap Absensi Guru', 'admin/com_laporan/lap_rekap_absensi_guru.php'],
            ['Kondisi Keuangan', 'admin/com_laporan/kondisi_keuangan.php'],
            ['Absensi Siswa', 'admin/absensi_siswa.php'],
            ['Absensi Guru', 'admin/absensi_guru.php'],
            ['Data Siswa', 'admin/siswa.php'],
            ['Data Guru', 'admin/guru.php'],
            ['Riwayat Payroll', 'admin/payroll_history.php'],
        ]],
    ],
    'guru' => [
        ['Dashboard', 'index-guru.php', 'fa-home'],
        ['AKADEMIK', 'fa-graduation-cap', [
            ['Jadwal', 'guru/jadwal.php'],
            ['Journal Mengajar', 'guru/journal.php'],
            ['Generator RPP', 'guru/rpp.php'],
            ['Generator Soal AI', 'guru/generator_soal.php'],
            ['Bahan & Tugas', 'guru/bahan_tugas.php'],
            ['Quiz/Ujian', 'guru/quiz.php'],
            ['Daftar Nilai', 'guru/raport.php'],
        ]],
        ['KESISWAAN', 'fa-users', [
            ['Absensi Siswa', 'guru/absensi.php'],
            ['Bimbingan (BK)', 'guru/bk.php'],
            ['Prestasi', 'guru/prestasi.php'],
            ['Eskul', 'guru/eskul.php'],
        ]],
        ['PAYROLL', 'fa-wallet', [
            ['Slip Gaji Saya', 'guru/payroll.php'],
        ]],
        ['E-KANTIN', 'fa-store', [
            ['Kantinku', 'guru/kantin.php'],
            ['Titip Jualan', 'guru/titip_jualan.php'],
            ['Dompet Penjual', 'guru/dompet_penjual.php'],
        ]],
        ['PENGATURAN', 'fa-cog', [
            ['Profil', 'guru/profil.php'],
        ]],
        ['Perpustakaan', 'guru/perpustakaan.php', 'fa-book-open'],
        ['Kesehatan', 'guru/kesehatan.php', 'fa-hand-holding-medical'],
    ],
    'siswa' => [
        ['Dashboard', 'index-siswa.php', 'fa-home'],
        ['Tagihan', 'siswa/tagihan.php', 'fa-file-invoice-dollar'],
        ['Absensi', 'siswa/absensi.php', 'fa-user-check'],
        ['Tugas', 'siswa/tugas.php', 'fa-tasks'],
        ['Quiz/Ujian', 'siswa/quiz.php', 'fa-question-circle'],
        ['Daftar Nilai', 'siswa/raport.php', 'fa-star'],
        ['Prestasi', 'siswa/prestasi.php', 'fa-trophy'],
        ['Eskul', 'siswa/eskul.php', 'fa-futbol'],
        ['Tabungan', 'siswa/tabungan.php', 'fa-piggy-bank'],
        ['E-KANTIN', 'fa-store', [
            ['Saldo & Riwayat', 'siswa/kantin.php'],
            ['Top-up Saldo', 'siswa/topup.php'],
        ]],
        ['Kesehatan', 'siswa/kesehatan.php', 'fa-hand-holding-medical'],
        ['Perpustakaan', 'siswa/perpustakaan.php', 'fa-book-open'],
        ['Profil', 'siswa/profil.php', 'fa-user'],
    ],
];
$items = $menus[$role] ?? $menus['admin'];

// ============================================================
// WAKA INJECTION: Guru dengan tugas tambahan mendapat menu admin
// (Harus SEBELUM feature filter agar ikut difilter)
// ============================================================
if ($role === 'guru') {
    $tugas = $_SESSION['tugas_tambahan'] ?? '';
    $injected = [];
    if ($tugas === 'Waka Kurikulum') {
        $injected[] = ['AKADEMIK (ADMIN)', 'fa-university', [
            ['Mata Pelajaran', 'admin/com_sease/mapel.php'],
            ['Jadwal', 'admin/com_sease/jadwal.php'],
            ['Bahan & Tugas', 'admin/com_sease/bahan_tugas.php'],
            ['Master Journal', 'admin/com_sease/journal.php'],
            ['Master Quiz', 'admin/com_sease/quiz.php'],
            ['Master Nilai', 'admin/com_sease/raport.php'],
        ]];
    }
    if ($tugas === 'Waka Kesiswaan') {
        $injected[] = ['KESISWAAN (ADMIN)', 'fa-users-cog', [
            ['Data Prestasi', 'admin/prestasi.php'],
            ['Bimbingan (BK)', 'admin/bk.php'],
            ['Ekstrakurikuler', 'admin/eskul.php'],
        ]];
    }
    if ($tugas === 'Waka Sarpras') {
        $injected[] = ['SARPRAS (ADMIN)', 'fa-boxes', [
            ['Data Barang', 'admin/com_sarpras/barang.php'],
            ['Pemeliharaan', 'admin/com_sarpras/pemeliharaan.php'],
        ]];
    }
    if ($tugas === 'Waka Humas') {
        $injected[] = ['HUMAS (ADMIN)', 'fa-handshake', [
            ['Data MOU/Kemitraan', 'admin/com_humas/kemitraan.php'],
            ['Buku Tamu Instrumen', 'admin/com_humas/tamu.php'],
        ]];
    }
    if ($tugas === 'Waka Keagamaan') {
        $injected[] = ['BINA AGAMA (ADMIN)', 'fa-mosque', [
            ['Jadwal Kegiatan', 'admin/com_agama/kegiatan.php'],
            ['Rekap Dana Infaq', 'admin/com_agama/infaq.php'],
            ['Data Sertifikasi', 'admin/com_agama/sertifikasi.php'],
        ]];
    }
    if ($tugas === 'Waka UKS') {
        $injected[] = ['UNIT KESEHATAN (ADMIN)', 'fa-hand-holding-medical', [
            ['Dashboard UKS', 'admin/com_uks/uks.php'],
            ['Stok Obat', 'admin/com_uks/obat.php'],
        ]];
    }
    if (!empty($injected)) {
        array_splice($items, 1, 0, $injected);
    }
}

// ============================================================
// FEATURE GATING: Filter menu berdasarkan modul yang aktif
// ============================================================
$menu_feature_map = [
    'KEUANGAN'              => 'keuangan',
    'KAS'                   => 'kas',
    'TABUNGAN'              => 'tabungan',
    'AKADEMIK'              => 'akademik',
    'KESISWAAN'             => 'kesiswaan',
    'SARPRAS'               => 'sarpras',
    'HUMAS'                 => 'humas',
    'UNIT KESEHATAN'        => 'uks',
    'BINA AGAMA'            => 'agama',
    'ABSENSI'               => 'absensi',
    'LAPORAN'               => 'laporan',
    'E-KANTIN'              => 'ekantin',
    'PERPUSTAKAAN'          => 'perpustakaan',
    'PAYROLL GURU'          => 'payroll',
    'PAYROLL'               => 'payroll',
    'LAINNYA'               => null,
    'MASTER DATA'           => null,
    'MONITORING'            => null,
    'PENGATURAN'            => null,
    'PROFIL'                => null,
    'AKADEMIK (ADMIN)'      => 'akademik',
    'KESISWAAN (ADMIN)'     => 'kesiswaan',
    'SARPRAS (ADMIN)'       => 'sarpras',
    'HUMAS (ADMIN)'         => 'humas',
    'BINA AGAMA (ADMIN)'    => 'agama',
    'UNIT KESEHATAN (ADMIN)'=> 'uks',
];

$single_menu_feature_map = [
    'Tagihan'       => 'keuangan',
    'Absensi'       => 'absensi',
    'Tabungan'      => 'tabungan',
    'Tugas'         => 'akademik',
    'Quiz/Ujian'    => 'akademik',
    'Daftar Nilai'  => 'akademik',
    'Prestasi'      => 'kesiswaan',
    'Eskul'         => 'kesiswaan',
    'Kesehatan'     => 'uks',
    'Perpustakaan'  => 'perpustakaan',
    'Slip Gaji Saya'=> 'payroll',
];

$items = array_filter($items, function($m) use ($menu_feature_map, $single_menu_feature_map) {
    $label = $m[0];
    if (isset($m[2]) && is_array($m[2])) {
        if (isset($menu_feature_map[$label])) {
            $module_id = $menu_feature_map[$label];
            if ($module_id !== null && !fitur_aktif($module_id)) return false;
        }
        return true;
    }
    if (isset($single_menu_feature_map[$label])) {
        if (!fitur_aktif($single_menu_feature_map[$label])) return false;
    }
    return true;
});
$items = array_values($items);
?>

<!-- Overlay for mobile -->
<div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/60 z-40 hidden md:hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="sidebar fixed md:static z-50 w-64 min-h-screen glass border-r border-white/5 flex flex-col transition-transform duration-300">
    <!-- Logo -->
    <div class="p-5 border-b border-white/5">
        <a href="<?= BASE_URL ?>" class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-blue-500/30">S</div>
            <div>
                <h1 class="text-lg font-bold bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent"><?= APP_NAME ?></h1>
                <p class="text-[10px] text-slate-500"><?= APP_VERSION ?></p>
            </div>
        </a>
    </div>

    <!-- Menu -->
    <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-1">
        <?php foreach ($items as $idx => $m):
            if (isset($m[2]) && is_array($m[2])): 
                // Logical group checking
                $is_group_active = false;
                foreach ($m[2] as $sub) {
                    if (strpos($_SERVER['REQUEST_URI'], $sub[1]) !== false) {
                        $is_group_active = true;
                        break;
                    }
                }
        ?>
            <div class="group-wrapper">
                <button onclick="toggleSubmenu('submenu-<?= $idx ?>', 'icon-submenu-<?= $idx ?>')" class="w-full text-left flex items-center justify-between px-3 py-2.5 rounded-lg text-sm transition-all <?= $is_group_active ? 'text-white bg-white/5' : 'text-slate-300 hover:text-white hover:bg-white/5' ?>">
                    <div class="flex items-center gap-3 flex-1">
                        <i class="fas <?= $m[1] ?> w-5 text-center <?= $is_group_active ? 'text-blue-400' : 'text-slate-400' ?>"></i>
                        <span class="font-medium text-left"><?= $m[0] ?></span>
                    </div>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-300 <?= $is_group_active ? 'rotate-180 text-blue-400' : 'text-slate-500' ?>" id="icon-submenu-<?= $idx ?>"></i>
                </button>
                <div id="submenu-<?= $idx ?>" class="overflow-hidden transition-all duration-300 <?= $is_group_active ? 'max-h-96' : 'max-h-0' ?>">
                    <div class="pl-9 pr-2 py-1 space-y-0.5">
                        <?php foreach ($m[2] as $sub): 
                            $href = BASE_URL . $sub[1];
                            $active = (strpos($_SERVER['REQUEST_URI'], $sub[1]) !== false) ? 'text-white bg-blue-600/20 text-blue-400 font-medium' : 'text-slate-400 hover:text-white';
                        ?>
                        <a href="<?= $href ?>" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs transition-all <?= $active ?>">
                            <i class="fas fa-circle text-[6px] <?= (strpos($_SERVER['REQUEST_URI'], $sub[1]) !== false) ? 'text-blue-400' : 'text-slate-600' ?>"></i>
                            <span><?= $sub[0] ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php else:
            $href = BASE_URL . $m[1];
            $active = (strpos($_SERVER['REQUEST_URI'], $m[1]) !== false) ? 'active text-white bg-blue-600' : 'text-slate-300 hover:text-white hover:bg-white/5';
        ?>
            <a href="<?= $href ?>" class="sidebar-link <?= $active ?> text-left flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all mb-1">
                <i class="fas <?= $m[2] ?> w-5 text-center <?= (strpos($_SERVER['REQUEST_URI'], $m[1]) !== false) ? 'text-white' : 'text-slate-400' ?>"></i>
                <span class="font-medium"><?= $m[0] ?></span>
            </a>
        <?php endif; endforeach; ?>
    </nav>

    <!-- User -->
    <div class="p-4 border-t border-white/5">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold">
                <?= strtoupper(substr($_SESSION['nama'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate"><?= clean($_SESSION['nama'] ?? 'User') ?></p>
                <p class="text-[10px] text-slate-500 capitalize"><?= $_SESSION['role'] ?? '' ?></p>
            </div>
            <a href="<?= BASE_URL ?>logout.php" class="text-slate-400 hover:text-red-400 transition-colors" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
</aside>

<script>
// Expand/Collapse submenu functionality
function toggleSubmenu(submenuId, iconId) {
    const el = document.getElementById(submenuId);
    const icon = document.getElementById(iconId);
    
    // Check current state by max-height
    if (el.classList.contains('max-h-0')) {
        // Expand
        // Optional: you can close all other submenus here if you want an accordion behavior
        const allGroups = document.querySelectorAll('div[id^="submenu-"]');
        const allIcons = document.querySelectorAll('i[id^="icon-submenu-"]');
        
        // Auto-close others for an accordion feel (uncomment if desired)
        /*
        allGroups.forEach(g => {
            if (g.id !== submenuId) {
                g.classList.remove('max-h-96');
                g.classList.add('max-h-0');
            }
        });
        allIcons.forEach(i => {
            if (i.id !== iconId) {
                i.classList.remove('rotate-180');
                i.classList.remove('text-blue-400');
            }
        });
        */
        
        el.classList.remove('max-h-0');
        el.classList.add('max-h-96');
        if (icon) {
            icon.classList.add('rotate-180');
            icon.classList.add('text-blue-400');
        }
    } else {
        // Collapse
        el.classList.remove('max-h-96');
        el.classList.add('max-h-0');
        if (icon) {
            icon.classList.remove('rotate-180');
            icon.classList.remove('text-blue-400');
        }
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const isHidden = sidebar.classList.contains('-translate-x-full');
    
    if (isHidden) {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    } else {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
}
</script>
