<?php
$page_title = 'Dashboard Guru';
require_once __DIR__ . '/config/init.php';
cek_role(['guru']);
$id_guru = (int)$_SESSION['user_id'];
$today = date('Y-m-d');

// Guru data
$guru_stmt = $pdo->prepare("SELECT * FROM tbl_guru WHERE id_guru=?");
$guru_stmt->execute([$id_guru]);
$guru_data = $guru_stmt->fetch();

// Jadwal hari ini
$hari_list = ['','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
$hari_ini = $hari_list[date('N')];
$jadwal_hari = $pdo->prepare("SELECT j.*, m.nama_mapel, k.nama_kelas, jm.jam_mulai, jm.jam_selesai, jm.nama_jam FROM tbl_jadwal j JOIN tbl_mapel m ON j.id_mapel=m.id_mapel JOIN tbl_kelas k ON j.id_kelas=k.id_kelas JOIN tbl_jam jm ON j.id_jam=jm.id_jam WHERE j.id_guru=? AND j.hari=? ORDER BY jm.jam_mulai");
$jadwal_hari->execute([$id_guru, $hari_ini]);
$jadwal_data = $jadwal_hari->fetchAll();

// Mapel count
$mapel_stmt = $pdo->prepare("SELECT COUNT(DISTINCT id_mapel) FROM tbl_jadwal WHERE id_guru=?");
$mapel_stmt->execute([$id_guru]);
$jml_mapel = $mapel_stmt->fetchColumn();

// Journal count bulan ini
$journal_stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_journal WHERE id_guru=? AND MONTH(tanggal)=? AND YEAR(tanggal)=?");
$journal_stmt->execute([$id_guru, date('m'), date('Y')]);
$jml_journal = $journal_stmt->fetchColumn();

// Tugas/Bahan count by guru
$tugas_stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_bahan_tugas WHERE id_guru=?");
$tugas_stmt->execute([$id_guru]);
$jml_tugas = $tugas_stmt->fetchColumn();

// Quiz aktif
$quiz_stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_quiz WHERE id_guru=? AND status='Aktif'");
$quiz_stmt->execute([$id_guru]);
$jml_quiz = $quiz_stmt->fetchColumn();

// Absensi guru hari ini
$abs_guru = $pdo->prepare("SELECT * FROM tbl_absensi_guru WHERE id_guru=? AND tanggal=?");
$abs_guru->execute([$id_guru, $today]);
$absen_hari = $abs_guru->fetch();

// Recent journals
$recent_journal = $pdo->prepare("SELECT j.*, m.nama_mapel, k.nama_kelas FROM tbl_journal j LEFT JOIN tbl_mapel m ON j.id_mapel=m.id_mapel LEFT JOIN tbl_kelas k ON j.id_kelas=k.id_kelas WHERE j.id_guru=? ORDER BY j.id DESC LIMIT 5");
$recent_journal->execute([$id_guru]);
$journals = $recent_journal->fetchAll();

require_once __DIR__ . '/template/header.php';
require_once __DIR__ . '/template/sidebar.php';
require_once __DIR__ . '/template/topbar.php';
?>

<!-- Welcome + Absensi Status -->
<div class="glass rounded-xl p-5 mb-6 border border-blue-500/20">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-xl font-bold shadow-lg shadow-blue-500/30"><?= strtoupper(substr($guru_data['nama'] ?? $_SESSION['nama'], 0, 1)) ?></div>
            <div>
                <h2 class="text-lg font-bold"><?= clean($guru_data['nama'] ?? $_SESSION['nama']) ?> 👨‍🏫</h2>
                <p class="text-sm text-slate-400"><?= $hari_ini ?>, <?= tgl_indo($today) ?></p>
                <?php if ($guru_data['tugas_tambahan']): ?><span class="text-xs px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-400"><?= clean($guru_data['tugas_tambahan']) ?></span><?php endif; ?>
            </div>
        </div>
        <div class="text-right hidden sm:block">
            <?php if ($absen_hari): 
                $status_label = 'Hadir';
                $status_class = 'bg-emerald-500/20 text-emerald-400';
                if ($absen_hari['status'] == 'Izin') { $status_label = 'Izin'; $status_class = 'bg-amber-500/20 text-amber-400'; }
                if ($absen_hari['status'] == 'Sakit') { $status_label = 'Sakit'; $status_class = 'bg-rose-500/20 text-rose-400'; }
            ?>
            <span class="text-xs px-3 py-1.5 rounded-full <?= $status_class ?>"><i class="fas fa-check-circle mr-1"></i><?= $status_label ?> · <?= substr($absen_hari['jam_masuk'],0,5) ?></span>
            <?php else: ?>
            <span class="text-xs px-3 py-1.5 rounded-full bg-red-500/20 text-red-400"><i class="fas fa-times-circle mr-1"></i>Belum Absen</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="stat-card glass rounded-xl p-5">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-600 to-cyan-500 flex items-center justify-center mb-3"><i class="fas fa-calendar text-white"></i></div>
        <p class="text-2xl font-bold"><?= count($jadwal_data) ?></p><p class="text-xs text-slate-400 mt-1">Jadwal Hari Ini</p>
    </div>
    <div class="stat-card glass rounded-xl p-5">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-600 to-teal-500 flex items-center justify-center mb-3"><i class="fas fa-book text-white"></i></div>
        <p class="text-2xl font-bold"><?= $jml_mapel ?></p><p class="text-xs text-slate-400 mt-1">Mata Pelajaran</p>
    </div>
    <div class="stat-card glass rounded-xl p-5">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-600 to-pink-500 flex items-center justify-center mb-3"><i class="fas fa-pen-fancy text-white"></i></div>
        <p class="text-2xl font-bold"><?= $jml_journal ?></p><p class="text-xs text-slate-400 mt-1">Journal Bulan Ini</p>
    </div>
    <div class="stat-card glass rounded-xl p-5">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center mb-3"><i class="fas fa-file-upload text-white"></i></div>
        <p class="text-2xl font-bold"><?= $jml_tugas ?></p><p class="text-xs text-slate-400 mt-1">Bahan/Tugas</p>
        <?php if ($jml_quiz > 0): ?><p class="text-[10px] text-amber-400 mt-1"><i class="fas fa-question-circle mr-1"></i><?= $jml_quiz ?> quiz aktif</p><?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <?php if (fitur_aktif('akademik')): ?>
    <a href="<?= BASE_URL ?>guru/journal.php" class="glass rounded-xl p-4 text-center hover:bg-white/10 transition-all group">
        <i class="fas fa-pen-fancy text-purple-400 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-medium">Tulis Journal</p>
    </a>
    <a href="<?= BASE_URL ?>guru/bahan_tugas.php" class="glass rounded-xl p-4 text-center hover:bg-white/10 transition-all group">
        <i class="fas fa-file-upload text-blue-400 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-medium">Upload Tugas</p>
    </a>
    <a href="<?= BASE_URL ?>guru/quiz.php" class="glass rounded-xl p-4 text-center hover:bg-white/10 transition-all group">
        <i class="fas fa-question-circle text-amber-400 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-medium">Quiz/Ujian</p>
    </a>
    <?php endif; ?>
    <?php if (fitur_aktif('absensi')): ?>
    <a href="<?= BASE_URL ?>guru/absensi.php" class="glass rounded-xl p-4 text-center hover:bg-white/10 transition-all group">
        <i class="fas fa-clipboard-check text-emerald-400 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-medium">Absensi Siswa</p>
    </a>
    <?php endif; ?>
    <button onclick="document.getElementById('modalIzinGuru').classList.remove('hidden')" class="glass rounded-xl p-4 text-center hover:bg-white/10 transition-all group border border-amber-500/20 cursor-pointer">
        <i class="fas fa-envelope-open-text text-amber-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-medium">Izin Mengajar</p>
    </button>
    <?php if (fitur_aktif('uks')): ?>
    <button onclick="document.getElementById('modalSakit').classList.remove('hidden')" class="glass rounded-xl p-4 text-center hover:bg-white/10 transition-all group border border-rose-500/20 cursor-pointer">
        <i class="fas fa-hand-holding-medical text-rose-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-medium">Lapor Sakit</p>
    </button>
    <?php endif; ?>
</div>

<!-- Modal Izin Guru (Tidak Mengajar) -->
<div id="modalIzinGuru" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[999] hidden flex items-center justify-center p-4">
    <div class="glass w-full max-w-md rounded-2xl border border-white/10 shadow-2xl overflow-hidden animate-zoom-in">
        <div class="p-6 border-b border-white/5 flex items-center justify-between">
            <h3 class="text-lg font-bold flex items-center gap-2 text-white italic font-black uppercase tracking-widest"><i class="fas fa-envelope-open-text text-amber-500"></i> Izin Tidak Mengajar</h3>
            <button onclick="document.getElementById('modalIzinGuru').classList.add('hidden')" class="text-slate-400 hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form id="formIzinGuru" class="p-6 space-y-4" enctype="multipart/form-data">
            <div class="grid grid-cols-2 gap-3 mb-4 bg-black/20 p-1 rounded-xl">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="status" value="Izin" checked class="hidden peer">
                    <div class="p-2 text-center rounded-lg text-[10px] font-bold uppercase transition-all peer-checked:bg-amber-600 peer-checked:text-white text-slate-500">Izin</div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="status" value="Sakit" class="hidden peer">
                    <div class="p-2 text-center rounded-lg text-[10px] font-bold uppercase transition-all peer-checked:bg-rose-600 peer-checked:text-white text-slate-500">Sakit</div>
                </label>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-2 uppercase tracking-widest font-bold">Alasan / Keterangan</label>
                <textarea name="keterangan" required placeholder="..." class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-amber-500 focus:outline-none min-h-[80px] text-white"></textarea>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-2 uppercase tracking-widest font-bold">Upload Bukti (Surat/Foto)</label>
                <input type="file" name="foto_bukti" accept="image/*" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2 text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-semibold file:bg-amber-600/20 file:text-amber-400 hover:file:bg-amber-600/30">
            </div>
            <p class="text-[9px] text-slate-500 italic mt-2 leading-relaxed leading-tight">*Pengajuan izin akan diteruskan ke Kepala Sekolah & Tata Usaha.</p>
            <div class="pt-2 flex gap-3">
                <button type="submit" class="w-full bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 py-3.5 rounded-xl text-sm font-bold text-white shadow-lg shadow-amber-900/20 transition-all font-black uppercase tracking-widest italic">Kirim Pengajuan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Lapor Sakit (Kesehatan/UKS) -->
<div id="modalSakit" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[999] hidden flex items-center justify-center p-4">
    <div class="glass w-full max-w-md rounded-2xl border border-white/10 shadow-2xl overflow-hidden animate-zoom-in">
        <div class="p-6 border-b border-white/5 flex items-center justify-between">
            <h3 class="text-lg font-bold flex items-center gap-2 text-white font-black italic uppercase tracking-widest"><i class="fas fa-hand-holding-medical text-rose-500"></i> Lapor Kesehatan/UKS</h3>
            <button onclick="document.getElementById('modalSakit').classList.add('hidden')" class="text-slate-400 hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form id="formLaporSakit" class="p-6 space-y-4">
            <input type="hidden" name="action" value="lapor_sakit">
            <div>
                <label class="block text-xs text-slate-400 mb-2 uppercase tracking-widest font-bold">Keluhan / Gejala</label>
                <textarea name="keluhan" required placeholder="..." class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-rose-500 focus:outline-none min-h-[100px] text-white"></textarea>
            </div>
            <p class="text-[10px] text-slate-500 italic leading-tight">Laporan akan langsung diteruskan ke petugas UKS dan Kepala Sekolah.</p>
            <div class="pt-2 flex gap-3">
                <button type="submit" class="w-full bg-rose-600 hover:bg-rose-500 py-4 rounded-xl text-sm font-bold text-white shadow-lg shadow-rose-600/20 transition-all font-black uppercase tracking-widest italic">Kirim Laporan UKS</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('formIzinGuru').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';

    const formData = new FormData(this);
    fetch('<?= BASE_URL ?>api/proses_izin_guru.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                title: 'Berhasil!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#d97706',
                confirmButtonText: 'Mantap!'
            }).then(() => location.reload());
            document.getElementById('modalIzinGuru').classList.add('hidden');
            this.reset();
        } else {
            Swal.fire('Gagal!', data.message, 'error');
        }
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Kirim Pengajuan';
    });
});

