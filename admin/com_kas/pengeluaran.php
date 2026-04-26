<?php
$page_title = 'Pengeluaran Kas';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara']);
cek_fitur('kas');

if (isset($_POST['simpan'])) { $pdo->prepare("INSERT INTO tbl_pengeluaran_kas (tanggal,id_jenis,uraian,jumlah,id_ta) VALUES (?,?,?,?,?)")->execute([$_POST['tanggal'],$_POST['id_jenis'],$_POST['uraian'],$_POST['jumlah'],get_ta_aktif($pdo)['id_ta']??null]); flash('msg','Berhasil!'); header('Location: pengeluaran.php'); exit; }
if (isset($_GET['hapus'])) { $pdo->prepare("DELETE FROM tbl_pengeluaran_kas WHERE id=?")->execute([$_GET['hapus']]); flash('msg','Dihapus!','warning'); header('Location: pengeluaran.php'); exit; }

$jenis = $pdo->query("SELECT * FROM tbl_jenis_keluar ORDER BY nama")->fetchAll();
$bln = (int)($_GET['bln'] ?? date('m')); $thn = (int)($_GET['thn'] ?? date('Y'));
$stmt = $pdo->prepare("SELECT p.*,j.nama FROM tbl_pengeluaran_kas p LEFT JOIN tbl_jenis_keluar j ON p.id_jenis=j.id WHERE MONTH(p.tanggal)=? AND YEAR(p.tanggal)=? ORDER BY p.tanggal DESC"); $stmt->execute([$bln, $thn]); $data = $stmt->fetchAll();
$total = array_sum(array_column($data,'jumlah'));
require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<div class="glass rounded-xl p-5 mb-6"><form method="POST" class="flex flex-wrap gap-3 items-end">
    <div><label class="block text-xs text-slate-400 mb-1">Tanggal</label><input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
    <div><label class="block text-xs text-slate-400 mb-1">Jenis</label><select name="id_jenis" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><?php foreach ($jenis as $j): ?><option value="<?= $j['id'] ?>"><?= clean($j['nama']) ?></option><?php endforeach; ?></select></div>
    <div class="flex-1 min-w-[200px]"><label class="block text-xs text-slate-400 mb-1">Uraian</label><input type="text" name="uraian" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
    <div><label class="block text-xs text-slate-400 mb-1">Jumlah (Rp)</label><input type="number" name="jumlah" required class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm w-40"></div>
    <button type="submit" name="simpan" class="bg-red-600 hover:bg-red-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-plus mr-1"></i>Tambah</button>
</form></div>
<div class="flex gap-3 mb-4">
    <form method="GET" class="flex gap-2 items-center"><select name="bln" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><?php for($i=1;$i<=12;$i++): ?><option value="<?= $i ?>" <?= $bln==$i?'selected':'' ?>><?= bulan_indo($i) ?></option><?php endfor; ?></select><input type="number" name="thn" value="<?= $thn ?>" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm w-24"><button class="bg-blue-600 px-3 py-2 rounded-lg text-sm"><i class="fas fa-filter"></i></button></form>
    <div class="glass px-4 py-2 rounded-lg text-sm">Total: <span class="text-red-400 font-bold"><?= rupiah($total) ?></span></div>
</div>
<div class="glass rounded-xl p-5"><div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">Tanggal</th><th class="pb-3">Jenis</th><th class="pb-3">Uraian</th><th class="pb-3">Jumlah</th><th class="pb-3">Aksi</th></tr></thead>
    <tbody><?php foreach ($data as $i => $r): ?>
    <tr class="border-b border-white/5"><td class="py-2"><?= $i+1 ?></td><td><?= tgl_indo($r['tanggal']) ?></td><td><span class="px-2 py-0.5 rounded-full text-xs bg-red-500/20 text-red-400"><?= clean($r['nama']??'-') ?></span></td><td><?= clean($r['uraian']) ?></td><td class="text-red-400 font-medium"><?= rupiah($r['jumlah']) ?></td>
    <td><button onclick="confirmDelete('?hapus=<?= $r['id'] ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 text-xs"><i class="fas fa-trash"></i></button></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
