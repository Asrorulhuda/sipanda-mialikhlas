<?php
$page_title = 'Kondisi Keuangan';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara','kepsek']);
cek_fitur('laporan');

// Get all tahun ajaran for filter
$ta_list = $pdo->query("SELECT * FROM tbl_tahun_ajaran ORDER BY id_ta DESC")->fetchAll();
$ta_aktif = get_ta_aktif($pdo);

// Determine filter mode: by TA or by calendar year
$sel_ta = isset($_GET['id_ta']) ? (int)$_GET['id_ta'] : ($ta_aktif ? $ta_aktif['id_ta'] : 0);

// Get selected TA data
$ta_data = null;
if ($sel_ta) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_tahun_ajaran WHERE id_ta=?");
    $stmt->execute([$sel_ta]);
    $ta_data = $stmt->fetch();
}

// Calculate date range from TA
if ($ta_data && $ta_data['tgl_mulai'] && $ta_data['tgl_selesai']) {
    $date_start = $ta_data['tgl_mulai'];
    $date_end = $ta_data['tgl_selesai'];
    $ta_start_y = (int)date('Y', strtotime($date_start));
    $ta_end_y = (int)date('Y', strtotime($date_end));
    $label = $ta_data['tahun'];
} else {
    $thn = (int)date('Y');
    $date_start = "$thn-07-01";
    $date_end = ($thn+1)."-06-30";
    $ta_start_y = $thn;
    $ta_end_y = $thn + 1;
    $label = "$thn/".($thn+1);
}

// Academic months (Juli - Juni)
$academic_months = [
    ['m' => 7, 'y' => $ta_start_y, 'name' => 'Juli'],
    ['m' => 8, 'y' => $ta_start_y, 'name' => 'Agustus'],
    ['m' => 9, 'y' => $ta_start_y, 'name' => 'September'],
    ['m' => 10, 'y' => $ta_start_y, 'name' => 'Oktober'],
    ['m' => 11, 'y' => $ta_start_y, 'name' => 'November'],
    ['m' => 12, 'y' => $ta_start_y, 'name' => 'Desember'],
    ['m' => 1, 'y' => $ta_end_y, 'name' => 'Januari'],
    ['m' => 2, 'y' => $ta_end_y, 'name' => 'Februari'],
    ['m' => 3, 'y' => $ta_end_y, 'name' => 'Maret'],
    ['m' => 4, 'y' => $ta_end_y, 'name' => 'April'],
    ['m' => 5, 'y' => $ta_end_y, 'name' => 'Mei'],
    ['m' => 6, 'y' => $ta_end_y, 'name' => 'Juni'],
];

// Totals for the TA period
$stmt_spp = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran WHERE tanggal_bayar BETWEEN ? AND ?");
$stmt_spp->execute([$date_start, $date_end]); $masuk_spp = $stmt_spp->fetchColumn();

$stmt_kas = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM tbl_penerimaan_kas WHERE tanggal BETWEEN ? AND ?");
$stmt_kas->execute([$date_start, $date_end]); $masuk_kas = $stmt_kas->fetchColumn();

$stmt_kel = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM tbl_pengeluaran_kas WHERE tanggal BETWEEN ? AND ?");
$stmt_kel->execute([$date_start, $date_end]); $keluar_kas = $stmt_kel->fetchColumn();

$total_masuk = $masuk_spp + $masuk_kas;
$selisih = $total_masuk - $keluar_kas;

// Monthly data
$monthly = [];
foreach ($academic_months as $am) {
    $m = $am['m'];
    $y = $am['y'];
    $s1 = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran WHERE MONTH(tanggal_bayar)=? AND YEAR(tanggal_bayar)=?");
    $s1->execute([$m, $y]); $pm1 = $s1->fetchColumn();
    $s2 = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM tbl_penerimaan_kas WHERE MONTH(tanggal)=? AND YEAR(tanggal)=?");
    $s2->execute([$m, $y]); $pm2 = $s2->fetchColumn();
    $s3 = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) FROM tbl_pengeluaran_kas WHERE MONTH(tanggal)=? AND YEAR(tanggal)=?");
    $s3->execute([$m, $y]); $pk = $s3->fetchColumn();
    $monthly[] = ['bulan' => $am['name'] . ' ' . $y, 'masuk' => $pm1+$pm2, 'keluar' => $pk, 'selisih' => ($pm1+$pm2)-$pk];
}

require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>