document.getElementById('formLaporSakit').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...';

    fetch('<?= BASE_URL ?>api/uks.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire('Terkirim!', data.message, 'success');
            document.getElementById('modalSakit').classList.add('hidden');
            this.reset();
        } else {
            Swal.fire('Gagal!', data.message, 'error');
        }
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Kirim Laporan';
    });
});
</script>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <!-- Jadwal Hari Ini -->
    <div class="glass rounded-xl p-5">
        <h3 class="text-sm font-semibold mb-3"><i class="fas fa-calendar-day mr-2 text-blue-400"></i>Jadwal Hari Ini — <?= $hari_ini ?></h3>
        <?php if ($jadwal_data): ?>
        <div class="space-y-2">
            <?php $now = date('H:i:s'); foreach ($jadwal_data as $r):
                $is_active = ($now >= $r['jam_mulai'] && $now <= $r['jam_selesai']);
                $is_done = ($now > $r['jam_selesai']);
            ?>
            <div class="flex items-center gap-4 p-3 rounded-lg <?= $is_active ? 'bg-blue-500/15 border border-blue-500/30' : ($is_done ? 'bg-white/3 opacity-60' : 'bg-white/5') ?> transition-all">
                <div class="text-center flex-shrink-0 w-14">
                    <p class="text-xs font-mono <?= $is_active ? 'text-blue-400' : 'text-slate-400' ?>"><?= substr($r['jam_mulai'],0,5) ?></p>
                    <p class="text-[10px] text-slate-500"><?= substr($r['jam_selesai'],0,5) ?></p>
                </div>
                <div class="flex-1">
                    <p class="font-medium text-sm"><?= clean($r['nama_mapel']) ?></p>
                    <p class="text-xs text-slate-400"><?= clean($r['nama_kelas']) ?> · <?= $r['nama_jam'] ?></p>
                </div>
                <?php if ($is_active): ?><span class="text-xs px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-400 animate-pulse">Berlangsung</span>
                <?php elseif ($is_done): ?><i class="fas fa-check-circle text-emerald-500/50"></i>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?><p class="text-sm text-slate-500 text-center py-4"><i class="fas fa-coffee mr-2"></i>Tidak ada jadwal hari ini.</p><?php endif; ?>
    </div>

    <!-- Journal Terbaru -->
    <div class="glass rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold"><i class="fas fa-pen-fancy mr-2 text-purple-400"></i>Journal Terbaru</h3>
            <a href="<?= BASE_URL ?>guru/journal.php" class="text-xs text-blue-400 hover:underline">Lihat Semua →</a>
        </div>
        <?php if ($journals): foreach ($journals as $j): ?>
        <div class="py-2 border-b border-white/5 last:border-0">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium"><?= clean($j['nama_mapel'] ?? '-') ?></span>
                <span class="text-[10px] text-slate-500"><?= tgl_indo($j['tanggal']) ?></span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5"><?= clean($j['nama_kelas'] ?? '-') ?> · <?= clean(substr($j['materi'] ?? '',0,60)) ?></p>
        </div>
        <?php endforeach; else: ?><p class="text-sm text-slate-500 text-center py-4">Belum ada journal.</p><?php endif; ?>
    </div>
</div>

<!-- Guru AI Chat Widget -->
<?php if (fitur_aktif('ai_chat')): ?>
<?php require_once __DIR__ . '/template/guru_ai_widget.php'; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/template/footer.php'; ?>
