<?php
$page_title = 'Journal Mengajar';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('akademik');
$id = $_SESSION['user_id'];
if (isset($_POST['simpan'])) { 
    $pdo->prepare("INSERT INTO tbl_journal (id_guru,id_mapel,id_kelas,jam_ke,tanggal,materi,materi_akan_datang,jml_h,jml_i,jml_s,jml_a,keterangan) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")->execute([
        $id, $_POST['id_mapel'], $_POST['id_kelas'], $_POST['jam_ke'], $_POST['tanggal'], 
        $_POST['materi'], $_POST['materi_akan_datang'], 
        (int)$_POST['jml_h'], (int)$_POST['jml_i'], (int)$_POST['jml_s'], (int)$_POST['jml_a'], 
        $_POST['ket']
    ]); 
    flash('msg','Berhasil disimpan!'); 
    header('Location: journal.php'); 
    exit; 
}
if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_journal WHERE id=? AND id_guru=?")->execute([$_GET['hapus'], $id]);
    flash('msg','Berhasil dihapus!','warning');
    header('Location: journal.php');
    exit;
}
$stmt = $pdo->prepare("SELECT DISTINCT m.* FROM tbl_jadwal j JOIN tbl_mapel m ON j.id_mapel=m.id_mapel WHERE j.id_guru=? ORDER BY m.nama_mapel"); $stmt->execute([$id]); $mapel = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT DISTINCT k.* FROM tbl_jadwal j JOIN tbl_kelas k ON j.id_kelas=k.id_kelas WHERE j.id_guru=? ORDER BY k.nama_kelas"); $stmt->execute([$id]); $kelas = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT j.*,m.nama_mapel,k.nama_kelas FROM tbl_journal j LEFT JOIN tbl_mapel m ON j.id_mapel=m.id_mapel LEFT JOIN tbl_kelas k ON j.id_kelas=k.id_kelas WHERE j.id_guru=? ORDER BY j.tanggal DESC LIMIT 30"); $stmt->execute([$id]); $data = $stmt->fetchAll();
require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<div class="flex flex-wrap items-center justify-between mb-6 gap-4">
    <h2 class="text-xl font-bold text-white"><i class="fas fa-book-open mr-2 text-blue-400"></i>Isi Jurnal KBM</h2>
    
    <form action="cetak_journal_hari.php" method="GET" target="_blank" class="flex gap-2 p-2 bg-purple-500/10 border border-purple-500/20 rounded-xl items-center">
        <label class="text-xs font-bold text-purple-300 ml-2">Cetak Jurnal Harian:</label>
        <input type="date" name="tgl" value="<?= date('Y-m-d') ?>" class="bg-slate-900 border border-white/10 rounded-lg px-2 py-1 text-sm text-white">
        <button type="submit" class="bg-purple-600 hover:bg-purple-500 px-3 py-1.5 rounded-lg text-sm font-medium text-white transition-all"><i class="fas fa-print mr-1"></i>Cetak</button>
    </form>
</div>

<div class="glass rounded-xl p-5 mb-6"><form method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-5">
    <div class="col-span-12 md:col-span-3"><label class="block text-xs font-bold text-slate-400 mb-1 uppercase tracking-widest">Tanggal</label><input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all cursor-pointer"></div>
    <div class="col-span-12 md:col-span-3"><label class="block text-xs font-bold text-slate-400 mb-1 uppercase tracking-widest">Jam Ke (cth: 1-2)</label><input type="text" name="jam_ke" placeholder="1-2" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all"></div>
    <div class="col-span-12 md:col-span-3"><label class="block text-xs font-bold text-slate-400 mb-1 uppercase tracking-widest">Mapel</label><select name="id_mapel" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all cursor-pointer"><option value="">-- Pilih --</option><?php foreach ($mapel as $m): ?><option value="<?= $m['id_mapel'] ?>"><?= clean($m['nama_mapel']) ?></option><?php endforeach; ?></select></div>
    <div class="col-span-12 md:col-span-3"><label class="block text-xs font-bold text-slate-400 mb-1 uppercase tracking-widest">Kelas</label><select name="id_kelas" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all cursor-pointer"><option value="">-- Pilih --</option><?php foreach ($kelas as $k): ?><option value="<?= $k['id_kelas'] ?>"><?= clean($k['nama_kelas']) ?></option><?php endforeach; ?></select></div>
    
    <div class="col-span-12 md:col-span-6"><label class="block text-xs font-bold text-slate-400 mb-1 uppercase tracking-widest">Materi yang Disampaikan</label><textarea name="materi" rows="3" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all" placeholder="Tuliskan materi..."></textarea></div>
    <div class="col-span-12 md:col-span-6"><label class="block text-xs font-bold text-slate-400 mb-1 uppercase tracking-widest">Materi yang Akan Datang</label><textarea name="materi_akan_datang" rows="3" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all" placeholder="(Opsional)..."></textarea></div>
    
    <div class="col-span-12 md:col-span-7">
        <label class="block text-xs font-bold text-slate-400 mb-2 uppercase tracking-widest text-center border-b border-white/10 pb-2">Data Kehadiran Siswa</label>
        <div class="grid grid-cols-4 gap-2">
            <div><label class="block text-[10px] text-emerald-400 mb-1 text-center font-bold">Hadir</label><input type="number" min="0" name="jml_h" value="0" class="w-full text-center bg-emerald-500/10 border border-emerald-500/20 rounded-lg px-2 py-2 text-sm text-emerald-400 focus:border-emerald-500 outline-none"></div>
            <div><label class="block text-[10px] text-purple-400 mb-1 text-center font-bold">Izin</label><input type="number" min="0" name="jml_i" value="0" class="w-full text-center bg-purple-500/10 border border-purple-500/20 rounded-lg px-2 py-2 text-sm text-purple-400 focus:border-purple-500 outline-none"></div>
            <div><label class="block text-[10px] text-blue-400 mb-1 text-center font-bold">Sakit</label><input type="number" min="0" name="jml_s" value="0" class="w-full text-center bg-blue-500/10 border border-blue-500/20 rounded-lg px-2 py-2 text-sm text-blue-400 focus:border-blue-500 outline-none"></div>
            <div><label class="block text-[10px] text-rose-400 mb-1 text-center font-bold">Alpha</label><input type="number" min="0" name="jml_a" value="0" class="w-full text-center bg-rose-500/10 border border-rose-500/20 rounded-lg px-2 py-2 text-sm text-rose-400 focus:border-rose-500 outline-none"></div>
        </div>
    </div>
    <div class="col-span-12 md:col-span-5 flex flex-col justify-end">
        <label class="block text-xs font-bold text-slate-400 mb-1 uppercase tracking-widest text-center border-b border-white/10 pb-2">Lainnya</label>
        <input type="text" name="ket" placeholder="Keterangan tambahan (Opsional)" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2.5 text-sm focus:border-blue-500 outline-none mb-3">
        <button type="submit" name="simpan" class="w-full bg-blue-600 hover:bg-blue-500 px-6 py-2.5 rounded-lg text-sm font-bold shadow-lg shadow-blue-600/30 transition-all uppercase tracking-widest"><i class="fas fa-save mr-2"></i>Simpan Jurnal</button>
    </div>
