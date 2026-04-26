<?php
$page_title = 'Laporan Saldo Akhir Tabungan';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara']);
cek_fitur('tabungan');

// Ambil list kelas untuk filter
$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();
$id_kelas = $_GET['id_kelas'] ?? '';

$sql_where = "";
$params = [];

if ($id_kelas) {
    $sql_where = "WHERE s.id_kelas = ?";
    $params[] = $id_kelas;
}

$sql = "SELECT n.*, s.nama, s.nis, s.id_siswa, k.nama_kelas 
        FROM tbl_nasabah n 
        JOIN tbl_siswa s ON n.id_siswa = s.id_siswa 
        LEFT JOIN tbl_kelas k ON s.id_kelas = k.id_kelas 
        $sql_where 
        ORDER BY k.nama_kelas ASC, s.nama ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// Kalkulasi Total Global / Kelas (Berdasarkan Filter)
$total_saldo_mengendap = 0;
foreach ($data as $r) {
    $total_saldo_mengendap += $r['saldo'];
}

require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>

<div class="glass rounded-xl p-5 mb-6 print:hidden">
    <form method="GET" class="flex gap-4 items-end flex-wrap">
        <div>
            <label class="block text-xs text-slate-400 mb-1">Filter Berdasarkan Kelas</label>
            <select name="id_kelas" class="bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm w-48">
                <option value="">-- Tampilkan Seluruh Sekolah --</option>
                <?php foreach($kelas_list as $k): ?>
                <option value="<?= $k['id_kelas'] ?>" <?= $id_kelas == $k['id_kelas'] ? 'selected' : '' ?>><?= $k['nama_kelas'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-semibold shadow transition-colors"><i class="fas fa-search mr-2"></i>Tampilkan Saldo</button>
            <a href="laporan_saldo.php" class="bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-lg text-sm shadow transition-colors ml-2"><i class="fas fa-sync"></i></a>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 mb-6 print:hidden">
    <div class="glass p-5 rounded-xl border-t-4 border-emerald-500 flex justify-between items-center bg-gradient-to-r from-emerald-900/40 to-transparent">
        <div>
            <h4 class="text-xs text-emerald-200 uppercase tracking-widest mb-1">Total Saldo Aktif <?= $id_kelas ? 'di Kelas Ini' : 'Seluruh Sekolah' ?></h4>
            <p class="text-3xl font-black text-emerald-400"><?= rupiah($total_saldo_mengendap) ?></p>
        </div>
        <div class="opacity-10 hidden md:block">
            <i class="fas fa-piggy-bank text-6xl"></i>
        </div>
    </div>
</div>

<div class="glass rounded-xl p-5" id="cetak-area">
    <div class="flex justify-between items-center mb-4 print:hidden">
        <h3 class="font-bold border-b border-white/10 pb-2 flex-1"><i class="fas fa-list-ul mr-2 text-slate-400"></i>Rincian Rekening Nasabah</h3>
        <button onclick="window.print()" class="bg-purple-600 hover:bg-purple-500 px-3 py-1.5 rounded text-xs font-semibold shadow ml-4"><i class="fas fa-print mr-1"></i>Cetak (Ctrl+P)</button>
    </div>
    
    <!-- Print Header -->
    <div class="hidden print:block text-center mb-5 text-black">
        <h2 class="text-xl font-bold uppercase">Laporan Saldo Akhir Tabungan Siswa</h2>
        <p class="text-sm"><?= $id_kelas ? "Difilter Berdasarkan Kelas Tertentu" : "Keseluruhan Siswa" ?></p>
        <p class="text-sm">Dicetak pada: <?= date('d/m/Y H:i') ?></p>
        <hr class="border-black my-3">
    </div>

    <div class="table-container"><table class="w-full text-sm print:text-black">
    <thead>
        <tr class="text-left text-slate-400 border-b border-white/10 print:border-black print:text-black">
            <th class="pb-3 w-10">#</th>
            <th class="pb-3 w-40">No Rekening</th>
            <th class="pb-3">Nama Siswa / NIS</th>
            <th class="pb-3">Kelas</th>
            <th class="pb-3 text-right pr-4">Saldo Mengendap</th>
            <th class="pb-3 text-center print:hidden w-32">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if(count($data)==0): ?><tr><td colspan="6" class="text-center py-5 text-slate-500">Tidak ada saldo / nasabah ditemukan pada filter ini.</td></tr><?php endif; ?>
        <?php foreach ($data as $i => $r): ?>
        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors print:border-slate-300">
            <td class="py-2"><?= $i+1 ?></td>
            <td class="font-mono text-xs text-slate-300 print:text-black"><?= $r['no_rekening'] ?></td>
            <td class="font-bold text-white print:text-black">
                <?= clean($r['nama']) ?> 
                <span class="block text-xs font-normal text-slate-400 print:hidden"><?= $r['nis'] ?></span>
            </td>
            <td class="text-slate-300 print:text-black"><?= clean($r['nama_kelas']) ?></td>
            <td class="font-black text-emerald-400 text-right pr-4 text-base print:text-black"><?= rupiah($r['saldo']) ?></td>
            <td class="text-center print:hidden">
                <a href="transaksi.php?id=<?= $r['id_nasabah'] ?>" class="text-xs bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white px-2 py-1 rounded transition-colors"><i class="fas fa-exchange-alt mr-1"></i>Mutasi</a>
            </td>
        </tr>
        <?php endforeach; ?>
        
        <tr class="border-t-2 border-white/20 print:border-black font-bold text-base bg-slate-900/30 print:bg-transparent">
            <td colspan="4" class="text-right py-3 pr-4 uppercase text-slate-300 print:text-black font-black">TOTAL SALDO AKHIR :</td>
            <td class="text-right pr-4 text-emerald-400 print:text-black text-lg py-3"><span class="print:hidden">Rp </span><?= number_format($total_saldo_mengendap,0,',','.') ?></td>
            <td class="print:hidden"></td>
        </tr>
    </tbody>
    </table></div>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    #cetak-area, #cetak-area * { visibility: visible; }
    #cetak-area { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; background: transparent !important; padding: 0 !important; }
    
    body { background: white; color: black; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
    .text-right { text-align: right; }
    .font-black, .text-emerald-400, .text-blue-400, .text-slate-300, .text-slate-400, .text-white { color: black !important; }
}
</style>

<?php require_once __DIR__ . '/../../template/footer.php'; ?>
