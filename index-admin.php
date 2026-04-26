<?php
// Admin Dashboard
$page_title = 'Dashboard Admin';
require_once __DIR__ . '/config/init.php';
cek_role(['admin']);

$jml_siswa = $pdo->query("SELECT COUNT(*) FROM tbl_siswa WHERE status='Aktif'")->fetchColumn();
$jml_guru = $pdo->query("SELECT COUNT(*) FROM tbl_guru WHERE status='Aktif'")->fetchColumn();
$jml_kelas = $pdo->query("SELECT COUNT(*) FROM tbl_kelas")->fetchColumn();
$jml_mapel = $pdo->query("SELECT COUNT(*) FROM tbl_mapel")->fetchColumn();

$today = date('Y-m-d');
$month = date('m');
$year = date('Y');

$absen_stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_absensi_siswa WHERE tanggal=?");
$absen_stmt->execute([$today]);
$jml_absen = $absen_stmt->fetchColumn();

$belum_absen = $jml_siswa - $jml_absen;

$pemasukan_total = $pdo->prepare("
    SELECT 
        (SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran WHERE MONTH(tanggal_bayar)=? AND YEAR(tanggal_bayar)=?) +
        (SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran_bebas WHERE MONTH(tanggal_bayar)=? AND YEAR(tanggal_bayar)=?)
");
$pemasukan_total->execute([$month, $year, $month, $year]);
$pemasukan = $pemasukan_total->fetchColumn();

// Pemasukan Hari Ini (Harian)
$pemasukan_hari = $pdo->prepare("
    SELECT 
        (SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran WHERE DATE(tanggal_bayar)=?) +
        (SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran_bebas WHERE DATE(tanggal_bayar)=?)
");
$pemasukan_hari->execute([$today, $today]);
$pemasukan_harian = $pemasukan_hari->fetchColumn();

$pengeluaran_stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM tbl_pengeluaran_kas WHERE MONTH(tanggal)=? AND YEAR(tanggal)=?");
$pengeluaran_stmt->execute([$month, $year]);
$pengeluaran = $pengeluaran_stmt->fetchColumn();

// Pemasukan total tahun ini (Tahunan)
$pemasukan_thn = $pdo->prepare("
    SELECT 
        (SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran WHERE YEAR(tanggal_bayar)=?) +
        (SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran_bebas WHERE YEAR(tanggal_bayar)=?)
");
$pemasukan_thn->execute([$year, $year]);
$total_thn = $pemasukan_thn->fetchColumn();

// Chart data - 12 bulan (prepared statements)
$chart_masuk = $chart_keluar = [];
for ($i = 1; $i <= 12; $i++) {
    $cm = $pdo->prepare("
        SELECT 
            (SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran WHERE MONTH(tanggal_bayar)=? AND YEAR(tanggal_bayar)=?) +
            (SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran_bebas WHERE MONTH(tanggal_bayar)=? AND YEAR(tanggal_bayar)=?)
    ");
    $cm->execute([$i, $year, $i, $year]); $chart_masuk[] = (int)$cm->fetchColumn();
    $ck = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM tbl_pengeluaran_kas WHERE MONTH(tanggal)=? AND YEAR(tanggal)=?");
    $ck->execute([$i, $year]); $chart_keluar[] = (int)$ck->fetchColumn();
}

// Agenda
$agenda = $pdo->prepare("SELECT * FROM tbl_agenda WHERE tanggal_mulai >= ? ORDER BY tanggal_mulai LIMIT 5");
$agenda->execute([$today]);
$agenda_list = $agenda->fetchAll();

require_once __DIR__ . '/template/header.php';
require_once __DIR__ . '/template/sidebar.php';
require_once __DIR__ . '/template/topbar.php';
?>

<!-- Welcome -->
<div class="glass rounded-xl p-5 mb-6 border border-blue-500/20">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold">Selamat Datang, <?= clean($_SESSION['nama']) ?>! 👋</h2>
            <p class="text-sm text-slate-400 mt-1"><?= clean($setting['nama_sekolah'] ?? 'Sekolah') ?> · <?= tgl_indo($today) ?></p>
        </div>
        <a href="<?= BASE_URL ?>home-livepresensi.php" target="_blank" class="hidden sm:flex items-center gap-2 bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-2 rounded-lg text-xs font-medium hover:from-blue-500 hover:to-purple-500 transition-all">
            <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></span>Live Presensi
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php
    $stats = [
        ['Siswa Aktif', $jml_siswa, 'fa-user-graduate', 'from-blue-600 to-cyan-500', 'blue'],
        ['Guru Aktif', $jml_guru, 'fa-chalkboard-teacher', 'from-emerald-600 to-teal-500', 'emerald'],
        ['Kelas', $jml_kelas, 'fa-school', 'from-purple-600 to-pink-500', 'purple'],
        ['Absen Hari Ini', $jml_absen, 'fa-user-check', 'from-amber-500 to-orange-500', 'amber'],
    ];
    foreach ($stats as $s): ?>
    <div class="stat-card glass rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br <?= $s[3] ?> flex items-center justify-center shadow-lg shadow-<?= $s[4] ?>-500/30">
                <i class="fas <?= $s[2] ?> text-white"></i>
            </div>
            <i class="fas fa-arrow-up text-emerald-400 text-xs"></i>
        </div>
        <p class="text-2xl font-bold" data-count="<?= $s[1] ?>">0</p>
        <p class="text-xs text-slate-400 mt-1"><?= $s[0] ?></p>
    </div>
    <?php endforeach; ?>
</div>

<?php if (fitur_aktif('keuangan')): ?>
<!-- Finance Overview -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="glass rounded-xl p-5 border border-white/5">
        <p class="text-[10px] text-slate-500 uppercase font-bold tracking-widest mb-1">Pemasukan Hari Ini</p>
        <p class="text-xl font-black text-white italic"><?= rupiah($pemasukan_harian) ?></p>
        <p class="text-[9px] text-emerald-400 mt-1 font-bold italic"><i class="fas fa-calendar-day mr-1"></i>Harian (Bulanan + Bebas)</p>
    </div>
    <div class="glass rounded-xl p-5 border border-white/5">
        <p class="text-[10px] text-slate-500 mb-1 uppercase font-bold tracking-widest">Pemasukan Bulan Ini</p>
        <p class="text-xl font-bold text-emerald-400"><?= rupiah($pemasukan) ?></p>
        <p class="text-[9px] text-slate-500 mt-1 uppercase font-bold tracking-widest">Tahun: <?= rupiah($total_thn) ?></p>
    </div>
    <div class="glass rounded-xl p-5 border border-white/5">
        <p class="text-[10px] text-slate-500 mb-1 uppercase font-bold tracking-widest">Pengeluaran Bulan Ini</p>
        <p class="text-xl font-bold text-red-500"><?= rupiah($pengeluaran) ?></p>
        <p class="text-[9px] text-slate-500 mt-1 italic font-bold">Operasional Kas</p>
    </div>
    <div class="glass rounded-xl p-5 border <?= ($pemasukan-$pengeluaran)>=0?'border-emerald-500/20':'border-red-500/20' ?>">
        <p class="text-[10px] text-slate-500 mb-1 uppercase font-bold tracking-widest">Selisih Bulan Ini</p>
        <p class="text-xl font-bold <?= ($pemasukan-$pengeluaran)>=0?'text-emerald-400':'text-red-400' ?>"><?= rupiah($pemasukan - $pengeluaran) ?></p>
        <p class="text-[9px] mt-1 font-bold uppercase tracking-widest <?= ($pemasukan-$pengeluaran)>=0?'text-emerald-500':'text-red-500' ?>"><?= ($pemasukan-$pengeluaran)>=0?'✅ Surplus':'⚠️ Defisit' ?></p>
    </div>
</div>
<?php endif; ?>

<?php if (fitur_aktif('keuangan')): ?>
<!-- Chart -->
<div class="glass rounded-xl p-5 mb-6">
    <h3 class="text-sm font-semibold mb-4"><i class="fas fa-chart-area mr-2 text-blue-400"></i>Grafik Keuangan <?= $year ?></h3>
    <canvas id="chartKeuangan" height="100"></canvas>
</div>
<?php endif; ?>

<!-- Recent Info -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <?php if (fitur_aktif('absensi')): ?>
    <!-- Absensi Terbaru -->
    <div class="glass rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold"><i class="fas fa-clock mr-2 text-amber-400"></i>Absensi Terbaru</h3>
            <a href="<?= BASE_URL ?>home-livepresensi.php" target="_blank" class="text-xs text-blue-400 hover:underline">Live →</a>
        </div>
        <?php $recent = $pdo->prepare("SELECT a.*, s.nama FROM tbl_absensi_siswa a JOIN tbl_siswa s ON a.id_siswa=s.id_siswa WHERE a.tanggal=? ORDER BY a.id DESC LIMIT 6");
        $recent->execute([$today]);
        $recent_data = $recent->fetchAll();
        if ($recent_data): foreach ($recent_data as $r): ?>
        <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold"><?= strtoupper(substr($r['nama'],0,1)) ?></div>
                <span class="text-sm truncate max-w-[120px]"><?= clean($r['nama']) ?></span>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full <?= $r['keterangan']=='Tepat Waktu'?'bg-emerald-500/20 text-emerald-400':($r['keterangan']=='Terlambat'?'bg-amber-500/20 text-amber-400':'bg-blue-500/20 text-blue-400') ?>"><?= $r['keterangan'] ?? $r['status'] ?></span>
        </div>
        <?php endforeach; else: ?><p class="text-sm text-slate-500 py-3 text-center">Belum ada absensi hari ini.</p><?php endif; ?>
        <div class="mt-3 flex gap-2 text-xs">
            <span class="px-2 py-1 rounded bg-emerald-500/10 text-emerald-400"><i class="fas fa-check mr-1"></i><?= $jml_absen ?> hadir</span>
            <span class="px-2 py-1 rounded bg-red-500/10 text-red-400"><i class="fas fa-times mr-1"></i><?= max(0,$belum_absen) ?> belum</span>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Info Sekolah -->
    <div class="glass rounded-xl p-5">
        <h3 class="text-sm font-semibold mb-3"><i class="fas fa-info-circle mr-2 text-blue-400"></i>Info Sekolah</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-slate-400">Sekolah</span><span class="font-medium"><?= clean($setting['nama_sekolah'] ?? '-') ?></span></div>
            <div class="flex justify-between"><span class="text-slate-400">NPSN</span><span class="font-mono text-xs"><?= clean($setting['npsn'] ?? '-') ?></span></div>
            <div class="flex justify-between"><span class="text-slate-400">Kepala Sekolah</span><span><?= clean($setting['kepsek'] ?? '-') ?></span></div>
            <div class="flex justify-between"><span class="text-slate-400">Tahun Ajaran</span><span><?php $ta=get_ta_aktif($pdo); echo $ta?$ta['tahun']:'-'; ?></span></div>
            <div class="flex justify-between"><span class="text-slate-400">Mata Pelajaran</span><span><?= $jml_mapel ?></span></div>
        </div>
    </div>

    <!-- Agenda -->
    <div class="glass rounded-xl p-5">
        <h3 class="text-sm font-semibold mb-3"><i class="fas fa-calendar-alt mr-2 text-purple-400"></i>Agenda Mendatang</h3>
        <?php if ($agenda_list): foreach ($agenda_list as $ag): ?>
        <div class="flex items-start gap-3 py-2 border-b border-white/5 last:border-0">
            <div class="w-10 text-center flex-shrink-0">
                <p class="text-lg font-bold" style="color:<?= $ag['warna'] ?>"><?= date('d', strtotime($ag['tanggal_mulai'])) ?></p>
                <p class="text-[10px] text-slate-500"><?= date('M', strtotime($ag['tanggal_mulai'])) ?></p>
            </div>
            <div>
                <p class="text-sm font-medium"><?= clean($ag['judul']) ?></p>
                <p class="text-xs text-slate-500"><?= clean(substr($ag['deskripsi'] ?? '',0,60)) ?></p>
            </div>
        </div>
        <?php endforeach; else: ?><p class="text-sm text-slate-500 py-3 text-center">Tidak ada agenda.</p><?php endif; ?>
    </div>
</div>

<?php if (fitur_aktif('keuangan')): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chartKeuangan'), {
    type: 'line',
    data: {
        labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
        datasets: [
            { label: 'Pemasukan', data: <?= json_encode($chart_masuk) ?>, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.1)', fill: true, tension: .4, pointRadius: 3, pointBackgroundColor: '#10b981' },
            { label: 'Pengeluaran', data: <?= json_encode($chart_keluar) ?>, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.1)', fill: true, tension: .4, pointRadius: 3, pointBackgroundColor: '#ef4444' }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { labels: { color: '#94a3b8', usePointStyle: true } } },
        scales: {
            y: { ticks: { color: '#64748b', callback: v => 'Rp ' + (v/1000000).toFixed(1) + 'jt' }, grid: { color: 'rgba(255,255,255,.05)' } },
            x: { ticks: { color: '#64748b' }, grid: { color: 'rgba(255,255,255,.05)' } }
        }
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/template/footer.php'; ?>
