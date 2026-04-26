<?php
$page_title = 'Buku Tamu Sekolah';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin', 'waka_humas']);
cek_fitur('humas');

if (isset($_POST['simpan'])) {
    $tgl = $_POST['tanggal'] . ' ' . date('H:i:s');
    $pdo->prepare("INSERT INTO tbl_humas_tamu (nama_tamu, no_hp, instansi, tujuan, tanggal, bertemu_dengan, status) VALUES (?,?,?,?,?,?,'Masuk')")
        ->execute([$_POST['nama_tamu'], $_POST['no_hp'], $_POST['instansi'], $_POST['tujuan'], $tgl, $_POST['bertemu_dengan']]);
    flash('msg', 'Data tamu berhasil disimpan!'); header('Location: tamu.php'); exit;
}

if (isset($_GET['checkout'])) {
    $pdo->prepare("UPDATE tbl_humas_tamu SET status = 'Keluar', waktu_keluar = NOW() WHERE id = ?")
        ->execute([$_GET['checkout']]);
    flash('msg', 'Tamu berhasil di Check-out!'); header('Location: tamu.php'); exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_humas_tamu WHERE id=?")->execute([$_GET['hapus']]);
    flash('msg', 'Data dihapus!', 'warning'); header('Location: tamu.php'); exit;
}

$data = $pdo->query("SELECT * FROM tbl_humas_tamu ORDER BY tanggal DESC")->fetchAll();

require_once __DIR__ . '/../../template/header.php';
require_once __DIR__ . '/../../template/sidebar.php';
require_once __DIR__ . '/../../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<div class="flex flex-wrap gap-4 mb-6">
    <button onclick="document.getElementById('frm').classList.toggle('hidden')" class="bg-rose-600 hover:bg-rose-500 px-4 py-2 rounded-lg text-sm font-medium shadow-lg shadow-rose-500/20 text-white"><i class="fas fa-user-plus mr-1"></i>Isi Buku Tamu</button>
    <button onclick="document.getElementById('filter-section').classList.toggle('hidden')" class="bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-lg text-sm font-medium shadow-lg text-white"><i class="fas fa-filter mr-1"></i>Filter & Laporan</button>
</div>

<div id="frm" class="hidden glass rounded-xl p-5 mb-6 border border-white/5 border-t-rose-500 border-t-4 shadow-2xl">
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="block text-xs text-slate-400 mb-1">Nama Tamu & Jabatan</label><input type="text" name="nama_tamu" required placeholder="Cth: Bpk Haryanto (Pengawas)" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-rose-500 focus:outline-none text-white"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Nomor WhatsApp</label><input type="text" name="no_hp" required placeholder="08xxxxxxxxxx" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-rose-500 focus:outline-none text-white"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Asal Instansi / Media</label><input type="text" name="instansi" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-rose-500 focus:outline-none text-white"></div>
        <div><label class="block text-xs text-slate-400 mb-1">Tanggal Kunjungan</label><input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-rose-500 focus:outline-none text-white"></div>
        <div class="md:col-span-2"><label class="block text-xs text-slate-400 mb-1">Bertemu Dengan</label><input type="text" name="bertemu_dengan" placeholder="Misal: Bapak Kepala Sekolah" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-rose-500 focus:outline-none text-white"></div>
        <div class="md:col-span-2"><label class="block text-xs text-slate-400 mb-1">Maksud / Tujuan Kunjungan</label><input type="text" name="tujuan" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-rose-500 focus:outline-none text-white"></div>
        <div class="md:col-span-2 pt-2">
            <button type="submit" name="simpan" class="bg-rose-600 hover:bg-rose-500 px-8 py-2.5 rounded-lg text-sm font-bold text-white"><i class="fas fa-save mr-1"></i>Simpan Kunjungan</button>
        </div>
    </form>
</div>

<!-- Filter & Laporan section -->
<div id="filter-section" class="hidden grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="glass rounded-xl p-5 border border-white/5 border-t-blue-500 border-t-4 shadow-xl">
        <h4 class="text-white font-bold text-sm mb-4"><i class="fas fa-calendar-alt mr-2 text-blue-400"></i>Cetak Laporan Range Tanggal</h4>
        <form action="cetak_tamu.php" method="POST" target="_blank" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[140px]">
                <label class="block text-[10px] text-slate-500 uppercase font-bold mb-1">Mulai</label>
                <input type="date" name="tgl_awal" value="<?= date('Y-m-01') ?>" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-xs focus:border-blue-500 focus:outline-none text-white">
            </div>
            <div class="flex-1 min-w-[140px]">
                <label class="block text-[10px] text-slate-500 uppercase font-bold mb-1">Sampai</label>
                <input type="date" name="tgl_akhir" value="<?= date('Y-m-d') ?>" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-xs focus:border-blue-500 focus:outline-none text-white">
            </div>
            <button type="submit" name="filter_range" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-xs font-bold whitespace-nowrap"><i class="fas fa-print mr-1"></i>Cetak</button>
        </form>
    </div>

    <div class="glass rounded-xl p-5 border border-white/5 border-t-emerald-500 border-t-4 shadow-xl">
        <h4 class="text-white font-bold text-sm mb-4"><i class="fas fa-calendar-check mr-2 text-emerald-400"></i>Cetak Laporan Bulanan</h4>
        <form action="cetak_tamu.php" method="POST" target="_blank" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[140px]">
                <label class="block text-[10px] text-slate-500 uppercase font-bold mb-1">Pilih Bulan</label>
                <select name="bulan" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none text-white">
                    <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?= $m ?>" <?= date('n') == $m ? 'selected' : '' ?>><?= bulan_indo($m) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="flex-1 min-w-[140px]">
                <label class="block text-[10px] text-slate-500 uppercase font-bold mb-1">Pilih Tahun</label>
                <select name="tahun" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none text-white">
                    <?php for($y=date('Y'); $y>=2023; $y--): ?>
                        <option value="<?= $y ?>"><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" name="filter_bulan" class="bg-emerald-600 hover:bg-emerald-500 px-4 py-2 rounded-lg text-xs font-bold whitespace-nowrap"><i class="fas fa-print mr-1"></i>Cetak</button>
        </form>
    </div>
