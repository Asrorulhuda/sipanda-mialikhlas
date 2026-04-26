<?php
$page_title = 'Kelola Daftar Nilai Dinamis';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','guru']);
cek_fitur('akademik');

$ta = get_ta_aktif($pdo);

// 1. Simpan Konfigurasi Komponen Penilaian (Admin Override)
if (isset($_POST['simpan_komponen']) && $ta) {
    $id_mapel = $_POST['id_mapel'];
    $id_kelas = $_POST['id_kelas'];
    
    // Build JSON components
    $komponen = [];
    $total_bobot = 0;
    if (!empty($_POST['k_nama'])) {
        foreach ($_POST['k_nama'] as $idx => $nama) {
            $bobot = (float)$_POST['k_bobot'][$idx];
            $kolom = (int)$_POST['k_kolom'][$idx];
            $total_bobot += $bobot;
            $komponen[] = [
                'id' => 'c' . uniqid(),
                'nama' => clean($nama),
                'bobot' => $bobot,
                'kolom' => max(1, $kolom)
            ];
        }
    }
    
    $json_komponen = json_encode($komponen);

    // Upsert as Admin (id_guru = 0)
    $stmt = $pdo->prepare("INSERT INTO tbl_bobot_nilai (id_guru, id_mapel, id_kelas, id_ta, komponen_json) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE komponen_json=VALUES(komponen_json)");
    $stmt->execute([0, $id_mapel, $id_kelas, $ta['id_ta'], $json_komponen]);

    flash('msg', 'Struktur Komponen Nilai (Admin) Berhasil Disimpan! Total Bobot: '.$total_bobot.'%', 'success');
    header('Location: raport.php?kelas='.$id_kelas.'&mapel='.$id_mapel);
    exit;
}

// 2. Simpan Nilai Massal
if (isset($_POST['simpan_massal']) && $ta) {
    $id_mapel = $_POST['id_mapel'];
    $id_kelas = $_POST['id_kelas'];
    $komponen_json = $_POST['komponen_json'];
    $komponen_arr = json_decode($komponen_json, true) ?: [];

    foreach ($_POST['id_siswa'] as $i => $id_siswa) {
        $data_nilai = [];
        $na = 0;
        
        foreach ($komponen_arr as $comp) {
            $cid = $comp['id'];
            $bobot = $comp['bobot'];
            $kolom = $comp['kolom'];
            
            $arr_vals = [];
            $sum_val = 0;
            $count_val = 0;
            
            for ($k=0; $k<$kolom; $k++) {
                $raw = $_POST['val'][$i][$cid][$k] ?? '';
                if ($raw !== '') {
                    $v = (float)$raw;
                    $arr_vals[] = $v;
                    $sum_val += $v;
                    $count_val++;
                } else {
                    $arr_vals[] = null;
                }
            }
            $data_nilai[$cid] = $arr_vals;
            
            $rata = $count_val > 0 ? ($sum_val / $count_val) : 0;
            $na += ($rata * ($bobot / 100));
        }
        
        $na = max(0, min(100, $na));
        $predikat = $na>=90?'A':($na>=80?'B':($na>=70?'C':'D'));
        $json_data = json_encode($data_nilai);

        $check = $pdo->prepare("SELECT id FROM tbl_raport WHERE id_siswa=? AND id_mapel=? AND id_ta=?");
        $check->execute([$id_siswa, $id_mapel, $ta['id_ta']]);
        
        if ($check->fetch()) {
            $pdo->prepare("UPDATE tbl_raport SET data_nilai=?, nilai_akhir=?, predikat=? WHERE id_siswa=? AND id_mapel=? AND id_ta=?")->execute([
                $json_data, $na, $predikat, $id_siswa, $id_mapel, $ta['id_ta']
            ]);
        } else {
            $pdo->prepare("INSERT INTO tbl_raport (id_siswa, id_mapel, id_ta, data_nilai, nilai_akhir, predikat) VALUES (?,?,?,?,?,?)")->execute([
                $id_siswa, $id_mapel, $ta['id_ta'], $json_data, $na, $predikat
            ]);
        }
    }
    flash('msg', 'Seluruh Nilai Berhasil Disimpan (Admin Override)!', 'success');
    header('Location: raport.php?kelas='.$id_kelas.'&mapel='.$id_mapel);
    exit;
}