<!-- Filter by Tahun Ajaran -->
<div class="mb-6 flex flex-col sm:flex-row gap-3 justify-between items-end">
    <form method="GET" class="flex gap-2 items-end">
        <div>
            <label class="block text-xs text-slate-400 mb-1">Tahun Ajaran</label>
            <select name="id_ta" onchange="this.form.submit()" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none min-w-[180px]">
                <?php foreach ($ta_list as $t): ?>
                <option value="<?= $t['id_ta'] ?>" <?= $sel_ta==$t['id_ta']?'selected':'' ?>><?= clean($t['tahun']) ?> <?= $t['status']=='aktif'?'(Aktif)':'' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
    <div class="flex gap-2">
        <div class="glass rounded-lg px-3 py-2 text-xs text-blue-400 border border-blue-500/20">
            <i class="fas fa-calendar-alt mr-1"></i>Jul <?= $ta_start_y ?> — Jun <?= $ta_end_y ?>
        </div>
        <button onclick="window.print()" class="bg-purple-600/80 hover:bg-purple-600 px-3 py-2 rounded-lg text-xs"><i class="fas fa-print mr-1"></i>Cetak</button>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="glass rounded-xl p-5"><p class="text-xs text-slate-400 mb-1"><i class="fas fa-coins text-emerald-400 mr-1"></i>Pemasukan SPP</p><p class="text-xl font-bold text-emerald-400"><?= rupiah($masuk_spp) ?></p></div>
    <div class="glass rounded-xl p-5"><p class="text-xs text-slate-400 mb-1"><i class="fas fa-arrow-down text-blue-400 mr-1"></i>Penerimaan Kas</p><p class="text-xl font-bold text-blue-400"><?= rupiah($masuk_kas) ?></p></div>
    <div class="glass rounded-xl p-5"><p class="text-xs text-slate-400 mb-1"><i class="fas fa-arrow-up text-red-400 mr-1"></i>Pengeluaran Kas</p><p class="text-xl font-bold text-red-400"><?= rupiah($keluar_kas) ?></p></div>
    <div class="glass rounded-xl p-5 <?= $selisih>=0?'border-emerald-500/30':'border-red-500/30' ?> border">
        <p class="text-xs text-slate-400 mb-1"><i class="fas fa-balance-scale text-purple-400 mr-1"></i>Selisih</p>
        <p class="text-xl font-bold <?= $selisih>=0?'text-emerald-400':'text-red-400' ?>"><?= rupiah($selisih) ?></p>
        <p class="text-xs mt-1 <?= $selisih>=0?'text-emerald-500':'text-red-500' ?>"><?= $selisih>=0?'✅ Surplus':'⚠️ Defisit' ?></p>
    </div>
</div>

<!-- Chart -->
<div class="glass rounded-xl p-5 mb-6">
    <h4 class="font-semibold text-sm mb-4"><i class="fas fa-chart-area text-blue-400 mr-2"></i>Grafik Keuangan TA <?= clean($label) ?></h4>
    <canvas id="chartKondisi" height="80"></canvas>
</div>

<div class="glass rounded-xl p-5">
    <h4 class="font-semibold text-sm mb-4">Rincian Per Bulan (Juli — Juni)</h4>
    <div class="table-container"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">Bulan</th><th class="pb-3">Pemasukan</th><th class="pb-3">Pengeluaran</th><th class="pb-3">Selisih</th></tr></thead>
        <tbody><?php foreach ($monthly as $d): ?>
        <tr class="border-b border-white/5"><td class="py-2 font-medium"><?= $d['bulan'] ?></td><td class="text-emerald-400"><?= rupiah($d['masuk']) ?></td><td class="text-red-400"><?= rupiah($d['keluar']) ?></td><td class="font-medium <?= $d['selisih']>=0?'text-emerald-400':'text-red-400' ?>"><?= rupiah($d['selisih']) ?></td></tr>
        <?php endforeach; ?></tbody>
        <tfoot><tr class="font-bold border-t border-white/10"><td class="py-2">Total</td><td class="text-emerald-400"><?= rupiah($total_masuk) ?></td><td class="text-red-400"><?= rupiah($keluar_kas) ?></td><td class="<?= $selisih>=0?'text-emerald-400':'text-red-400' ?>"><?= rupiah($selisih) ?></td></tr></tfoot>
    </table></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chartKondisi'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($monthly,'bulan')) ?>,
        datasets: [
            { label: 'Pemasukan', data: <?= json_encode(array_column($monthly,'masuk')) ?>, backgroundColor: 'rgba(16,185,129,.6)', borderRadius: 4 },
            { label: 'Pengeluaran', data: <?= json_encode(array_column($monthly,'keluar')) ?>, backgroundColor: 'rgba(239,68,68,.6)', borderRadius: 4 }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { labels: { color: '#94a3b8' } } },
        scales: {
            y: { ticks: { color: '#64748b' }, grid: { color: 'rgba(255,255,255,.05)' } },
            x: { ticks: { color: '#64748b', maxRotation: 45 }, grid: { color: 'rgba(255,255,255,.05)' } }
        }
    }
});
</script>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
