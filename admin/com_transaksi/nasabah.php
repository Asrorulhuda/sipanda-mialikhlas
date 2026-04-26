<?php
$page_title = 'Nasabah Tabungan';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara']);
cek_fitur('tabungan');

if (isset($_POST['simpan'])) {
    $no = 'TAB-'.str_pad($_POST['id_siswa'], 5, '0', STR_PAD_LEFT);
    $pdo->prepare("INSERT IGNORE INTO tbl_nasabah (id_siswa,no_rekening) VALUES (?,?)")->execute([$_POST['id_siswa'],$no]);
    flash('msg','Nasabah ditambahkan!'); header('Location: nasabah.php'); exit;
}
if (isset($_GET['hapus'])) { $pdo->prepare("DELETE FROM tbl_nasabah WHERE id_nasabah=?")->execute([$_GET['hapus']]); flash('msg','Dihapus!','warning'); header('Location: nasabah.php'); exit; }

$siswa = $pdo->query("SELECT s.* FROM tbl_siswa s WHERE s.status='Aktif' AND s.id_siswa NOT IN (SELECT id_siswa FROM tbl_nasabah) ORDER BY s.nama")->fetchAll();
$data = $pdo->query("SELECT n.*,s.nama,s.nisn,k.nama_kelas FROM tbl_nasabah n JOIN tbl_siswa s ON n.id_siswa=s.id_siswa LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas ORDER BY s.nama")->fetchAll();
require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<div class="glass rounded-xl p-5 mb-6"><form method="POST" class="flex gap-3 items-end">
    <div class="flex-1"><label class="block text-xs text-slate-400 mb-1">Pilih Siswa</label><select name="id_siswa" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><option value="">-- Pilih --</option><?php foreach ($siswa as $s): ?><option value="<?= $s['id_siswa'] ?>"><?= clean($s['nama']) ?> (<?= $s['nisn'] ?>)</option><?php endforeach; ?></select></div>
    <button type="submit" name="simpan" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-plus mr-1"></i>Daftarkan</button>
</form></div>
<div class="glass rounded-xl p-5"><div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">No. Rek</th><th class="pb-3">Nama</th><th class="pb-3">Kelas</th><th class="pb-3">Saldo</th><th class="pb-3">Aksi</th></tr></thead>
    <tbody><?php foreach ($data as $i => $r): ?>
    <tr class="border-b border-white/5"><td class="py-2"><?= $i+1 ?></td><td class="font-mono text-xs"><?= $r['no_rekening'] ?></td><td class="font-medium"><?= clean($r['nama']) ?></td><td><?= clean($r['nama_kelas']) ?></td><td class="text-emerald-400 font-medium"><?= rupiah($r['saldo']) ?></td>
    <td class="flex gap-1"><a href="transaksi.php?id=<?= $r['id_nasabah'] ?>" class="p-1.5 rounded bg-emerald-600/20 text-emerald-400 text-xs"><i class="fas fa-exchange-alt"></i></a><button onclick="confirmDelete('?hapus=<?= $r['id_nasabah'] ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 text-xs"><i class="fas fa-trash"></i></button></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
