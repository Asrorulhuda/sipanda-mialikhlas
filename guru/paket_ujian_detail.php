<?php
$page_title = 'Detail Paket Ujian';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
$id_guru = $_SESSION['user_id'];
$id_paket = (int)($_GET['id'] ?? 0);

// Get paket data
$paket_stmt = $pdo->prepare("SELECT pu.*, m.nama_mapel, k.nama_kelas FROM tbl_paket_ujian pu LEFT JOIN tbl_mapel m ON pu.id_mapel=m.id_mapel LEFT JOIN tbl_kelas k ON pu.id_kelas=k.id_kelas WHERE pu.id_paket=? AND pu.id_guru=?");
$paket_stmt->execute([$id_paket, $id_guru]);
$paket = $paket_stmt->fetch();

if (!$paket) {
    flash('msg', 'Paket ujian tidak ditemukan!', 'error');
    header('Location: generator_soal.php');
    exit;
}

// Get soal in paket
$soal_stmt = $pdo->prepare("SELECT bs.*, ps.nomor_urut, ps.bobot FROM tbl_paket_soal ps JOIN tbl_bank_soal bs ON ps.id_soal_bank=bs.id_soal_bank WHERE ps.id_paket=? ORDER BY ps.nomor_urut");
$soal_stmt->execute([$id_paket]);
$soal_list = $soal_stmt->fetchAll();

// Separate PG and Essay
$soal_pg = array_filter($soal_list, fn($s) => $s['tipe_soal'] === 'PG');
$soal_essay = array_filter($soal_list, fn($s) => $s['tipe_soal'] === 'Essay');

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="mb-6 flex items-center gap-4">
    <a href="generator_soal.php" class="w-10 h-10 rounded-xl glass flex items-center justify-center text-slate-400 hover:text-white transition-all"><i class="fas fa-arrow-left"></i></a>
    <div class="flex-1">
        <h2 class="text-xl font-black italic uppercase tracking-widest"><?= clean($paket['nama_ujian']) ?> ✦</h2>
        <p class="text-xs text-slate-400"><?= clean($paket['nama_mapel'] ?? '-') ?> · <?= clean($paket['nama_kelas'] ?? '-') ?> · <?= $paket['tipe_ujian'] ?> · <?= $paket['jenis_ujian'] ?></p>
    </div>
</div>

<!-- Paket Info -->
<div class="glass rounded-xl p-5 mb-6">
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-4 text-center">
        <div><p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Tipe</p><p class="text-sm font-bold mt-1"><?= $paket['tipe_ujian'] ?></p></div>
        <div><p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Jenis</p><p class="text-sm font-bold mt-1"><?= $paket['jenis_ujian'] ?></p></div>
        <div><p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Durasi</p><p class="text-sm font-bold mt-1"><?= $paket['durasi_menit'] ?> menit</p></div>
        <div><p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Soal PG</p><p class="text-sm font-bold mt-1 text-blue-400"><?= count($soal_pg) ?></p></div>
        <div><p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Soal Essay</p><p class="text-sm font-bold mt-1 text-emerald-400"><?= count($soal_essay) ?></p></div>
        <div><p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Opsi PG</p><p class="text-sm font-bold mt-1"><?= $paket['jumlah_opsi_pg'] ?> opsi</p></div>
    </div>
</div>

<!-- Action Buttons: Cetak -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
    <a href="cetak_soal.php?id=<?= $id_paket ?>" target="_blank" class="glass rounded-xl p-4 text-center hover:bg-violet-600/20 transition-all group border border-violet-500/20">
        <i class="fas fa-file-alt text-2xl text-violet-400 mb-2 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-bold uppercase tracking-widest">Cetak Naskah Soal</p>
        <p class="text-[10px] text-slate-500 mt-1">Dengan kop surat sekolah</p>
    </a>
    <a href="cetak_kunci.php?id=<?= $id_paket ?>" target="_blank" class="glass rounded-xl p-4 text-center hover:bg-emerald-600/20 transition-all group border border-emerald-500/20">
        <i class="fas fa-key text-2xl text-emerald-400 mb-2 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-bold uppercase tracking-widest">Cetak Kunci Jawaban</p>
        <p class="text-[10px] text-slate-500 mt-1">Jawaban + pembahasan</p>
    </a>
    <a href="cetak_kisi.php?id=<?= $id_paket ?>" target="_blank" class="glass rounded-xl p-4 text-center hover:bg-amber-600/20 transition-all group border border-amber-500/20">
        <i class="fas fa-th-list text-2xl text-amber-400 mb-2 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-bold uppercase tracking-widest">Cetak Kisi-Kisi</p>
        <p class="text-[10px] text-slate-500 mt-1">Indikator & taksonomi</p>
    </a>
