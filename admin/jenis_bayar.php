<?php
$page_title = 'Jenis Bayar';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin','bendahara']);
cek_fitur('keuangan');

if (isset($_POST['simpan'])) { $pdo->prepare("INSERT INTO tbl_jenis_bayar (nama_jenis,tipe,id_pos,id_ta) VALUES (?,?,?,?)")->execute([$_POST['nama'],$_POST['tipe'],$_POST['id_pos'],$_POST['id_ta']]); flash('msg','Berhasil!'); header('Location: jenis_bayar.php'); exit; }
if (isset($_POST['update'])) { $pdo->prepare("UPDATE tbl_jenis_bayar SET nama_jenis=?,tipe=?,id_pos=?,id_ta=? WHERE id_jenis=?")->execute([$_POST['nama'],$_POST['tipe'],$_POST['id_pos'],$_POST['id_ta'],$_POST['id']]); flash('msg','Berhasil!'); header('Location: jenis_bayar.php'); exit; }
if (isset($_GET['hapus'])) { $pdo->prepare("DELETE FROM tbl_jenis_bayar WHERE id_jenis=?")->execute([$_GET['hapus']]); flash('msg','Dihapus!','warning'); header('Location: jenis_bayar.php'); exit; }

$pos = $pdo->query("SELECT * FROM tbl_pos_bayar")->fetchAll();
$ta = $pdo->query("SELECT * FROM tbl_tahun_ajaran ORDER BY id_ta DESC")->fetchAll();
$data = $pdo->query("SELECT j.*, p.nama_pos, t.tahun FROM tbl_jenis_bayar j LEFT JOIN tbl_pos_bayar p ON j.id_pos=p.id_pos LEFT JOIN tbl_tahun_ajaran t ON j.id_ta=t.id_ta ORDER BY j.id_jenis DESC")->fetchAll();
if (isset($_GET['edit'])) { $stmt = $pdo->prepare("SELECT * FROM tbl_jenis_bayar WHERE id_jenis=?"); $stmt->execute([(int)$_GET['edit']]); $edit = $stmt->fetch(); } else { $edit = null; }
require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<div class="glass rounded-xl p-5 mb-6">
    <form method="POST" class="flex flex-wrap gap-3 items-end">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id_jenis'] ?>"><?php endif; ?>
        <div class="flex-1 min-w-[180px]"><label class="block text-xs text-slate-400 mb-1">Nama Jenis</label><input type="text" name="nama" value="<?= clean($edit['nama_jenis']??'') ?>" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Tipe</label><select name="tipe" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><option value="Bulanan" <?= ($edit['tipe']??'')=='Bulanan'?'selected':'' ?>>Bulanan</option><option value="Bebas" <?= ($edit['tipe']??'')=='Bebas'?'selected':'' ?>>Bebas</option></select></div>
        <div><label class="block text-xs text-slate-400 mb-1">Pos Bayar</label><select name="id_pos" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><?php foreach ($pos as $p): ?><option value="<?= $p['id_pos'] ?>" <?= ($edit['id_pos']??'')==$p['id_pos']?'selected':'' ?>><?= clean($p['nama_pos']) ?></option><?php endforeach; ?></select></div>
        <div><label class="block text-xs text-slate-400 mb-1">TA</label>
        <?php 
        $edit_tahun = '';
        if ($edit && !empty($edit['id_ta'])) {
            $stmt_ta = $pdo->prepare("SELECT tahun FROM tbl_tahun_ajaran WHERE id_ta=?");
            $stmt_ta->execute([$edit['id_ta']]);
            $edit_tahun = $stmt_ta->fetchColumn();
        }
        ?>
        <select name="id_ta" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
            <?php foreach ($ta as $t): ?>
            <option value="<?= $t['id_ta'] ?>" <?= $edit_tahun === $t['tahun'] ? 'selected' : '' ?>><?= $t['tahun'] ?></option>
            <?php endforeach; ?>
        </select></div>
        <button type="submit" name="<?= $edit?'update':'simpan' ?>" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-save mr-1"></i><?= $edit?'Update':'Simpan' ?></button>
    </form>
</div>
<div class="glass rounded-xl p-5"><div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">Nama</th><th class="pb-3">Tipe</th><th class="pb-3">Pos</th><th class="pb-3">TA</th><th class="pb-3">Aksi</th></tr></thead>
    <tbody><?php foreach ($data as $i => $r): ?>
    <tr class="border-b border-white/5"><td class="py-2"><?= $i+1 ?></td><td class="font-medium"><?= clean($r['nama_jenis']) ?></td>
    <td><span class="px-2 py-0.5 rounded-full text-xs <?= $r['tipe']=='Bulanan'?'bg-blue-500/20 text-blue-400':'bg-purple-500/20 text-purple-400' ?>"><?= $r['tipe'] ?></span></td>
    <td><?= clean($r['nama_pos']) ?></td><td class="text-slate-400"><?= $r['tahun'] ?></td>
    <td class="flex gap-1"><a href="?edit=<?= $r['id_jenis'] ?>" class="p-1.5 rounded bg-blue-600/20 text-blue-400 text-xs"><i class="fas fa-edit"></i></a><button onclick="confirmDelete('?hapus=<?= $r['id_jenis'] ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 text-xs"><i class="fas fa-trash"></i></button></td></tr>
    <?php endforeach; ?></tbody>
</table></div></div>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
