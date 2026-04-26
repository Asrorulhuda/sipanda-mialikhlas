<?php
$page_title = 'Prestasi Saya';
require_once __DIR__ . '/../config/init.php';
cek_role(['siswa']);
cek_fitur('kesiswaan');

$id_siswa = $_SESSION['user_id'];

// Get prestasi for this student
$stmt = $pdo->prepare("SELECT * FROM tbl_prestasi WHERE id_siswa=? ORDER BY tanggal DESC");
$stmt->execute([$id_siswa]);
$data = $stmt->fetchAll();

// Stats for this student
$total = count($data);
$akademik = count(array_filter($data, fn($x) => $x['jenis'] == 'Akademik'));
$non_akademik = count(array_filter($data, fn($x) => $x['jenis'] == 'Non-Akademik'));
$nasional = count(array_filter($data, fn($x) => in_array($x['tingkat'], ['Nasional','Internasional'])));

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="mb-6">
    <h2 class="text-xl font-bold bg-gradient-to-r from-amber-400 to-orange-400 bg-clip-text text-transparent mb-2">Prestasi & Pencapaian</h2>
    <p class="text-sm text-slate-400">Rekam jejak kebanggaan selama belajar di <?= APP_NAME ?>.</p>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="glass rounded-xl p-4 flex items-center gap-4 hover:border-amber-500/50 transition-colors border border-white/5">
        <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-400 text-lg"><i class="fas fa-trophy"></i></div>
        <div><p class="text-xs text-slate-400">Total Prestasi</p><p class="text-xl font-bold text-white"><?= $total ?></p></div>
    </div>
    <div class="glass rounded-xl p-4 flex items-center gap-4 hover:border-blue-500/50 transition-colors border border-white/5">
        <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center text-blue-400 text-lg"><i class="fas fa-book-reader"></i></div>
        <div><p class="text-xs text-slate-400">Akademik</p><p class="text-xl font-bold text-white"><?= $akademik ?></p></div>
    </div>
    <div class="glass rounded-xl p-4 flex items-center gap-4 hover:border-purple-500/50 transition-colors border border-white/5">
        <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center text-purple-400 text-lg"><i class="fas fa-running"></i></div>
        <div><p class="text-xs text-slate-400">Non-Akademik</p><p class="text-xl font-bold text-white"><?= $non_akademik ?></p></div>
    </div>
    <div class="glass rounded-xl p-4 flex items-center gap-4 hover:border-emerald-500/50 transition-colors border border-white/5">
        <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-lg"><i class="fas fa-medal"></i></div>
        <div><p class="text-xs text-slate-400">Nasional+</p><p class="text-xl font-bold text-white"><?= $nasional ?></p></div>
    </div>
</div>

<div class="glass rounded-xl p-5 border border-white/5">
    <h3 class="font-bold text-white mb-4 flex items-center gap-2"><i class="fas fa-award text-amber-500"></i> Riwayat Prestasi</h3>
    
    <div class="relative wrap overflow-hidden p-2 h-full">
        <?php if ($data): ?>
            <div class="border-2-2 absolute border-opacity-20 border-white h-full border ml-4 hidden md:block"></div>
            <?php foreach ($data as $i => $r): 
                // Badge color based on jenis
                $jenis_color = 'bg-slate-500/20 text-slate-400';
                if ($r['jenis'] == 'Akademik') $jenis_color = 'bg-blue-500/20 text-blue-400 border-blue-500/30';
                elseif ($r['jenis'] == 'Non-Akademik') $jenis_color = 'bg-purple-500/20 text-purple-400 border-purple-500/30';
                elseif ($r['jenis'] == 'Olahraga') $jenis_color = 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30';
                elseif ($r['jenis'] == 'Seni') $jenis_color = 'bg-pink-500/20 text-pink-400 border-pink-500/30';
                
                // Badge color based on tingkat
                $tk_color = 'bg-white/10 text-slate-300';
                if (in_array(strtolower($r['tingkat']), ['nasional', 'internasional'])) $tk_color = 'bg-amber-500/20 text-amber-400 border-amber-500/30 font-semibold shadow-[0_0_10px_rgba(245,158,11,0.2)]';
                elseif (in_array(strtolower($r['tingkat']), ['provinsi'])) $tk_color = 'bg-slate-300/20 text-white font-semibold';
            ?>
            
            <div class="mb-6 flex justify-between items-center w-full relative">
                <div class="hidden md:block w-3 h-3 bg-amber-500 rounded-full z-10 absolute -left-1.5 top-5 shadow-[0_0_10px_rgba(245,158,11,0.6)]"></div>
                
                <div class="border border-white/10 bg-slate-800/40 rounded-xl px-5 py-4 w-full md:ml-10 shadow-lg hover:bg-slate-800/60 hover:border-white/20 transition-all">
                    <div class="flex flex-wrap items-center justify-between mb-2">
                        <span class="text-xs font-mono text-slate-400 bg-black/30 px-2 py-1 rounded"><i class="far fa-calendar-alt mr-1"></i><?= date('d M Y', strtotime($r['tanggal'])) ?></span>
                        <div class="flex gap-2">
                            <span class="text-[10px] px-2 py-0.5 rounded-full border <?= $tk_color ?> uppercase tracking-wider"><?= clean($r['tingkat']) ?></span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full border <?= $jenis_color ?> uppercase tracking-wider"><?= clean($r['jenis']) ?></span>
                        </div>
                    </div>
                    <h4 class="font-bold text-lg text-white mb-1"><?= clean($r['nama_prestasi']) ?></h4>
                    <?php if ($r['keterangan']): ?>
                        <p class="text-sm text-slate-400 bg-white/5 p-3 rounded-lg mt-2 italic">“<?= clean($r['keterangan']) ?>”</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-10">
                <i class="fas fa-folder-open text-4xl text-slate-600 mb-3"></i>
                <p class="text-slate-400">Belum ada catatan prestasi.</p>
                <p class="text-xs text-slate-500 mt-1">Ayo semangat tingkatkan prestasimu!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
