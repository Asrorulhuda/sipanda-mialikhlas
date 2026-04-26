<?php
$page_title = 'Laporan Transaksi Tabungan';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara']);
cek_fitur('tabungan');

// Ambil list kelas untuk filter
$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();

$id_kelas = $_GET['id_kelas'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';
$bulan = $_GET['bulan'] ?? '';
$tahun = $_GET['tahun'] ?? date('Y');

$where = [];
$params = [];

if ($id_kelas) {
    $where[] = "s.id_kelas = ?";
    $params[] = $id_kelas;
}
if ($tanggal) {
    // Filter Per Hari
    $where[] = "DATE(t.tanggal) = ?";
    $params[] = $tanggal;
} else if ($bulan && $tahun) {
    // Filter Per Bulan
    $where[] = "MONTH(t.tanggal) = ? AND YEAR(t.tanggal) = ?";
    $params[] = $bulan;
    $params[] = $tahun;
} else if ($tahun) {
    $where[] = "YEAR(t.tanggal) = ?";
    $params[] = $tahun;
}

$sql_where = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
$sql = "SELECT t.*, n.no_rekening, s.nama, k.nama_kelas 
        FROM tbl_transaksi_tabungan t 
        JOIN tbl_nasabah n ON t.id_nasabah = n.id_nasabah 
        JOIN tbl_siswa s ON n.id_siswa = s.id_siswa 
        LEFT JOIN tbl_kelas k ON s.id_kelas = k.id_kelas 
        $sql_where 
        ORDER BY t.tanggal DESC, t.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// Kalkulasi Total
$tot_setor = 0;
$tot_tarik = 0;
$tot_admin = 0;
foreach ($data as $r) {
    if ($r['jenis'] == 'Debit') {
        $tot_setor += $r['jumlah'];
    } else {
        if (stripos($r['keterangan'], 'Biaya Admin') !== false) {
            $tot_admin += $r['jumlah'];
        } else {
            $tot_tarik += $r['jumlah'];
        }
    }
}

require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>

<div class="glass rounded-xl p-5 mb-6">
    <form method="GET" class="flex gap-4 items-end flex-wrap">
        <div>
            <label class="block text-xs text-slate-400 mb-1">Kelas</label>
            <select name="id_kelas" class="bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm w-40">
                <option value="">-- Semua Kelas --</option>
                <?php foreach($kelas_list as $k): ?>
                <option value="<?= $k['id_kelas'] ?>" <?= $id_kelas == $k['id_kelas'] ? 'selected' : '' ?>><?= $k['nama_kelas'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="border-l border-white/10 pl-4">
            <label class="block text-xs text-emerald-400 mb-1 font-bold">Per Hari</label>
            <input type="date" name="tanggal" value="<?= $tanggal ?>" class="bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm">
        </div>
        
        <div class="border-l border-white/10 pl-4 flex gap-2">
            <div>
                <label class="block text-xs text-blue-400 mb-1 font-bold">Atau Per Bulan</label>
                <select name="bulan" class="bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm">
                    <option value="">-- Bulan --</option>
                    <?php for($i=1; $i<=12; $i++): $bln = str_pad($i,2,'0',STR_PAD_LEFT); ?>
                    <option value="<?= $bln ?>" <?= $bulan == $bln ? 'selected' : '' ?>><?= $bln ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">&nbsp;</label>
                <input type="number" name="tahun" value="<?= $tahun ?>" placeholder="Tahun" class="bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm w-24">
            </div>
        </div>
        
        <div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-semibold shadow transition-colors"><i class="fas fa-search mr-2"></i>Tampilkan</button>
            <a href="laporan.php" class="bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-lg text-sm shadow transition-colors ml-2"><i class="fas fa-sync"></i></a>
        </div>
    </form>
    <p class="text-xs text-slate-500 mt-3">* Jika filter "Per Hari" diisi, pencarian "Per Bulan" akan diabaikan.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="glass p-4 rounded-xl border-l-4 border-emerald-500">
        <h4 class="text-xs text-slate-400 uppercase tracking-wide">Total Setoran Keluar/Terkumpul</h4>
        <p class="text-2xl font-bold text-emerald-400 mt-1"><?= rupiah($tot_setor) ?></p>
    </div>
    <div class="glass p-4 rounded-xl border-l-4 border-red-500">
        <h4 class="text-xs text-slate-400 uppercase tracking-wide">Total Uang Ditarik Siswa</h4>
        <p class="text-2xl font-bold text-red-500 mt-1"><?= rupiah($tot_tarik) ?></p>
    </div>
    <div class="glass p-4 rounded-xl border-l-4 border-yellow-500">
        <h4 class="text-xs text-slate-400 uppercase tracking-wide">Total Biaya Admin Kas</h4>
        <p class="text-2xl font-bold text-yellow-400 mt-1"><?= rupiah($tot_admin) ?></p>
    </div>
</div>

<div class="glass rounded-xl p-5" id="cetak-area">
    <div class="flex justify-between items-center mb-4 print:hidden">
        <h3 class="font-bold border-b border-white/10 pb-2 flex-1"><i class="fas fa-list-ul mr-2 text-slate-400"></i>Rincian Transaksi</h3>
        <button onclick="window.print()" class="bg-purple-600 hover:bg-purple-500 px-3 py-1.5 rounded text-xs font-semibold shadow ml-4"><i class="fas fa-print mr-1"></i>Cetak Laporan</button>
    </div>
    
    <!-- Print Header -->
    <div class="hidden print:block text-center mb-5 text-black">
        <h2 class="text-xl font-bold uppercase">Laporan Mutasi Transaksi Tabungan</h2>
        <p class="text-sm">Filter: <?= $tanggal ? tgl_indo($tanggal) : ($bulan && $tahun ? "Bulan $bulan Tahun $tahun" : "Seluruh Waktu") ?></p>
        <p class="text-sm">Dicetak pada: <?= date('d/m/Y H:i') ?></p>
        <hr class="border-black my-3">
    </div>

    <div class="table-container"><table class="w-full text-sm print:text-black">
    <thead>
        <tr class="text-left text-slate-400 border-b border-white/10 print:border-black print:text-black">
            <th class="pb-3 w-10">#</th>
            <th class="pb-3 w-40">Waktu</th>
            <th class="pb-3">Nasabah</th>
            <th class="pb-3">Kelas</th>
            <th class="pb-3">Jenis</th>
            <th class="pb-3 text-right">Jumlah</th>
            <th class="pb-3 text-right">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        <?php if(count($data)==0): ?><tr><td colspan="7" class="text-center py-5 text-slate-500 print:text-black">Tidak ada transaksi ditemukan pada filter ini.</td></tr><?php endif; ?>
        <?php foreach ($data as $i => $r): 
            $is_admin = (stripos($r['keterangan'],'Biaya Admin') !== false);
            $jenis_lbl = $r['jenis'] == 'Debit' ? 'SETOR' : ($is_admin ? 'ADMIN' : 'TARIK');
            $jenis_clr = $r['jenis'] == 'Debit' ? 'bg-emerald-500/20 text-emerald-400' : ($is_admin ? 'bg-yellow-500/20 text-yellow-500' : 'bg-red-500/20 text-red-400');
            $jml_clr = $r['jenis'] == 'Debit' ? 'text-emerald-400' : ($is_admin ? 'text-yellow-500' : 'text-red-400');
        ?>
        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors print:border-slate-300">
            <td class="py-2"><?= $i+1 ?></td>
            <td class="whitespace-nowrap font-mono text-xs text-slate-300 print:text-black"><?= date('d/m/Y H:i', strtotime($r['tanggal'])) ?></td>
            <td class="font-bold text-white print:text-black"><?= clean($r['nama']) ?> <span class="block text-xs font-normal text-slate-400 print:hidden"><?= $r['no_rekening'] ?></span></td>
            <td class="text-slate-300 print:text-black"><?= clean($r['nama_kelas']) ?></td>
            <td><span class="px-2 py-0.5 rounded-full text-xs font-bold <?= $jenis_clr ?> print:bg-transparent print:border print:border-black print:text-black"><?= $jenis_lbl ?></span></td>
            <td class="font-black text-right <?= $jml_clr ?> print:text-black"><?= rupiah($r['jumlah']) ?></td>
            <td class="text-slate-400 text-xs text-right print:text-black"><?= clean($r['keterangan']) ?></td>
        </tr>
        <?php endforeach; ?>
        
        <tr class="border-t-2 border-white/20 print:border-black font-bold text-base bg-slate-900/30 print:bg-transparent">
            <td colspan="4" class="text-right py-3 pr-4 uppercase text-slate-300 print:text-black font-black">TOTAL MASUK :</td>
            <td colspan="3" class="text-right text-emerald-400 print:text-black font-bold py-3 uppercase">Rp <?= number_format($tot_setor,0,',','.') ?></td>
        </tr>
        <tr class="border-b border-white/20 print:border-black font-bold text-base bg-slate-900/30 print:bg-transparent">
            <td colspan="4" class="text-right py-3 pr-4 uppercase text-slate-300 print:text-black font-black">TOTAL KELUAR :</td>
            <td colspan="3" class="text-right text-red-500 print:text-black font-bold py-3 uppercase">Rp <?= number_format($tot_tarik+$tot_admin,0,',','.') ?></td>
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
    .font-black, .text-emerald-400, .text-blue-400, .text-red-500, .text-yellow-500, .text-slate-300, .text-slate-400, .text-white { color: black !important; }
}
</style>

<?php require_once __DIR__ . '/../../template/footer.php'; ?>