</form></div>

<div class="glass rounded-xl p-5 mb-10"><div class="table-container"><table class="w-full text-sm">
    <thead>
        <tr class="text-left text-slate-400 border-b border-white/10 text-xs uppercase tracking-widest">
            <th class="pb-3 px-2">Data KBM</th>
            <th class="pb-3 px-2 w-[40%]">Materi</th>
            <th class="pb-3 px-2 text-center">H/I/S/A</th>
            <th class="pb-3 px-2 text-right">Aksi</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-white/5"><?php foreach ($data as $r): ?>
    <tr class="hover:bg-white/5 transition-all group">
        <td class="py-3 px-2">
            <p class="font-bold text-white text-xs mb-0.5"><i class="fas fa-calendar-day text-blue-400 p-1 bg-blue-400/10 rounded mr-1"></i> <?= tgl_indo($r['tanggal']) ?></p>
            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider mt-1"><?= clean($r['nama_kelas']) ?> <span class="mx-1 opacity-30">|</span> Jam <?= clean($r['jam_ke'] ?? '-') ?></p>
            <p class="text-xs text-blue-300 font-medium"><?= clean($r['nama_mapel']) ?></p>
        </td>
        <td class="py-3 px-2 align-top">
            <div class="mb-2">
                <span class="text-[9px] bg-white/10 text-white/50 px-2 py-0.5 rounded uppercase tracking-wider font-bold block mb-1 w-max">Disampaikan</span>
                <p class="text-xs text-slate-300"><?= nl2br(clean($r['materi'])) ?></p>
            </div>
            <?php if($r['materi_akan_datang']): ?>
            <div class="mt-2 border-t border-white/5 pt-2">
                <span class="text-[9px] bg-blue-500/10 text-blue-400/70 px-2 py-0.5 rounded uppercase tracking-wider font-bold block mb-1 w-max">Akan Datang</span>
                <p class="text-[11px] text-slate-400 italic"><?= nl2br(clean($r['materi_akan_datang'])) ?></p>
            </div>
            <?php endif; ?>
        </td>
        <td class="py-3 px-2 text-center font-mono text-[10px] whitespace-nowrap">
            <span class="text-emerald-400 px-1 bg-emerald-500/10 rounded" title="Hadir"><?= $r['jml_h'] ?? 0 ?></span> /
            <span class="text-purple-400 px-1 bg-purple-500/10 rounded" title="Izin"><?= $r['jml_i'] ?? 0 ?></span> /
            <span class="text-blue-400 px-1 bg-blue-500/10 rounded" title="Sakit"><?= $r['jml_s'] ?? 0 ?></span> /
            <span class="text-rose-400 px-1 bg-rose-500/10 rounded" title="Alpha"><?= $r['jml_a'] ?? 0 ?></span>
        </td>
        <td class="py-3 px-2 text-right">
            <button onclick="confirmDelete('?hapus=<?= $r['id'] ?>')" class="text-slate-500 hover:text-rose-500 transition-colors p-2"><i class="fas fa-trash-alt"></i></button>
        </td>
    </tr>
    <?php endforeach; if(empty($data)): ?>
    <tr><td colspan="4" class="text-center py-6 text-slate-500">Belum ada data jurnal KBM.</td></tr>
    <?php endif; ?></tbody>
</table></div></div>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
