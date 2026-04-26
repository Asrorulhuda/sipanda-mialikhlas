<?php
$page_title = 'Dashboard Kepala Sekolah';
require_once __DIR__ . '/config/init.php';
cek_role(['kepsek']);

$today = date('Y-m-d');
$year = date('Y');

$jml_siswa = $pdo->query("SELECT COUNT(*) FROM tbl_siswa WHERE status='Aktif'")->fetchColumn();
$jml_guru = $pdo->query("SELECT COUNT(*) FROM tbl_guru WHERE status='Aktif'")->fetchColumn();
$jml_kelas = $pdo->query("SELECT COUNT(*) FROM tbl_kelas")->fetchColumn();

$s1 = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran WHERE YEAR(tanggal_bayar)=?");
$s1->execute([$year]); $pemasukan = $s1->fetchColumn();

$s2 = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM tbl_pengeluaran_kas WHERE YEAR(tanggal)=?");
$s2->execute([$year]); $pengeluaran = $s2->fetchColumn();

$selisih = $pemasukan - $pengeluaran;

// Absensi hari ini
$abs1 = $pdo->prepare("SELECT COUNT(DISTINCT id_siswa) FROM tbl_absensi_siswa WHERE tanggal=?");
$abs1->execute([$today]); $hadir = $abs1->fetchColumn();

$abs2 = $pdo->prepare("SELECT COUNT(DISTINCT id_siswa) FROM tbl_absensi_siswa WHERE tanggal=? AND keterangan='Terlambat'");
$abs2->execute([$today]); $telat = $abs2->fetchColumn();

$guru_hadir = $pdo->prepare("SELECT COUNT(DISTINCT id_guru) FROM tbl_absensi_guru WHERE tanggal=?");
$guru_hadir->execute([$today]); $guru_hadir_count = $guru_hadir->fetchColumn();

// Chart pemasukan per bulan
$chart_data = [];
for ($m=1;$m<=12;$m++) {
    $cm = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran WHERE MONTH(tanggal_bayar)=? AND YEAR(tanggal_bayar)=?");
    $cm->execute([$m,$year]); $chart_data[] = (int)$cm->fetchColumn();
}

require_once __DIR__ . '/template/header.php';
require_once __DIR__ . '/template/sidebar.php';
require_once __DIR__ . '/template/topbar.php';
?>

<!-- Welcome -->
<div class="glass rounded-xl p-5 mb-6 border border-purple-500/20">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold">Selamat Datang, <?= clean($setting['kepsek'] ?? $_SESSION['nama']) ?>! 🏫</h2>
            <p class="text-sm text-slate-400 mt-1"><?= clean($setting['nama_sekolah'] ?? '') ?> · <?= tgl_indo($today) ?></p>
        </div>
        <a href="<?= BASE_URL ?>home-livepresensi.php" target="_blank" class="hidden sm:flex items-center gap-2 bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-2 rounded-lg text-xs font-medium hover:opacity-90 transition">
            <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>Live Presensi
        </a>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="stat-card glass rounded-xl p-5">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-600 to-cyan-500 flex items-center justify-center mb-3 shadow-lg shadow-blue-500/30"><i class="fas fa-user-graduate text-white"></i></div>
        <p class="text-2xl font-bold" data-count="<?= $jml_siswa ?>">0</p><p class="text-xs text-slate-400 mt-1">Total Siswa</p>
    </div>
    <div class="stat-card glass rounded-xl p-5">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-600 to-teal-500 flex items-center justify-center mb-3 shadow-lg shadow-emerald-500/30"><i class="fas fa-chalkboard-teacher text-white"></i></div>
        <p class="text-2xl font-bold" data-count="<?= $jml_guru ?>">0</p><p class="text-xs text-slate-400 mt-1">Total Guru</p>
    </div>
    <?php if (fitur_aktif('keuangan')): ?>
    <div class="stat-card glass rounded-xl p-5">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-600 to-pink-500 flex items-center justify-center mb-3 shadow-lg shadow-purple-500/30"><i class="fas fa-arrow-down text-white"></i></div>
        <p class="text-lg font-bold text-emerald-400"><?= rupiah($pemasukan) ?></p><p class="text-xs text-slate-400 mt-1">Pemasukan <?= $year ?></p>
    </div>
    <div class="stat-card glass rounded-xl p-5">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-red-600 to-orange-500 flex items-center justify-center mb-3 shadow-lg shadow-red-500/30"><i class="fas fa-arrow-up text-white"></i></div>
        <p class="text-lg font-bold text-red-400"><?= rupiah($pengeluaran) ?></p><p class="text-xs text-slate-400 mt-1">Pengeluaran <?= $year ?></p>
    </div>
    <?php endif; ?>
</div>

