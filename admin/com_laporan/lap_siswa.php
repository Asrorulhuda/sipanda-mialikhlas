<?php
$page_title = 'Laporan Data Siswa';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara','kepsek']);
cek_fitur('laporan');
$kelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 0;
$status = $_GET['status'] ?? 'Aktif';
$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();

$q = "SELECT s.*, k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE s.status=?";
$params = [$status];
if ($kelas) { $q .= " AND s.id_kelas=?"; $params[] = $kelas; }
$q .= " ORDER BY k.nama_kelas, s.nama";
$stmt = $pdo->prepare($q); $stmt->execute($params);
$data = $stmt->fetchAll();

require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>
<div class="glass rounded-xl p-5 mb-6">
    <div class="flex flex-col sm:flex-row gap-3 items-end justify-between">
        <form method="GET" class="flex gap-3 items-end flex-wrap">
            <div><label class="block text-xs text-slate-400 mb-1">Kelas</label><select name="kelas" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><option value="">Semua</option><?php foreach ($kelas_list as $k): ?><option value="<?= $k['id_kelas'] ?>" <?= $kelas==$k['id_kelas']?'selected':'' ?>><?= clean($k['nama_kelas']) ?></option><?php endforeach; ?></select></div>
            <div><label class="block text-xs text-slate-400 mb-1">Status</label><select name="status" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><?php foreach(['Aktif','Lulus','Pindah','Keluar'] as $st): ?><option value="<?= $st ?>" <?= $status==$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?></select></div>
            <button class="bg-blue-600 px-4 py-2 rounded-lg text-sm"><i class="fas fa-filter mr-1"></i>Filter</button>
        </form>
        <div class="flex gap-2">
            <a href="cetak_siswa.php?kelas=<?= $kelas ?>" target="_blank" class="bg-purple-600/80 hover:bg-purple-600 px-3 py-2 rounded-lg text-xs"><i class="fas fa-print mr-1"></i>Cetak</a>
            <a href="cetak_siswa.php?kelas=<?= $kelas ?>&export=csv" class="bg-emerald-600/80 hover:bg-emerald-600 px-3 py-2 rounded-lg text-xs"><i class="fas fa-file-excel mr-1"></i>Excel</a>
        </div>
    </div>
</div>
<div class="glass rounded-xl p-5"><div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">NISN</th><th class="pb-3">Nama</th><th class="pb-3">JK</th><th class="pb-3">Kelas</th><th class="pb-3">TTL</th><th class="pb-3">HP Ortu</th><th class="pb-3">Status</th></tr></thead>
    <tbody><?php if ($data): foreach ($data as $i => $r): ?>
    <tr class="border-b border-white/5">
        <td class="py-2"><?= $i+1 ?></td>
        <td class="font-mono text-xs"><?= clean($r['nisn'] ?? '-') ?></td>
        <td class="font-medium"><?= clean($r['nama']) ?></td>
        <td><span class="px-2 py-0.5 rounded-full text-xs <?= $r['jk']=='L'?'bg-blue-500/20 text-blue-400':'bg-pink-500/20 text-pink-400' ?>"><?= $r['jk'] ?></span></td>
        <td><?= clean($r['nama_kelas'] ?? '-') ?></td>
        <td class="text-xs text-slate-400"><?= clean($r['tempat_lahir'] ?? '') ?><?= $r['tanggal_lahir'] ? ', '.date('d/m/Y', strtotime($r['tanggal_lahir'])) : '' ?></td>
        <td class="text-xs"><?= clean($r['no_hp_ortu'] ?? '-') ?></td>
        <td><span class="px-2 py-0.5 rounded-full text-xs <?= $r['status']=='Aktif'?'bg-emerald-500/20 text-emerald-400':'bg-red-500/20 text-red-400' ?>"><?= $r['status'] ?></span></td>
    </tr>
    <?php endforeach; else: ?><tr><td colspan="8" class="py-4 text-center text-slate-500">Tidak ada data siswa.</td></tr><?php endif; ?></tbody>
</table></div>
<p class="text-xs text-slate-500 mt-3">Total: <?= count($data) ?> siswa</p>
</div>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
