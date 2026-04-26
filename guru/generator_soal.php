<?php
$page_title = 'Generator Soal AI';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('ai_soal');
$id_guru = $_SESSION['user_id'];

// Data guru
$mapel_stmt = $pdo->prepare("SELECT DISTINCT m.* FROM tbl_jadwal j JOIN tbl_mapel m ON j.id_mapel=m.id_mapel WHERE j.id_guru=? ORDER BY m.nama_mapel");
$mapel_stmt->execute([$id_guru]);
$mapel_list = $mapel_stmt->fetchAll();

$kelas_stmt = $pdo->prepare("SELECT DISTINCT k.* FROM tbl_jadwal j JOIN tbl_kelas k ON j.id_kelas=k.id_kelas WHERE j.id_guru=? ORDER BY k.nama_kelas");
$kelas_stmt->execute([$id_guru]);
$kelas_list = $kelas_stmt->fetchAll();

// Bank soal stats
$stats = $pdo->prepare("SELECT COUNT(*) as total, SUM(tipe_soal='PG') as pg, SUM(tipe_soal='Essay') as essay FROM tbl_bank_soal WHERE id_guru=?");
$stats->execute([$id_guru]);
$st = $stats->fetch();

// Bank soal list
$filter_mapel = $_GET['fm'] ?? '';
$filter_tipe = $_GET['ft'] ?? '';
$where = "WHERE bs.id_guru=?";
$params = [$id_guru];
if ($filter_mapel) { $where .= " AND bs.id_mapel=?"; $params[] = (int)$filter_mapel; }
if ($filter_tipe) { $where .= " AND bs.tipe_soal=?"; $params[] = $filter_tipe; }

$bank_stmt = $pdo->prepare("SELECT bs.*, m.nama_mapel, k.nama_kelas FROM tbl_bank_soal bs LEFT JOIN tbl_mapel m ON bs.id_mapel=m.id_mapel LEFT JOIN tbl_kelas k ON bs.id_kelas=k.id_kelas {$where} ORDER BY bs.id_soal_bank DESC LIMIT 100");
$bank_stmt->execute($params);
$bank_soal = $bank_stmt->fetchAll();

// Paket ujian list
$paket_stmt = $pdo->prepare("SELECT pu.*, m.nama_mapel, k.nama_kelas, (SELECT COUNT(*) FROM tbl_paket_soal WHERE id_paket=pu.id_paket) as jml_soal FROM tbl_paket_ujian pu LEFT JOIN tbl_mapel m ON pu.id_mapel=m.id_mapel LEFT JOIN tbl_kelas k ON pu.id_kelas=k.id_kelas WHERE pu.id_guru=? ORDER BY pu.id_paket DESC");
$paket_stmt->execute([$id_guru]);
$paket_list = $paket_stmt->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="mb-6 flex items-center justify-between flex-wrap gap-4">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-violet-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-violet-500/30">
            <i class="fas fa-wand-magic-sparkles text-white text-xl"></i>
        </div>
        <div>
            <h2 class="text-xl font-black italic uppercase tracking-widest">Generator Soal AI ✦</h2>
            <p class="text-xs text-slate-400">Buat soal ujian otomatis dengan AI, kelola bank soal, dan cetak profesional.</p>
        </div>
    </div>
    <a href="generator_soal_buat.php" class="flex items-center gap-2 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white px-6 py-3 rounded-xl text-sm font-bold shadow-lg shadow-violet-500/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
        <i class="fas fa-wand-magic-sparkles"></i> Generate Soal Baru
    </a>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="stat-card glass rounded-xl p-5">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-violet-600 to-purple-500 flex items-center justify-center mb-3"><i class="fas fa-database text-white"></i></div>
        <p class="text-2xl font-bold"><?= $st['total'] ?? 0 ?></p><p class="text-xs text-slate-400 mt-1">Total Bank Soal</p>
    </div>
    <div class="stat-card glass rounded-xl p-5">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-600 to-cyan-500 flex items-center justify-center mb-3"><i class="fas fa-list-ol text-white"></i></div>
        <p class="text-2xl font-bold"><?= $st['pg'] ?? 0 ?></p><p class="text-xs text-slate-400 mt-1">Soal PG</p>
    </div>
    <div class="stat-card glass rounded-xl p-5">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-600 to-teal-500 flex items-center justify-center mb-3"><i class="fas fa-pen-fancy text-white"></i></div>
        <p class="text-2xl font-bold"><?= $st['essay'] ?? 0 ?></p><p class="text-xs text-slate-400 mt-1">Soal Essay</p>
    </div>
    <div class="stat-card glass rounded-xl p-5">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center mb-3"><i class="fas fa-file-alt text-white"></i></div>
        <p class="text-2xl font-bold"><?= count($paket_list) ?></p><p class="text-xs text-slate-400 mt-1">Paket Ujian</p>
    </div>
