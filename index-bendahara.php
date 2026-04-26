<?php
$page_title = 'Dashboard Bendahara';
require_once __DIR__ . '/config/init.php';
cek_role(['bendahara']);

$today = date('Y-m-d');
$month = date('m');
$year = date('Y');

$s1 = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran WHERE DATE(tanggal_bayar)=?");
$s1->execute([$today]); $pemasukan_hari = $s1->fetchColumn();

$s2 = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran WHERE MONTH(tanggal_bayar)=? AND YEAR(tanggal_bayar)=?");
$s2->execute([$month, $year]); $pemasukan_bulan = $s2->fetchColumn();

$s3 = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM tbl_pengeluaran_kas WHERE MONTH(tanggal)=? AND YEAR(tanggal)=?");
$s3->execute([$month, $year]); $pengeluaran_bulan = $s3->fetchColumn();

$total_tabungan = $pdo->query("SELECT COALESCE(SUM(saldo),0) FROM tbl_nasabah")->fetchColumn();
$selisih = $pemasukan_bulan - $pengeluaran_bulan;

// Chart data
$chart_masuk = $chart_keluar = [];
for ($i=1;$i<=12;$i++) {
    $cm = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran WHERE MONTH(tanggal_bayar)=? AND YEAR(tanggal_bayar)=?");
    $cm->execute([$i,$year]); $chart_masuk[] = (int)$cm->fetchColumn();
    $ck = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM tbl_pengeluaran_kas WHERE MONTH(tanggal)=? AND YEAR(tanggal)=?");
    $ck->execute([$i,$year]); $chart_keluar[] = (int)$ck->fetchColumn();
}

require_once __DIR__ . '/template/header.php';
require_once __DIR__ . '/template/sidebar.php';
require_once __DIR__ . '/template/topbar.php';
?>

<!-- Welcome -->
<div class="glass rounded-xl p-5 mb-6 border border-emerald-500/20">
    <h2 class="text-lg font-bold">Selamat Datang, <?= clean($_SESSION['nama']) ?>! 💰</h2>
    <p class="text-sm text-slate-400 mt-1"><?= tgl_indo($today) ?> · <?= clean($setting['nama_sekolah'] ?? '') ?></p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="stat-card glass rounded-xl p-5">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-600 to-teal-500 flex items-center justify-center mb-3 shadow-lg shadow-emerald-500/30"><i class="fas fa-coins text-white"></i></div>
        <p class="text-xl font-bold text-emerald-400"><?= rupiah($pemasukan_hari) ?></p>
        <p class="text-xs text-slate-400 mt-1">Pemasukan Hari Ini</p>
    </div>
    <div class="stat-card glass rounded-xl p-5">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-600 to-cyan-500 flex items-center justify-center mb-3 shadow-lg shadow-blue-500/30"><i class="fas fa-chart-line text-white"></i></div>
        <p class="text-xl font-bold text-blue-400"><?= rupiah($pemasukan_bulan) ?></p>
        <p class="text-xs text-slate-400 mt-1">Pemasukan Bulan Ini</p>
    </div>
    <div class="stat-card glass rounded-xl p-5">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-red-600 to-pink-500 flex items-center justify-center mb-3 shadow-lg shadow-red-500/30"><i class="fas fa-arrow-up text-white"></i></div>
        <p class="text-xl font-bold text-red-400"><?= rupiah($pengeluaran_bulan) ?></p>
        <p class="text-xs text-slate-400 mt-1">Pengeluaran Bulan Ini</p>
    </div>
    <?php if (fitur_aktif('tabungan')): ?>
    <div class="stat-card glass rounded-xl p-5 border <?= $selisih>=0?'border-emerald-500/20':'border-red-500/20' ?>">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center mb-3 shadow-lg shadow-amber-500/30"><i class="fas fa-piggy-bank text-white"></i></div>
        <p class="text-xl font-bold text-amber-400"><?= rupiah($total_tabungan) ?></p>
        <p class="text-xs text-slate-400 mt-1">Total Tabungan</p>
    </div>
    <?php endif; ?>
</div>

<!-- Selisih -->
<div class="glass rounded-xl p-4 mb-6 flex items-center justify-between">
    <span class="text-sm"><i class="fas fa-balance-scale text-purple-400 mr-2"></i>Selisih Bulan Ini (Pemasukan - Pengeluaran)</span>
    <span class="text-lg font-bold <?= $selisih>=0?'text-emerald-400':'text-red-400' ?>"><?= rupiah($selisih) ?> <?= $selisih>=0?'✅':'⚠️' ?></span>