</div>

<!-- Push to Quiz Section -->
<div class="glass rounded-2xl p-6 border border-indigo-500/20 mb-6" x-data="pushQuiz()">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold uppercase tracking-widest text-indigo-400 flex items-center gap-2">
            <i class="fas fa-share-square"></i> Push ke Quiz Online
        </h3>
        <div class="flex items-center gap-3">
            <button @click="selectAll()" class="text-[10px] px-3 py-1.5 rounded-lg border border-white/10 text-slate-400 hover:text-white hover:bg-white/5 transition-all">
                <i class="fas fa-check-double mr-1"></i>Select All
            </button>
            <button @click="deselectAll()" class="text-[10px] px-3 py-1.5 rounded-lg border border-white/10 text-slate-400 hover:text-white hover:bg-white/5 transition-all">
                <i class="fas fa-times mr-1"></i>Deselect
            </button>
            <span class="text-[10px] text-slate-400 bg-white/5 px-3 py-1.5 rounded-lg" x-text="selectedCount() + ' soal dipilih'"></span>
        </div>
    </div>

    <div class="space-y-2 max-h-[400px] overflow-y-auto pr-2 mb-4">
        <?php foreach ($soal_list as $i => $s): if ($s['tipe_soal'] !== 'PG') continue; ?>
        <label class="flex items-center gap-3 p-3 rounded-lg bg-black/20 border border-white/5 hover:bg-white/5 cursor-pointer transition-all">
            <input type="checkbox" value="<?= $s['id_soal_bank'] ?>" x-model="selected" class="w-4 h-4 rounded border-white/20 bg-slate-800 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-0">
            <span class="flex-shrink-0 w-6 h-6 rounded bg-blue-500/20 text-blue-400 flex items-center justify-center text-[10px] font-bold"><?= $i + 1 ?></span>
            <span class="text-xs text-slate-300 flex-1"><?= clean(mb_substr($s['pertanyaan'], 0, 120)) ?><?= mb_strlen($s['pertanyaan']) > 120 ? '...' : '' ?></span>
            <span class="text-[9px] px-2 py-0.5 rounded-full <?= $s['tingkat']==='Mudah' ? 'bg-emerald-500/10 text-emerald-400' : ($s['tingkat']==='Sulit' ? 'bg-red-500/10 text-red-400' : 'bg-amber-500/10 text-amber-400') ?>"><?= $s['tingkat'] ?></span>
        </label>
        <?php endforeach; ?>
    </div>

    <button @click="pushToQuiz()" :disabled="selectedCount() === 0 || pushing" class="w-full bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white py-3.5 rounded-xl text-sm font-black italic uppercase tracking-widest shadow-lg shadow-indigo-500/20 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
        <i class="fas fa-share-square mr-2"></i> Push <span x-text="selectedCount()"></span> Soal PG ke Quiz Online
    </button>
    <p class="text-[10px] text-slate-500 text-center mt-2">Hanya soal PG yang bisa dipush ke Quiz Online. Soal Essay hanya di naskah cetak.</p>
</div>

