<?php
$page_title = 'SKL (Surat Keterangan Lulus)';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);

// Helper: Generate SKL Number
function generate_skl_number($format, $no, $ta_tahun) {
    $months = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
    $romans = $months[date('n')];
    $s = str_replace('[NO]', str_pad($no, 3, '0', STR_PAD_LEFT), $format);
    $s = str_replace('[TA]', $ta_tahun, $s);
    $s = str_replace('[MONTH]', $romans, $s);
    $s = str_replace('[YEAR]', date('Y'), $s);
    return $s;
}

// 1. Simpan Pengaturan
if (isset($_POST['simpan_setting'])) {
    $pdo->prepare("UPDATE tbl_setting SET skl_format=?, skl_next_no=? WHERE id=1")
        ->execute([$_POST['skl_format'], $_POST['skl_next_no']]);
    flash('msg', 'Pengaturan penomoran SKL disimpan!');
    header('Location: skl.php'); exit;
}

// 2. Terbitkan Masal
if (isset($_POST['terbit_masal'])) {
    $siswa_lulus_no_skl = $pdo->query("SELECT s.id_siswa FROM tbl_siswa s LEFT JOIN tbl_skl sk ON s.id_siswa = sk.id_siswa WHERE s.status='Lulus' AND sk.id_siswa IS NULL")->fetchAll();
    
    if (!$siswa_lulus_no_skl) {
        flash('msg', 'Semua siswa lulus sudah memiliki SKL!', 'info');
    } else {
        $setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
        $ta_aktif = get_ta_aktif($pdo);
        $next_no = (int)$setting['skl_next_no'];
        
        $count = 0;
        foreach ($siswa_lulus_no_skl as $s) {
            $nomor = generate_skl_number($setting['skl_format'], $next_no, $ta_aktif['tahun']);
            $token = bin2hex(random_bytes(8));
            $pdo->prepare("INSERT INTO tbl_skl (id_siswa, nomor_skl, tanggal, id_ta, v_token) VALUES (?,?,?,?,?)")
                ->execute([$s['id_siswa'], $nomor, date('Y-m-d'), $ta_aktif['id_ta'], $token]);
            $next_no++;
            $count++;
        }
        
        // Update next number
        $pdo->prepare("UPDATE tbl_setting SET skl_next_no=? WHERE id=1")->execute([$next_no]);
        flash('msg', "$count SKL berhasil diterbitkan secara massal!");
    }
    header('Location: skl.php'); exit;
}

// 3. Simpan Manual (Existing with auto-num if number is empty)
if (isset($_POST['simpan_manual'])) {
    $setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
    $nomor = $_POST['nomor'];
    $next_no = (int)$setting['skl_next_no'];
    
    if (empty($nomor)) {
        $ta = $pdo->prepare("SELECT tahun FROM tbl_tahun_ajaran WHERE id_ta=?");
        $ta->execute([$_POST['id_ta']]);
        $ta_tahun = $ta->fetchColumn();
        $nomor = generate_skl_number($setting['skl_format'], $next_no, $ta_tahun);
        $pdo->prepare("UPDATE tbl_setting SET skl_next_no=? WHERE id=1")->execute([$next_no + 1]);
    }
    
    $token = bin2hex(random_bytes(8));
    $pdo->prepare("INSERT INTO tbl_skl (id_siswa, nomor_skl, tanggal, id_ta, v_token) VALUES (?,?,?,?,?)")
        ->execute([$_POST['id_siswa'], $nomor, $_POST['tanggal'], $_POST['id_ta'], $token]);
        
    flash('msg', 'SKL berhasil dibuat!');
    header('Location: skl.php'); exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_skl WHERE id=?")->execute([$_GET['hapus']]);
    flash('msg', 'Data dihapus!', 'warning');
    header('Location: skl.php'); exit;
}

$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
$siswa_lulus = $pdo->query("SELECT * FROM tbl_siswa WHERE status='Lulus' AND id_siswa NOT IN (SELECT id_siswa FROM tbl_skl) ORDER BY nama")->fetchAll();
$ta_list = $pdo->query("SELECT * FROM tbl_tahun_ajaran ORDER BY id_ta DESC")->fetchAll();
$data = $pdo->query("SELECT s.*, si.nama, si.nisn, t.tahun FROM tbl_skl s JOIN tbl_siswa si ON s.id_siswa=si.id_siswa LEFT JOIN tbl_tahun_ajaran t ON s.id_ta=t.id_ta ORDER BY s.id DESC")->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-white">Manajemen SKL</h2>
    <div class="flex gap-2">
        <button onclick="document.getElementById('modalSetting').classList.remove('hidden')" class="bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-lg text-sm font-medium transition-all shadow-lg border border-white/5">
            <i class="fas fa-cog mr-2"></i>Pengaturan Nomor
        </button>
        <form method="POST" onsubmit="return confirm('Terbitkan SKL untuk semua siswa lulus yang belum memiliki nomor SKL?')">
            <button type="submit" name="terbit_masal" class="bg-amber-600 hover:bg-amber-500 px-4 py-2 rounded-lg text-sm font-medium transition-all shadow-lg shadow-amber-600/20">
                <i class="fas fa-bolt mr-2"></i>Terbitkan Masal
            </button>
        </form>
    </div>
</div>

