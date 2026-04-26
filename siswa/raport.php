<?php
$page_title = 'Detail Daftar Nilai';
require_once __DIR__ . '/../config/init.php';
cek_role(['siswa']);
cek_fitur('akademik');

$id = $_SESSION['user_id']; 
$ta = get_ta_aktif($pdo);

// Get student's class
$stmtS = $pdo->prepare("SELECT id_kelas FROM tbl_siswa WHERE id_siswa=?");
$stmtS->execute([$id]);
$my_kelas = $stmtS->fetchColumn();

// Fetch Raport & Mapel
$raport = [];
if ($ta) { 
    $stmt = $pdo->prepare("SELECT r.*,m.nama_mapel FROM tbl_raport r JOIN tbl_mapel m ON r.id_mapel=m.id_mapel WHERE r.id_siswa=? AND r.id_ta=? ORDER BY m.nama_mapel"); 
    $stmt->execute([$id, $ta['id_ta']]); 
    $raport = $stmt->fetchAll(); 
}

// Preset Colors for UI
$colors = [
    ['bg'=>'bg-blue-500/20', 'text'=>'text-blue-300', 'bg_sub'=>'bg-blue-500/10', 'bg_tot'=>'bg-blue-600/30', 'text_rata'=>'text-blue-200'],
    ['bg'=>'bg-emerald-500/20', 'text'=>'text-emerald-300', 'bg_sub'=>'bg-emerald-500/10', 'bg_tot'=>'bg-emerald-600/30', 'text_rata'=>'text-emerald-200'],
    ['bg'=>'bg-orange-500/20', 'text'=>'text-orange-300', 'bg_sub'=>'bg-orange-500/10', 'bg_tot'=>'bg-orange-600/30', 'text_rata'=>'text-orange-200'],
    ['bg'=>'bg-rose-500/20', 'text'=>'text-rose-300', 'bg_sub'=>'bg-rose-500/10', 'bg_tot'=>'bg-rose-600/30', 'text_rata'=>'text-rose-200'],
];

require_once __DIR__ . '/../template/header.php'; 
require_once __DIR__ . '/../template/sidebar.php'; 
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex flex-wrap items-center justify-between mb-6 gap-4">
    <h2 class="text-xl font-bold text-white"><i class="fas fa-file-excel mr-2 text-emerald-400"></i>Detail Nilai Berdasarkan Kategori</h2>
</div>

<?php if ($raport): ?>
<div class="glass rounded-xl p-5 mb-4 border-l-4 border-emerald-500">
    <p class="text-sm text-slate-400">Tahun Ajaran: <span class="text-white font-bold bg-emerald-500/20 px-3 py-1 rounded"><?= $ta['tahun'] ?></span> | Setiap Mata Pelajaran memiliki rancangan komponen Ujian/Tugas yang berbeda-beda dari Guru Anda.</p>
</div>