<?php if (fitur_aktif('keuangan')): ?>
<!-- Selisih -->
<div class="glass rounded-xl p-4 mb-6 flex items-center justify-between border <?= $selisih>=0?'border-emerald-500/20':'border-red-500/20' ?>">
    <span class="text-sm"><i class="fas fa-balance-scale text-purple-400 mr-2"></i>Selisih Keuangan <?= $year ?></span>
    <span class="text-lg font-bold <?= $selisih>=0?'text-emerald-400':'text-red-400' ?>"><?= rupiah($selisih) ?> <?= $selisih>=0?'✅ Surplus':'⚠️ Defisit' ?></span>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <?php if (fitur_aktif('absensi')): ?>
    <!-- Absensi Hari Ini -->
    <div class="glass rounded-xl p-5">
        <h3 class="text-sm font-semibold mb-4"><i class="fas fa-user-check mr-2 text-blue-400"></i>Rekap Absensi Hari Ini</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-emerald-500/10 rounded-lg p-3 text-center"><p class="text-2xl font-bold text-emerald-400"><?= $hadir ?></p><p class="text-[10px] text-slate-400">Siswa Hadir</p></div>
            <div class="bg-amber-500/10 rounded-lg p-3 text-center"><p class="text-2xl font-bold text-amber-400"><?= $telat ?></p><p class="text-[10px] text-slate-400">Terlambat</p></div>
            <div class="bg-red-500/10 rounded-lg p-3 text-center"><p class="text-2xl font-bold text-red-400"><?= max(0, $jml_siswa - $hadir) ?></p><p class="text-[10px] text-slate-400">Belum Absen</p></div>
            <div class="bg-blue-500/10 rounded-lg p-3 text-center"><p class="text-2xl font-bold text-blue-400"><?= $guru_hadir_count ?></p><p class="text-[10px] text-slate-400">Guru Hadir</p></div>
        </div>
        <!-- Progress -->
        <div class="mt-4">
            <div class="flex justify-between text-xs text-slate-400 mb-1"><span>Kehadiran Siswa</span><span><?= $jml_siswa ? round(($hadir/$jml_siswa)*100) : 0 ?>%</span></div>
            <div class="bg-slate-800 rounded-full h-2.5 overflow-hidden"><div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all" style="width:<?= $jml_siswa ? ($hadir/$jml_siswa)*100 : 0 ?>%"></div></div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Info Sekolah -->
    <div class="glass rounded-xl p-5">
        <h3 class="text-sm font-semibold mb-3"><i class="fas fa-info-circle mr-2 text-purple-400"></i>Info Sekolah</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-slate-400">Kepala Sekolah</span><span class="font-medium"><?= clean($setting['kepsek'] ?? '-') ?></span></div>
            <div class="flex justify-between"><span class="text-slate-400">NPSN</span><span class="font-mono text-xs"><?= clean($setting['npsn'] ?? '-') ?></span></div>
            <div class="flex justify-between"><span class="text-slate-400">Tahun Ajaran</span><span><?php $ta=get_ta_aktif($pdo); echo $ta?$ta['tahun']:'-'; ?></span></div>
            <div class="flex justify-between"><span class="text-slate-400">Total Kelas</span><span><?= $jml_kelas ?></span></div>
            <div class="flex justify-between"><span class="text-slate-400">Mata Pelajaran</span><span><?= $pdo->query("SELECT COUNT(*) FROM tbl_mapel")->fetchColumn() ?></span></div>
        </div>
        <!-- Quick Links -->
        <div class="mt-4 flex flex-wrap gap-2">
            <?php if (fitur_aktif('keuangan')): ?>
            <a href="<?= BASE_URL ?>admin/com_laporan/kondisi_keuangan.php" class="text-xs px-3 py-1.5 rounded-lg bg-purple-600/20 text-purple-400 hover:bg-purple-600/40 transition"><i class="fas fa-chart-pie mr-1"></i>Keuangan</a>
            <?php endif; ?>
            <?php if (fitur_aktif('absensi')): ?>
            <a href="<?= BASE_URL ?>admin/absensi_siswa.php" class="text-xs px-3 py-1.5 rounded-lg bg-blue-600/20 text-blue-400 hover:bg-blue-600/40 transition"><i class="fas fa-clipboard-check mr-1"></i>Absensi</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>admin/siswa.php" class="text-xs px-3 py-1.5 rounded-lg bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600/40 transition"><i class="fas fa-users mr-1"></i>Siswa</a>
        </div>
    </div>
</div>

<?php if (fitur_aktif('keuangan')): ?>
<!-- Chart -->
<div class="glass rounded-xl p-5">
    <h3 class="text-sm font-semibold mb-4"><i class="fas fa-chart-line mr-2 text-emerald-400"></i>Tren Pemasukan <?= $year ?></h3>
    <canvas id="chartKepsek" height="80"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chartKepsek'), {
    type: 'line',
    data: {
        labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
        datasets: [{ label: 'Pemasukan SPP', data: <?= json_encode($chart_data) ?>, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.15)', fill: true, tension: .4, pointRadius: 4, pointBackgroundColor: '#10b981' }]
    },
    options: {
        responsive: true,
        plugins: { legend: { labels: { color: '#94a3b8' } } },
        scales: {
            y: { ticks: { color: '#64748b' }, grid: { color: 'rgba(255,255,255,.05)' } },
            x: { ticks: { color: '#64748b' }, grid: { color: 'rgba(255,255,255,.05)' } }
        }
    }
});
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/template/footer.php'; ?>