$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();
$mapel_list = $pdo->query("SELECT * FROM tbl_mapel ORDER BY nama_mapel")->fetchAll();

$sel_kelas = (int)($_GET['kelas'] ?? 0); 
$sel_mapel = (int)($_GET['mapel'] ?? 0);

$siswa_list = [];
$komponen_arr = [];

if ($sel_kelas && $sel_mapel && $ta) { 
    $stmt = $pdo->prepare("SELECT * FROM tbl_siswa WHERE id_kelas=? AND status='Aktif' ORDER BY nama"); 
    $stmt->execute([$sel_kelas]); 
    $siswa_list = $stmt->fetchAll(); 

    // Admin searches globally for configuration, order by ID DESC to get the latest (either Admin or Guru)
    $stmtB = $pdo->prepare("SELECT komponen_json FROM tbl_bobot_nilai WHERE id_mapel=? AND id_kelas=? AND id_ta=? AND komponen_json IS NOT NULL ORDER BY id DESC LIMIT 1");
    $stmtB->execute([$sel_mapel, $sel_kelas, $ta['id_ta']]);
    if ($b = $stmtB->fetch()) {
        $komponen_arr = json_decode($b['komponen_json'], true) ?: [];
    }
}

// Preset Colors for UI
$colors = [
    ['bg'=>'bg-blue-500/20', 'text'=>'text-blue-300', 'bg_sub'=>'bg-blue-500/10', 'bg_tot'=>'bg-blue-600/30', 'text_rata'=>'text-blue-200'],
    ['bg'=>'bg-emerald-500/20', 'text'=>'text-emerald-300', 'bg_sub'=>'bg-emerald-500/10', 'bg_tot'=>'bg-emerald-600/30', 'text_rata'=>'text-emerald-200'],
    ['bg'=>'bg-orange-500/20', 'text'=>'text-orange-300', 'bg_sub'=>'bg-orange-500/10', 'bg_tot'=>'bg-orange-600/30', 'text_rata'=>'text-orange-200'],
    ['bg'=>'bg-rose-500/20', 'text'=>'text-rose-300', 'bg_sub'=>'bg-rose-500/10', 'bg_tot'=>'bg-rose-600/30', 'text_rata'=>'text-rose-200'],
    ['bg'=>'bg-amber-500/20', 'text'=>'text-amber-300', 'bg_sub'=>'bg-amber-500/10', 'bg_tot'=>'bg-amber-600/30', 'text_rata'=>'text-amber-200'],
    ['bg'=>'bg-teal-500/20', 'text'=>'text-teal-300', 'bg_sub'=>'bg-teal-500/10', 'bg_tot'=>'bg-teal-600/30', 'text_rata'=>'text-teal-200'],
];

require_once __DIR__ . '/../../template/header.php'; 
require_once __DIR__ . '/../../template/sidebar.php'; 
require_once __DIR__ . '/../../template/topbar.php';
?>

<?= alert_flash('msg') ?>
<div class="flex flex-wrap items-center justify-between mb-6 gap-4">
    <h2 class="text-xl font-bold text-white"><i class="fas fa-file-excel mr-2 text-emerald-400"></i>Kelola Daftar Nilai Dinamis (Admin)</h2>
</div>