<!-- Daftar Soal Detail -->
<?php if (!empty($soal_pg)): ?>
<div class="glass rounded-2xl p-6 border border-white/5 mb-6">
    <h3 class="text-sm font-bold uppercase tracking-widest text-blue-400 mb-4 flex items-center gap-2"><i class="fas fa-list-ol"></i> Soal Pilihan Ganda (<?= count($soal_pg) ?> soal)</h3>
    <div class="space-y-4">
        <?php $no = 1; foreach ($soal_pg as $s): ?>
        <div class="bg-black/20 rounded-xl p-4 border border-white/5">
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center text-sm font-bold"><?= $no++ ?></span>
                <div class="flex-1">
                    <p class="text-sm font-medium mb-3"><?= clean($s['pertanyaan']) ?></p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2">
                        <?php
                        $opts = ['A' => $s['opsi_a'], 'B' => $s['opsi_b'], 'C' => $s['opsi_c'], 'D' => $s['opsi_d'], 'E' => $s['opsi_e']];
                        foreach ($opts as $k => $v):
                            if (!$v) continue;
                        ?>
                        <div class="text-xs px-3 py-2 rounded-lg <?= $s['jawaban'] === $k ? 'bg-emerald-500/20 text-emerald-400 font-semibold' : 'bg-white/5 text-slate-300' ?>">
                            <span class="font-bold mr-1"><?= $k ?>.</span> <?= clean($v) ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="text-[9px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400">Jawaban: <?= $s['jawaban'] ?></span>
                        <span class="text-[9px] px-2 py-0.5 rounded-full <?= $s['tingkat']==='Mudah' ? 'bg-emerald-500/10 text-emerald-400' : ($s['tingkat']==='Sulit' ? 'bg-red-500/10 text-red-400' : 'bg-amber-500/10 text-amber-400') ?>"><?= $s['tingkat'] ?></span>
                        <span class="text-[9px] px-2 py-0.5 rounded-full bg-violet-500/10 text-violet-400"><?= $s['taksonomi'] ?></span>
                    </div>
                    <?php if ($s['pembahasan']): ?>
                    <p class="text-[11px] text-slate-500 mt-2 italic"><i class="fas fa-lightbulb mr-1 text-amber-500"></i><?= clean($s['pembahasan']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($soal_essay)): ?>
<div class="glass rounded-2xl p-6 border border-white/5 mb-6">
    <h3 class="text-sm font-bold uppercase tracking-widest text-emerald-400 mb-4 flex items-center gap-2"><i class="fas fa-pen-fancy"></i> Soal Essay (<?= count($soal_essay) ?> soal)</h3>
    <div class="space-y-4">
        <?php $no = 1; foreach ($soal_essay as $s): ?>
        <div class="bg-black/20 rounded-xl p-4 border border-white/5">
            <div class="flex items-start gap-3">
                <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-sm font-bold"><?= $no++ ?></span>
                <div class="flex-1">
                    <p class="text-sm font-medium mb-2"><?= clean($s['pertanyaan']) ?></p>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <span class="text-[9px] px-2 py-0.5 rounded-full <?= $s['tingkat']==='Mudah' ? 'bg-emerald-500/10 text-emerald-400' : ($s['tingkat']==='Sulit' ? 'bg-red-500/10 text-red-400' : 'bg-amber-500/10 text-amber-400') ?>"><?= $s['tingkat'] ?></span>
                        <span class="text-[9px] px-2 py-0.5 rounded-full bg-violet-500/10 text-violet-400"><?= $s['taksonomi'] ?></span>
                    </div>
                    <details class="text-xs text-slate-400"><summary class="cursor-pointer text-amber-400"><i class="fas fa-key mr-1"></i>Kunci & Pembahasan</summary>
                        <div class="mt-2 pl-4 border-l-2 border-amber-500/30 space-y-1">
                            <p><b class="text-emerald-400">Jawaban:</b> <?= clean($s['jawaban']) ?></p>
                            <?php if ($s['pembahasan']): ?><p><b class="text-amber-400">Pembahasan:</b> <?= clean($s['pembahasan']) ?></p><?php endif; ?>
                        </div>
                    </details>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pushQuiz', () => ({
        selected: [],
        pushing: false,
        selectAll() {
            this.selected = [...document.querySelectorAll('input[type=checkbox][value]')].map(el => el.value);
        },
        deselectAll() { this.selected = []; },
        selectedCount() { return this.selected.length; },
        async pushToQuiz() {
            const confirm = await Swal.fire({
                title: 'Push ke Quiz Online?',
                html: `<b>${this.selected.length}</b> soal PG akan ditambahkan sebagai Quiz Online baru.`,
                icon: 'question', showCancelButton: true,
                confirmButtonColor: '#6366f1', cancelButtonText: 'Batal', confirmButtonText: 'Ya, Push!'
            });
            if (!confirm.isConfirmed) return;

            this.pushing = true;
            const fd = new FormData();
            fd.append('action', 'push_quiz');
            fd.append('id_paket', '<?= $id_paket ?>');
            fd.append('soal_ids', JSON.stringify(this.selected.map(Number)));

            try {
                const res = await fetch('<?= BASE_URL ?>api/soal_ai.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status !== 'success') throw new Error(data.message);
                Swal.fire({ title: 'Berhasil!', text: data.message, icon: 'success', confirmButtonColor: '#6366f1' });
            } catch (e) {
                Swal.fire({ title: 'Gagal', text: e.message, icon: 'error' });
            }
            this.pushing = false;
        }
    }));
});
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