</div>

<!-- Tabs -->
<div x-data="{ tab: 'bank' }" class="space-y-4">
    <div class="flex gap-2 bg-black/20 p-1 rounded-xl w-fit">
        <button @click="tab='bank'" :class="tab==='bank' ? 'bg-violet-600 text-white' : 'text-slate-400 hover:text-white'" class="px-5 py-2.5 rounded-lg text-xs font-bold uppercase tracking-widest transition-all"><i class="fas fa-database mr-2"></i>Bank Soal</button>
        <button @click="tab='paket'" :class="tab==='paket' ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'" class="px-5 py-2.5 rounded-lg text-xs font-bold uppercase tracking-widest transition-all"><i class="fas fa-file-alt mr-2"></i>Paket Ujian</button>
    </div>

    <!-- TAB: BANK SOAL -->
    <div x-show="tab==='bank'" x-cloak>
        <!-- Filters -->
        <div class="glass rounded-xl p-4 mb-4">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Mapel</label>
                    <select name="fm" class="bg-slate-900 border border-white/10 rounded-lg px-3 py-2 text-sm">
                        <option value="">Semua Mapel</option>
                        <?php foreach ($mapel_list as $m): ?>
                        <option value="<?= $m['id_mapel'] ?>" <?= $filter_mapel == $m['id_mapel'] ? 'selected' : '' ?>><?= clean($m['nama_mapel']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Tipe</label>
                    <select name="ft" class="bg-slate-900 border border-white/10 rounded-lg px-3 py-2 text-sm">
                        <option value="">Semua Tipe</option>
                        <option value="PG" <?= $filter_tipe==='PG' ? 'selected' : '' ?>>PG</option>
                        <option value="Essay" <?= $filter_tipe==='Essay' ? 'selected' : '' ?>>Essay</option>
                    </select>
                </div>
                <button type="submit" class="bg-violet-600 hover:bg-violet-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-filter mr-1"></i>Filter</button>
                <?php if ($filter_mapel || $filter_tipe): ?>
                <a href="generator_soal.php" class="text-xs text-slate-400 hover:text-red-400 py-2"><i class="fas fa-times mr-1"></i>Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($bank_soal)): ?>
        <div class="glass rounded-xl p-10 text-center">
            <i class="fas fa-wand-magic-sparkles text-4xl text-violet-400 mb-4"></i>
            <p class="text-slate-400 mb-4">Belum ada soal di Bank Soal.</p>
            <a href="generator_soal_buat.php" class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-500 px-5 py-2.5 rounded-lg text-sm font-medium"><i class="fas fa-plus"></i>Generate Soal Pertama</a>
        </div>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($bank_soal as $i => $s): ?>
            <div class="glass rounded-xl p-4 hover:bg-white/5 transition-all group" id="soal-bank-<?= $s['id_soal_bank'] ?>">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center text-sm font-bold <?= $s['tipe_soal']==='PG' ? 'bg-blue-500/20 text-blue-400' : 'bg-emerald-500/20 text-emerald-400' ?>">
                        <?= $s['tipe_soal'] ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium leading-relaxed"><?= clean(mb_substr($s['pertanyaan'], 0, 200)) ?><?= mb_strlen($s['pertanyaan']) > 200 ? '...' : '' ?></p>
                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-white/5 text-slate-400"><?= clean($s['nama_mapel'] ?? '-') ?></span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full <?= $s['tingkat']==='Mudah' ? 'bg-emerald-500/10 text-emerald-400' : ($s['tingkat']==='Sulit' ? 'bg-red-500/10 text-red-400' : 'bg-amber-500/10 text-amber-400') ?>"><?= $s['tingkat'] ?></span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-violet-500/10 text-violet-400"><?= $s['taksonomi'] ?></span>
                            <?php if ($s['tipe_soal'] === 'PG'): ?>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400">Jawaban: <?= $s['jawaban'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button onclick="hapusSoalBank(<?= $s['id_soal_bank'] ?>)" class="text-slate-500 hover:text-red-400 opacity-0 group-hover:opacity-100 transition-all"><i class="fas fa-trash text-xs"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- TAB: PAKET UJIAN -->
    <div x-show="tab==='paket'" x-cloak>
        <?php if (empty($paket_list)): ?>
        <div class="glass rounded-xl p-10 text-center">
            <i class="fas fa-file-alt text-4xl text-indigo-400 mb-4"></i>
            <p class="text-slate-400 mb-4">Belum ada paket ujian. Generate soal dulu, lalu buat paket.</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($paket_list as $p): ?>
            <div class="glass rounded-xl p-5 hover:bg-white/5 transition-all">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h4 class="font-bold text-sm"><?= clean($p['nama_ujian']) ?></h4>
                        <p class="text-xs text-slate-400 mt-1"><?= clean($p['nama_mapel'] ?? '-') ?> · <?= clean($p['nama_kelas'] ?? '-') ?></p>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 rounded-full <?= $p['status']==='Final' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400' ?>"><?= $p['status'] ?></span>
                </div>
                <div class="flex flex-wrap gap-2 text-[10px] mb-3">
                    <span class="px-2 py-0.5 rounded-full bg-white/5 text-slate-400"><i class="fas fa-list mr-1"></i><?= $p['jml_soal'] ?> soal</span>
                    <span class="px-2 py-0.5 rounded-full bg-white/5 text-slate-400"><i class="fas fa-clock mr-1"></i><?= $p['durasi_menit'] ?> menit</span>
                    <span class="px-2 py-0.5 rounded-full bg-white/5 text-slate-400"><?= $p['tipe_ujian'] ?></span>
                    <span class="px-2 py-0.5 rounded-full bg-white/5 text-slate-400"><?= $p['jenis_ujian'] ?></span>
                </div>
                <div class="flex items-center gap-2 pt-3 border-t border-white/5">
                    <a href="paket_ujian_detail.php?id=<?= $p['id_paket'] ?>" class="flex-1 text-center text-xs py-2 rounded-lg bg-indigo-600/20 text-indigo-400 hover:bg-indigo-600 hover:text-white transition-all font-medium"><i class="fas fa-eye mr-1"></i>Detail & Cetak</a>
                    <button onclick="hapusPaket(<?= $p['id_paket'] ?>)" class="px-3 py-2 rounded-lg text-xs text-red-400 hover:bg-red-500/10 transition-all"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function hapusSoalBank(id) {
    Swal.fire({
        title: 'Hapus Soal?', text: 'Soal ini akan dihapus dari Bank Soal.', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonText: 'Batal', confirmButtonText: 'Hapus'
    }).then(r => {
        if (r.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'hapus_soal');
            fd.append('id', id);
            fetch('<?= BASE_URL ?>api/soal_ai.php', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => {
                if (d.status === 'success') {
                    document.getElementById('soal-bank-' + id)?.remove();
                    Swal.fire('Dihapus!', d.message, 'success');
                } else Swal.fire('Gagal', d.message, 'error');
            });
        }
    });
}

function hapusPaket(id) {
    Swal.fire({
        title: 'Hapus Paket?', text: 'Paket ujian dan semua soalnya akan dihapus.', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonText: 'Batal', confirmButtonText: 'Hapus'
    }).then(r => {
        if (r.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'hapus_paket');
            fd.append('id', id);
            fetch('<?= BASE_URL ?>api/soal_ai.php', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => {
                if (d.status === 'success') location.reload();
                else Swal.fire('Gagal', d.message, 'error');
            });
        }
    });
}
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