</div>

<!-- Chart -->
<div class="glass rounded-xl p-5 mb-6">
    <h3 class="text-sm font-semibold mb-4"><i class="fas fa-chart-area mr-2 text-blue-400"></i>Grafik Keuangan <?= $year ?></h3>
    <canvas id="chartBendahara" height="90"></canvas>
</div>

<!-- Recent Payments -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="glass rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold"><i class="fas fa-history mr-2 text-blue-400"></i>Pembayaran Terakhir</h3>
            <a href="<?= BASE_URL ?>admin/pembayaran.php" class="text-xs text-blue-400 hover:underline">Proses →</a>
        </div>
        <div class="table-container"><table class="w-full text-sm">
            <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-2">Siswa</th><th class="pb-2">Jenis</th><th class="pb-2">Jumlah</th><th class="pb-2">Tanggal</th></tr></thead>
            <tbody>
            <?php $q = $pdo->query("SELECT p.*, s.nama, j.nama_jenis FROM tbl_pembayaran p JOIN tbl_siswa s ON p.id_siswa=s.id_siswa LEFT JOIN tbl_jenis_bayar j ON p.id_jenis=j.id_jenis ORDER BY p.id_pembayaran DESC LIMIT 8");
            while ($r = $q->fetch()): ?>
            <tr class="border-b border-white/5 hover:bg-white/5">
                <td class="py-2 font-medium"><?= clean($r['nama']) ?></td>
                <td class="text-xs"><?= clean($r['nama_jenis'] ?? '-') ?></td>
                <td class="text-emerald-400 font-medium"><?= rupiah($r['jumlah_bayar']) ?></td>
                <td class="text-xs text-slate-400"><?= date('d/m', strtotime($r['tanggal_bayar'])) ?></td>
            </tr>
            <?php endwhile; ?></tbody>
        </table></div>
    </div>

    <!-- Quick Links -->
    <div class="glass rounded-xl p-5">
        <h3 class="text-sm font-semibold mb-3"><i class="fas fa-bolt mr-2 text-amber-400"></i>Akses Cepat</h3>
        <div class="grid grid-cols-2 gap-3">
            <?php $links = [
                ['Pembayaran','admin/pembayaran.php','fa-cash-register','from-emerald-500 to-teal-600'],
                ['Penerimaan Kas','admin/com_kas/penerimaan.php','fa-arrow-down','from-blue-500 to-indigo-600'],
                ['Pengeluaran Kas','admin/com_kas/pengeluaran.php','fa-arrow-up','from-red-500 to-pink-600'],
                ['Kondisi Keuangan','admin/com_laporan/kondisi_keuangan.php','fa-balance-scale','from-purple-500 to-pink-600'],
                ['Rekap Bayar','admin/com_laporan/rekap_pembayaran.php','fa-chart-bar','from-cyan-500 to-blue-600'],
            ]; 
            if (fitur_aktif('tabungan')) {
                $links[] = ['Tabungan','admin/com_transaksi/nasabah.php','fa-piggy-bank','from-amber-500 to-orange-600'];
            }
            foreach ($links as $l): ?>
            <a href="<?= BASE_URL.$l[1] ?>" class="flex items-center gap-3 p-3 bg-white/5 rounded-lg hover:bg-white/10 transition-all group">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br <?= $l[3] ?> flex items-center justify-center group-hover:scale-110 transition-transform"><i class="fas <?= $l[2] ?> text-white text-xs"></i></div>
                <span class="text-xs font-medium"><?= $l[0] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chartBendahara'), {
    type: 'bar',
    data: {
        labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
        datasets: [
            { label: 'Pemasukan', data: <?= json_encode($chart_masuk) ?>, backgroundColor: 'rgba(16,185,129,.7)', borderRadius: 4 },
            { label: 'Pengeluaran', data: <?= json_encode($chart_keluar) ?>, backgroundColor: 'rgba(239,68,68,.7)', borderRadius: 4 }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { labels: { color: '#94a3b8', usePointStyle: true } } },
        scales: {
            y: { ticks: { color: '#64748b' }, grid: { color: 'rgba(255,255,255,.05)' } },
            x: { ticks: { color: '#64748b' }, grid: { color: 'rgba(255,255,255,.05)' } }
        }
    }
});
</script>
<?php require_once __DIR__ . '/template/footer.php'; ?>