<?= alert_flash('msg') ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Form Manual -->
    <div class="lg:col-span-1">
        <div class="glass rounded-xl p-5 border border-white/5 h-full">
            <h3 class="text-sm font-bold mb-4 flex items-center gap-2"><i class="fas fa-plus-circle text-blue-400"></i>Buat SKL Manual</h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Siswa Lulus</label>
                    <select name="id_siswa" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                        <option value="">-- Pilih Siswa --</option>
                        <?php foreach ($siswa_lulus as $s): ?>
                            <option value="<?= $s['id_siswa'] ?>"><?= clean($s['nama']) ?> (<?= $s['nisn'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Nomor SKL (Kosongkan utk Otomatis)</label>
                    <input type="text" name="nomor" placeholder="Otomatis: <?= generate_skl_number($setting['skl_format'], $setting['skl_next_no'], '---') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Tanggal SKL</label>
                        <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Tahun Ajaran</label>
                        <select name="id_ta" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                            <?php foreach ($ta_list as $t): ?>
                                <option value="<?= $t['id_ta'] ?>" <?= $t['status']=='aktif'?'selected':'' ?>><?= $t['tahun'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="simpan_manual" class="w-full bg-blue-600 hover:bg-blue-500 py-2.5 rounded-lg text-sm font-bold shadow-lg shadow-blue-600/20 transition-all">Simpan SKL</button>
            </form>
        </div>
    </div>

    <!-- Statistik Ringkas -->
    <div class="lg:col-span-2">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 h-full">
            <div class="glass rounded-xl p-5 border border-white/5 flex flex-col justify-center">
                <p class="text-xs text-slate-400 uppercase tracking-widest mb-1">Belum Terbit SKL</p>
                <div class="flex items-end justify-between">
                    <h4 class="text-3xl font-black text-white"><?= count($siswa_lulus) ?></h4>
                    <p class="text-[10px] text-amber-400 font-bold mb-1">Siswa Lulus</p>
                </div>
            </div>
            <div class="glass rounded-xl p-5 border border-white/5 flex flex-col justify-center">
                <p class="text-xs text-slate-400 uppercase tracking-widest mb-1">SKL Terbit</p>
                <div class="flex items-end justify-between">
                    <h4 class="text-3xl font-black text-emerald-400"><?= count($data) ?></h4>
                    <p class="text-[10px] text-slate-500 font-bold mb-1">Total Dokumen</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="glass rounded-xl p-5 overflow-hidden border border-white/5">
    <div class="table-container">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-slate-400 border-b border-white/10">
                    <th class="pb-3 w-10">#</th>
                    <th class="pb-3">Siswa</th>
                    <th class="pb-3">Nomor SKL</th>
                    <th class="pb-3">Tanggal Terbit</th>
                    <th class="pb-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $i => $r): ?>
                <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                    <td class="py-3 text-slate-500"><?= $i+1 ?></td>
                    <td class="py-3">
                        <div class="font-bold text-white"><?= clean($r['nama']) ?></div>
                        <div class="text-[10px] text-slate-500 font-mono">NISN: <?= clean($r['nisn']) ?></div>
                    </td>
                    <td class="py-3 text-blue-400 font-mono text-xs"><?= clean($r['nomor_skl']) ?></td>
                    <td class="py-3 text-slate-400"><?= tgl_indo($r['tanggal']) ?> <span class="text-[10px] text-slate-600">(TA <?= $r['tahun'] ?>)</span></td>
                    <td class="py-3 text-center">
                        <a href="cetak_skl.php?id=<?= $r['id'] ?>" target="_blank" class="inline-flex items-center justify-center p-2 rounded-lg bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600 hover:text-white transition-all mr-1" title="Cetak SKL">
                            <i class="fas fa-print"></i>
                        </a>
                        <button onclick="confirmDelete('?hapus=<?= $r['id'] ?>')" class="inline-flex items-center justify-center p-2 rounded-lg bg-red-600/20 text-red-400 hover:bg-red-600 hover:text-white transition-all" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; if(!$data) echo '<tr><td colspan="5" class="text-center py-10 text-slate-500 italic">Belum ada SKL yang diterbitkan.</td></tr>'; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Setting -->
<div id="modalSetting" class="fixed inset-0 z-50 flex items-center justify-center px-4 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="glass w-full max-w-md relative z-10 p-6 rounded-2xl border border-white/10 shadow-2xl animate-zoom-in">
        <h3 class="text-lg font-bold text-white mb-4">Pengaturan Nomor SKL</h3>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs text-slate-400 mb-1">Format Nomor</label>
                <input type="text" name="skl_format" value="<?= clean($setting['skl_format']) ?>" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none font-mono">
                <div class="mt-2 p-2 bg-slate-900/50 rounded text-[10px] text-slate-500 leading-relaxed font-mono">
                    Placeholders:<br>
                    [NO] : No Otomatis (001)<br>
                    [TA] : Tahun Ajaran Aktif<br>
                    [MONTH] : Bulan Romawi (V)<br>
                    [YEAR] : Tahun Kalender (2026)
                </div>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Mulai Dari Nomor (Next)</label>
                <input type="number" name="skl_next_no" value="<?= $setting['skl_next_no'] ?>" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
            </div>
            <div class="pt-2 flex gap-2">
                <button type="submit" name="simpan_setting" class="flex-1 bg-blue-600 hover:bg-blue-500 py-2.5 rounded-xl text-sm font-bold transition-all">Simpan Pengaturan</button>
                <button type="button" onclick="this.closest('#modalSetting').classList.add('hidden')" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-400 hover:text-white transition-all">Batal</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