<div class="glass rounded-xl p-5 mb-6 border-l-4 border-indigo-500">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-xs text-slate-400 mb-1 font-bold uppercase tracking-widest">Pilih Kelas</label>
            <select name="kelas" onchange="this.form.submit()" class="bg-slate-800/50 border border-white/10 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 outline-none w-48">
                <option value="">-- Pilih --</option>
                <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k['id_kelas'] ?>" <?= $sel_kelas==$k['id_kelas']?'selected':'' ?>><?= clean($k['nama_kelas']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs text-slate-400 mb-1 font-bold uppercase tracking-widest">Pilih Mata Pelajaran</label>
            <select name="mapel" onchange="this.form.submit()" class="bg-slate-800/50 border border-white/10 rounded-lg px-4 py-2 text-sm focus:border-indigo-500 outline-none w-64">
                <option value="">-- Pilih --</option>
                <?php foreach ($mapel_list as $m): ?>
                    <option value="<?= $m['id_mapel'] ?>" <?= $sel_mapel==$m['id_mapel']?'selected':'' ?>><?= clean($m['nama_mapel']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($sel_kelas && $sel_mapel && $siswa_list): ?>
<!-- 1. KONFIGURASI KOMPONEN -->
<div class="glass rounded-xl p-5 mb-6" id="configSection" style="<?= count($komponen_arr)>0 ? 'display:none;' : '' ?>">
    <div class="flex items-center justify-between mb-4 border-b border-white/10 pb-3">
        <h3 class="font-bold text-emerald-400"><i class="fas fa-cogs mr-2"></i>Atur Komponen Penilaian (Override)</h3>
        <button type="button" onclick="document.getElementById('configSection').style.display='none'" class="text-slate-400 hover:text-white"><i class="fas fa-times"></i> Tutup</button>
    </div>
    
    <form method="POST">
        <input type="hidden" name="id_kelas" value="<?= $sel_kelas ?>">
        <input type="hidden" name="id_mapel" value="<?= $sel_mapel ?>">
        
        <div id="komponenList" class="space-y-3 mb-4">
            <?php if (empty($komponen_arr)): ?>
                <div class="flex gap-2 items-center k-item">
                    <input type="text" name="k_nama[]" value="Tugas Harian" class="flex-1 bg-slate-800 border border-white/10 rounded px-3 py-2 text-sm" required>
                    <input type="number" name="k_kolom[]" value="4" min="1" class="w-20 bg-slate-800 border border-white/10 rounded px-3 py-2 text-sm" required>
                    <div class="flex items-center w-28"><input type="number" name="k_bobot[]" value="20" min="1" class="w-full bg-slate-800 border border-white/10 rounded-l px-3 py-2 text-sm" required><span class="bg-white/10 px-2 py-2 rounded-r text-sm">%</span></div>
                    <button type="button" onclick="this.parentElement.remove()" class="bg-red-500/20 text-red-400 px-3 py-2 rounded"><i class="fas fa-trash"></i></button>
                </div>
            <?php else: 
                foreach ($komponen_arr as $c): ?>
                <div class="flex gap-2 items-center k-item">
                    <input type="text" name="k_nama[]" value="<?= htmlspecialchars($c['nama']) ?>" class="flex-1 bg-slate-800 border border-white/10 rounded px-3 py-2 text-sm text-white" required>
                    <input type="number" name="k_kolom[]" value="<?= $c['kolom'] ?>" min="1" class="w-20 bg-slate-800 border border-white/10 rounded px-3 py-2 text-sm text-white" required>
                    <div class="flex items-center w-28"><input type="number" name="k_bobot[]" value="<?= $c['bobot'] ?>" min="1" max="100" class="w-full bg-slate-800 border border-white/10 rounded-l px-3 py-2 text-sm font-bold text-white" required><span class="bg-white/10 px-2 py-2 rounded-r text-sm">%</span></div>
                    <button type="button" onclick="this.parentElement.remove()" class="bg-red-500/20 text-red-400 px-3 py-2 rounded hover:bg-red-500 hover:text-white"><i class="fas fa-trash"></i></button>
                </div>
            <?php endforeach; endif; ?>
        </div>
        
        <div class="flex justify-between items-center mt-6 pt-4 border-t border-white/10">
            <button type="button" onclick="tambahKomponen()" class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded text-sm"><i class="fas fa-plus mr-1"></i> Tambah Kategori Baru</button>
            <button type="submit" name="simpan_komponen" class="bg-emerald-600 hover:bg-emerald-500 text-white px-6 py-2 rounded-lg font-bold shadow-lg"><i class="fas fa-save mr-2"></i>Simpan Konfigurasi Dinamis</button>
        </div>
    </form>
</div>

<script>
function tambahKomponen() {
    const html = `<div class="flex gap-2 items-center k-item">
        <input type="text" name="k_nama[]" placeholder="Nama Kategori..." class="flex-1 bg-slate-800 border border-white/10 rounded px-3 py-2 text-sm text-white" required>
        <input type="number" name="k_kolom[]" value="1" min="1" class="w-20 bg-slate-800 border border-white/10 rounded px-3 py-2 text-sm text-white" required>
        <div class="flex items-center w-28"><input type="number" name="k_bobot[]" value="10" min="1" max="100" class="w-full bg-slate-800 border border-white/10 rounded-l px-3 py-2 text-sm font-bold text-white" required><span class="bg-white/10 px-2 py-2 rounded-r text-sm">%</span></div>
        <button type="button" onclick="this.parentElement.remove()" class="bg-red-500/20 text-red-400 px-3 py-2 rounded hover:bg-red-500 hover:text-white"><i class="fas fa-trash"></i></button>
    </div>`;
    document.getElementById('komponenList').insertAdjacentHTML('beforeend', html);
}
</script>

<!-- 2. TABEL ENTRY NILAI (RENDER DINAMIS) -->
<?php if (!empty($komponen_arr)): ?>
    <div class="flex flex-wrap justify-between items-center mb-4 gap-3">
        <h3 class="font-bold text-white"><i class="fas fa-list-ol mr-2 text-indigo-400"></i>Form Input Nilai Siswa (Dinamis)</h3>
        <div class="flex gap-2">
            <a href="cetak_nilai.php?kelas=<?= $sel_kelas ?>&mapel=<?= $sel_mapel ?>" target="_blank" class="bg-blue-600 hover:bg-blue-500 border border-white/10 px-4 py-1.5 rounded flex items-center text-xs text-white shadow-lg">
                <i class="fas fa-print mr-2"></i>Cetak Daftar Nilai
            </a>
            <button onclick="document.getElementById('configSection').style.display='block'" class="bg-slate-800 hover:bg-slate-700 border border-white/10 px-3 py-1.5 rounded text-xs text-slate-300"><i class="fas fa-cog mr-1"></i>Edit Kategori Mapel Ini</button>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="id_kelas" value="<?= $sel_kelas ?>">
        <input type="hidden" name="id_mapel" value="<?= $sel_mapel ?>">
        <input type="hidden" name="komponen_json" value="<?= htmlspecialchars(json_encode($komponen_arr)) ?>">

        <div class="glass rounded-xl p-5 mb-20 overflow-hidden">
            <div class="overflow-x-auto pb-4 custom-scrollbar" style="width: 100%; -webkit-overflow-scrolling: touch;">
                <style>
                    .t-input { min-width: 45px; width: 100%; max-width: 60px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 4px; padding: 6px 4px; font-size: 13px; text-align: center; color: white; transition: 0.2s; }
                    .t-input:focus { background: rgba(255,255,255,0.15); border-color: #6366f1; outline: none; }
                    .th-group { text-align: center; border: 1px solid rgba(255,255,255,0.1); padding: 8px 5px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; white-space: nowrap; }
                    .td-cell { border: 1px solid rgba(255,255,255,0.05); padding: 6px; text-align: center; white-space: nowrap; }
                    .t-rata { font-weight: bold; color: #fff; background: rgba(0,0,0,0.2); font-size: 12px; padding: 6px; border-radius: 4px; display: inline-block; min-width: 45px; }
                </style>
                
                <table class="border-collapse" style="width: max-content; min-width: 100%;">
                    <thead>
                        <tr>
                            <th rowspan="2" class="th-group" style="min-width:40px; max-width:40px; width:40px; position:sticky; left:0; z-index:10; background:#1e293b;">No</th>
                            <th rowspan="2" class="th-group" style="min-width:180px; max-width:180px; width:180px; text-align:left; padding-left:10px; position:sticky; left:40px; z-index:10; background:#1e293b;">Nama Siswa</th>
                            
                            <?php foreach ($komponen_arr as $idx => $comp): 
                                $cTheme = $colors[$idx % count($colors)];
                            ?>
                                <th colspan="<?= $comp['kolom'] + 1 ?>" class="th-group <?= $cTheme['bg'] ?> <?= $cTheme['text'] ?>" title="Bobot: <?= $comp['bobot'] ?>%">
                                    <?= htmlspecialchars($comp['nama']) ?> <span class="text-[9px] bg-black/20 px-1 rounded ml-1"><?= $comp['bobot'] ?>%</span>
                                </th>
                            <?php endforeach; ?>
                            
                            <th rowspan="2" class="th-group bg-purple-500/30 text-purple-200">RAPORT<br>(NA)</th>
                        </tr>
                        <tr>
                            <?php foreach ($komponen_arr as $idx => $comp): 
                                $cTheme = $colors[$idx % count($colors)];
                                for ($k=1; $k<=$comp['kolom']; $k++):
                            ?>
                                <th class="th-group <?= $cTheme['bg_sub'] ?>"><?= $k ?></th>
                            <?php endfor; ?>
                                <th class="th-group <?= $cTheme['bg_tot'] ?>" title="Rata-rata <?= htmlspecialchars($comp['nama']) ?>">Rata</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($siswa_list as $i => $s):
                            $stmt_ex = $pdo->prepare("SELECT * FROM tbl_raport WHERE id_siswa=? AND id_mapel=? AND id_ta=?"); 
                            $stmt_ex->execute([$s['id_siswa'], $sel_mapel, $ta['id_ta']]); 
                            $ex = $stmt_ex->fetch();
                            $student_data = [];
                            if ($ex && $ex['data_nilai']) {
                                $student_data = json_decode($ex['data_nilai'], true) ?: [];
                            }
                        ?>
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="td-cell" style="min-width:40px; max-width:40px; width:40px; position:sticky; left:0; z-index:9; background:#0f172a;"><?= $no++ ?></td>
                            <td class="td-cell text-left px-2" style="min-width:180px; max-width:180px; width:180px; position:sticky; left:40px; z-index:9; background:#0f172a;">
                                <input type="hidden" name="id_siswa[<?= $i ?>]" value="<?= $s['id_siswa'] ?>">
                                <div style="width:165px; overflow:hidden; text-overflow:ellipsis; font-weight:600; font-size:12px; white-space:nowrap;" title="<?= clean($s['nama']) ?>"><?= clean($s['nama']) ?></div>
                            </td>
                            
                            <?php foreach ($komponen_arr as $idx => $comp): 
                                $cTheme = $colors[$idx % count($colors)];
                                $cid = $comp['id'];
                                $arr_vals = $student_data[$cid] ?? [];
                                
                                $sum_val = 0; $count_val = 0;
                                for ($k=0; $k<$comp['kolom']; $k++):
                                    $val = $arr_vals[$k] ?? '';
                                    if ($val !== '') { $sum_val += (float)$val; $count_val++; }
                            ?>
                                <td class="td-cell <?= $cTheme['bg_sub'] ?>">
                                    <input type="number" step="0.01" name="val[<?= $i ?>][<?= $cid ?>][<?= $k ?>]" value="<?= $val ?>" class="t-input" max="100">
                                </td>
                            <?php endfor; 
                                $rata = $count_val > 0 ? ($sum_val / $count_val) : 0;
                            ?>
                                <td class="td-cell <?= $cTheme['bg_sub'] ?>"><span class="t-rata <?= $cTheme['text_rata'] ?>"><?= number_format($rata, 1) ?></span></td>
                            <?php endforeach; ?>
                            
                            <!-- FINAL NA -->
                            <td class="td-cell bg-purple-500/20"><span class="t-rata !w-[50px] !bg-purple-600/50 !text-white !font-black !text-sm border !border-purple-400"><?= number_format($ex['nilai_akhir'] ?? 0, 1) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="fixed bottom-0 left-0 lg:ml-64 w-full lg:w-[calc(100%-16rem)] p-4 bg-slate-900 border-t border-white/10 z-40 flex justify-center backdrop-blur-sm bg-opacity-90 shadow-[0_-5px_20px_rgba(0,0,0,0.5)]">
            <button type="submit" name="simpan_massal" class="bg-indigo-600 hover:bg-indigo-500 text-white px-10 py-3 rounded-full font-bold shadow-lg shadow-indigo-500/30 uppercase tracking-widest transition-all transform hover:scale-105">
                <i class="fas fa-save mr-2"></i>Simpan Perubahan Nilai 
            </button>
        </div>
    </form>
<?php else: ?>
    <!-- Belum ada konfigurasi -->
    <div class="glass rounded-xl p-10 flex flex-col items-center justify-center text-center">
        <div class="w-16 h-16 rounded-full bg-slate-800 flex items-center justify-center mb-4 border border-indigo-500/30 shadow-[0_0_15px_rgba(99,102,241,0.5)]">
            <i class="fas fa-sliders-h text-2xl text-indigo-400"></i>
        </div>
        <h3 class="text-white font-bold mb-2">Belum Tersedia Konfigurasi Nilai</h3>
        <p class="text-sm text-slate-400 max-w-sm mb-6">Penilaian kelas ini belum dikonfigurasi oleh Guru maupun Admin. Anda bisa mengaturnya sekarang.</p>
        <button onclick="document.getElementById('configSection').style.display='block'" class="bg-indigo-600 hover:bg-indigo-500 px-6 py-2 rounded shadow-lg text-sm font-bold">Mulai Atur Komponen</button>
    </div>
<?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../../template/footer.php'; ?>