<div class="space-y-6">
    <?php 
    $total_akhir = 0;
    foreach ($raport as $r): 
        $total_akhir += $r['nilai_akhir'];
        $id_mapel = $r['id_mapel'];
        
        // Cari Skema Master Mapel ini untuk Kelas ini
        $stmtB = $pdo->prepare("SELECT komponen_json FROM tbl_bobot_nilai WHERE id_mapel=? AND id_kelas=? AND id_ta=? AND komponen_json IS NOT NULL ORDER BY id DESC LIMIT 1");
        $stmtB->execute([$id_mapel, $my_kelas, $ta['id_ta']]);
        $schema = json_decode($stmtB->fetchColumn(), true) ?: [];
        
        $data_nilai = json_decode($r['data_nilai'], true) ?: [];
    ?>
    <div class="glass rounded-xl overflow-hidden shadow-lg border border-white/5">
        <div class="bg-slate-800/80 px-5 py-3 flex justify-between items-center border-b border-white/10">
            <h3 class="font-bold text-white text-base"><i class="fas fa-book mr-2 text-blue-400"></i><?= clean($r['nama_mapel']) ?></h3>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <span class="text-[10px] text-slate-400 uppercase tracking-widest block">Predikat</span>
                    <span class="font-bold <?= $r['predikat']=='A'?'text-emerald-400':($r['predikat']=='B'?'text-blue-400':'text-rose-400') ?>"><?= $r['predikat'] ?></span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] text-slate-400 uppercase tracking-widest block">Nilai Raport (NA)</span>
                    <span class="text-lg font-black bg-purple-500/20 px-3 py-1 rounded text-purple-300"><?= number_format($r['nilai_akhir'], 1) ?></span>
                </div>
            </div>
        </div>
        
        <?php if (!empty($schema)): ?>
        <div class="p-5 overflow-x-auto custom-scrollbar">
            <table class="w-[max-content] min-w-full border-collapse">
                <thead>
                    <tr>
                        <?php foreach ($schema as $idx => $comp): 
                            $cTheme = $colors[$idx % count($colors)];
                        ?>
                            <th colspan="<?= $comp['kolom'] + 1 ?>" class="border border-white/5 px-4 py-2 text-[10px] uppercase tracking-widest font-bold <?= $cTheme['bg'] ?> <?= $cTheme['text'] ?>">
                                <?= htmlspecialchars($comp['nama']) ?> 
                                <span class="bg-black/30 px-1.5 py-0.5 rounded ml-2 text-[9px]"><?= $comp['bobot'] ?>%</span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($schema as $idx => $comp): 
                            $cTheme = $colors[$idx % count($colors)];
                            for ($k=1; $k<=$comp['kolom']; $k++):
                        ?>
                            <th class="border border-white/5 px-2 py-1.5 text-[10px] <?= $cTheme['bg_sub'] ?> text-slate-300 w-12 text-center"><?= $k ?></th>
                        <?php endfor; ?>
                            <th class="border border-white/5 px-3 py-1.5 text-[10px] <?= $cTheme['bg_tot'] ?> text-white w-14 text-center">RATA</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php foreach ($schema as $idx => $comp): 
                            $cTheme = $colors[$idx % count($colors)];
                            $cid = $comp['id'];
                            $arr_vals = $data_nilai[$cid] ?? [];
                            $sum_val = 0; $count_val = 0;
                            
                            for ($k=0; $k<$comp['kolom']; $k++):
                                $val = $arr_vals[$k] ?? '';
                                if ($val !== '') { $sum_val+=(float)$val; $count_val++; }
                        ?>
                            <td class="border border-white/5 px-2 py-2 text-center text-xs text-slate-300 bg-white/5"><?= $val==='' ? '-' : $val ?></td>
                        <?php endfor; 
                            $rata = $count_val > 0 ? ($sum_val / $count_val) : 0;
                        ?>
                            <td class="border border-white/5 px-3 py-2 text-center text-xs font-bold <?= $cTheme['text_rata'] ?> bg-black/20"><?= number_format($rata, 1) ?></td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="p-5 text-center text-slate-400 text-sm">
            Guru Mata Pelajaran ini belum melakukan konfigurasi rincian nilai komponen. Hanya Nilai Akhir yang tersedia saat ini.
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    
    <div class="glass rounded-xl p-5 mb-10 flex justify-between items-center bg-purple-900/20 border-purple-500/30">
        <span class="text-slate-300 font-medium">Berdasarkan Total Rata-Rata Seluruh Mata Pelajaran:</span>
        <span class="font-black text-2xl text-purple-400 drop-shadow-[0_0_10px_rgba(168,85,247,0.5)]">
            <?= count($raport) ? number_format($total_akhir/count($raport), 1) : '0.0' ?>
        </span>
    </div>
</div>
<?php else: ?>
    <div class="glass rounded-xl p-10 flex flex-col items-center justify-center text-center">
        <div class="w-16 h-16 rounded-full bg-slate-800 flex items-center justify-center mb-4 border border-white/10 shadow-lg">
            <i class="fas fa-folder-open text-2xl text-slate-500"></i>
        </div>
        <h3 class="text-white font-bold mb-2">Belum Ada Daftar Nilai</h3>
        <p class="text-sm text-slate-400 max-w-sm">Guru belum men-submit nilai Anda pada Tahun Ajaran saat ini.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