</div>

<div class="glass rounded-xl p-5 border border-white/5">
    <form action="cetak_tamu.php" method="POST" target="_blank">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-white font-bold"><i class="fas fa-book-open mr-2"></i>Daftar Pengunjung</h3>
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium shadow-lg shadow-blue-500/20"><i class="fas fa-print mr-2"></i>Cetak Terpilih</button>
        </div>
        <div class="table-container">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-slate-400 border-b border-white/10">
                    <th class="pb-3 w-10 text-center"><input type="checkbox" onchange="document.querySelectorAll('.chk-cetak').forEach(c => c.checked = this.checked)" class="rounded border-white/10 bg-slate-800 text-blue-500"></th>
                    <th class="pb-3 w-48">Waktu Datang</th><th class="pb-3">Profil Tamu</th><th class="pb-3">Kepada / Keperluan</th><th class="pb-3">Status / Keluar</th><th class="pb-3 text-right">Aksi</th>
                </tr></thead>
                <tbody><?php foreach ($data as $r): ?>
                <tr class="border-b border-white/5 hover:bg-white/5">
                    <td class="py-3 text-center"><input type="checkbox" name="id_cetak[]" value="<?= $r['id'] ?>" class="chk-cetak rounded border-white/10 bg-slate-800 text-blue-500"></td>
                    <td class="py-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-rose-500/20 flex items-center justify-center text-rose-400"><i class="fas fa-clock text-xs"></i></div>
                <div>
                    <span class="block text-xs font-bold text-slate-200"><?= tgl_indo(date('Y-m-d', strtotime($r['tanggal']))) ?></span>
                    <span class="block text-[10px] text-slate-500 mt-0.5 font-mono"><?= date('H:i', strtotime($r['tanggal'])) ?> WIB</span>
                </div>
            </div>
        </td>
        <td>
            <span class="block font-bold text-white"><?= clean($r['nama_tamu']) ?></span>
            <div class="flex items-center gap-2 mt-1">
                <span class="text-[10px] text-slate-400"><i class="fas fa-building mr-1"></i><?= clean($r['instansi']) ?></span>
                <span class="text-[10px] text-emerald-400 font-bold"><i class="fab fa-whatsapp mr-1"></i><?= clean($r['no_hp']) ?></span>
            </div>
        </td>
        <td>
            <span class="block text-xs text-slate-300 font-bold mb-1"><i class="fas fa-user-tie mr-1 text-slate-500"></i><?= clean($r['bertemu_dengan'] ?: '-') ?></span>
            <span class="block text-[10px] text-slate-500 italic leading-relaxed line-clamp-1" title="<?= clean($r['tujuan']) ?>"><?= clean($r['tujuan']) ?></span>
        </td>
        <td>
            <?php if ($r['status'] == 'Masuk'): ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest bg-emerald-500/10 text-emerald-500 animate-pulse">DIDALAM</span>
            <?php else: ?>
                <div class="space-y-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest bg-slate-500/10 text-slate-500">PULANG</span>
                    <span class="block text-[9px] text-slate-600 font-mono italic"><?= date('H:i', strtotime($r['waktu_keluar'])) ?> WIB</span>
                </div>
            <?php endif; ?>
        </td>
        <td class="text-right whitespace-nowrap">
            <?php if ($r['status'] == 'Masuk'): ?>
                <a href="?checkout=<?= $r['id'] ?>" onclick="return confirm('Tamu sudah pulang?')" class="p-1.5 rounded bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600 hover:text-white text-[10px] mr-1" title="Check-out Manual"><i class="fas fa-sign-out-alt"></i></a>
            <?php endif; ?>
            <button type="button" onclick="confirmDelete('?hapus=<?= $r['id'] ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 hover:bg-red-600/40 text-[10px]"><i class="fas fa-trash"></i></button>
        </td>
    </tr>
    <?php endforeach; if(!$data) echo '<tr><td colspan="6" class="text-center py-8 text-slate-400"><i class="fas fa-book-open text-3xl mb-2 opacity-50 block"></i>Buku tamu belum diisi.</td></tr>'; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../../template/footer.php'; ?>
