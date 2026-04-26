<?php
$page_title = 'Dashboard Siswa';
require_once __DIR__ . '/config/init.php';
cek_role(['siswa']);
$id_siswa = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT s.*, k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE s.id_siswa=?");
$stmt->execute([$id_siswa]);
$me = $stmt->fetch();

// Tagihan pending bulan ini
$tag_stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_jenis_bayar jb WHERE jb.tipe='Bulanan' AND jb.id_jenis NOT IN (SELECT id_jenis FROM tbl_pembayaran WHERE id_siswa=? AND bulan=? AND tahun=?)");
$tag_stmt->execute([$id_siswa, date('n'), date('Y')]);
$tagihan = $tag_stmt->fetchColumn();

// Kehadiran bulan ini
$absen_stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_absensi_siswa WHERE id_siswa=? AND MONTH(tanggal)=? AND YEAR(tanggal)=?");
$absen_stmt->execute([$id_siswa, date('m'), date('Y')]);
$absen_bulan = $absen_stmt->fetchColumn();

// Tugas
$id_kelas = (int)($me['id_kelas'] ?? 0);
$tugas_stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_bahan_tugas WHERE tipe='Tugas' AND id_kelas=?");
$tugas_stmt->execute([$id_kelas]);
$tugas = $tugas_stmt->fetchColumn();

// Quiz aktif
$quiz_stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_quiz WHERE id_kelas=? AND status='Aktif'");
$quiz_stmt->execute([$id_kelas]);
$quiz_count = $quiz_stmt->fetchColumn();

// Riwayat pembayaran terakhir
$hist_stmt = $pdo->prepare("SELECT p.*, j.nama_jenis FROM tbl_pembayaran p LEFT JOIN tbl_jenis_bayar j ON p.id_jenis=j.id_jenis WHERE p.id_siswa=? ORDER BY p.id_pembayaran DESC LIMIT 5");
$hist_stmt->execute([$id_siswa]);
$riwayat_bayar = $hist_stmt->fetchAll();

// Absensi terakhir
// Riwayat absensi terakhir
$abs_stmt = $pdo->prepare("SELECT * FROM tbl_absensi_siswa WHERE id_siswa=? ORDER BY id DESC LIMIT 5");
$abs_stmt->execute([$id_siswa]);
$riwayat_absen = $abs_stmt->fetchAll();

// ── Jadwal Hari Ini ──
$hari_map = ['Sunday'=>'','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
$hari_ini = $hari_map[date('l')] ?? '';
$jadwal_hari_ini = [];
if ($id_kelas && $hari_ini) {
    $jdw = $pdo->prepare("
        SELECT j.*, m.nama_mapel, g.nama AS nama_guru, jm.nama_jam, jm.jam_mulai, jm.jam_selesai
        FROM tbl_jadwal j
        JOIN tbl_mapel m ON j.id_mapel = m.id_mapel
        LEFT JOIN tbl_guru g ON j.id_guru = g.id_guru
        JOIN tbl_jam jm ON j.id_jam = jm.id_jam
        WHERE j.id_kelas = ? AND j.hari = ?
        ORDER BY jm.jam_mulai
    ");
    $jdw->execute([$id_kelas, $hari_ini]);
    $jadwal_hari_ini = $jdw->fetchAll();
}
$pastel_jadwal = ['#dbeafe','#dcfce7','#fef9c3','#fce7f3','#e0e7ff','#f3e8ff','#ccfbf1','#ffedd5','#fee2e2','#ecfeff'];

require_once __DIR__ . '/template/header.php';
require_once __DIR__ . '/template/sidebar.php';
require_once __DIR__ . '/template/topbar.php';
?>

<!-- Profile Card -->
<div class="glass rounded-xl p-6 mb-6 border border-blue-500/20">
    <div class="flex items-center gap-4">
        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold shadow-lg shadow-blue-500/30"><?= strtoupper(substr($me['nama'] ?? 'S', 0, 1)) ?></div>
        <div class="flex-1">
            <h3 class="text-lg font-bold"><?= clean($me['nama'] ?? '') ?></h3>
            <p class="text-sm text-slate-400"><?= clean($me['nama_kelas'] ?? '-') ?> · NISN: <?= clean($me['nisn'] ?? '-') ?></p>
            <div class="flex gap-2 mt-1">
                <span class="inline-block text-xs px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400"><?= $me['status'] ?? 'Aktif' ?></span>
                <?php if ($me['no_hp_siswa']): ?><span class="inline-block text-xs px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-400"><i class="fas fa-phone mr-1"></i><?= clean($me['no_hp_siswa']) ?></span><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php if (fitur_aktif('keuangan')): ?>
    <div class="stat-card glass rounded-xl p-5">
        <i class="fas fa-file-invoice-dollar text-amber-400 text-xl mb-2"></i>
        <p class="text-2xl font-bold text-amber-400"><?= $tagihan ?></p>
        <p class="text-xs text-slate-400">Tagihan Pending</p>
    </div>
    <?php endif; ?>
    <?php if (fitur_aktif('absensi')): ?>
    <div class="stat-card glass rounded-xl p-5">
        <i class="fas fa-user-check text-emerald-400 text-xl mb-2"></i>
        <p class="text-2xl font-bold"><?= $absen_bulan ?></p>
        <p class="text-xs text-slate-400">Kehadiran Bulan Ini</p>
    </div>
    <?php endif; ?>
    <?php if (fitur_aktif('tabungan')): ?>
    <div class="stat-card glass rounded-xl p-5">
        <i class="fas fa-piggy-bank text-blue-400 text-xl mb-2"></i>
        <p class="text-xl font-bold text-blue-400"><?= rupiah($me['saldo'] ?? 0) ?></p>
        <p class="text-xs text-slate-400">Saldo Tabungan</p>
    </div>
    <?php endif; ?>
    <div class="stat-card glass rounded-xl p-5">
        <i class="fas fa-tasks text-purple-400 text-xl mb-2"></i>
        <p class="text-2xl font-bold"><?= $tugas ?></p>
        <p class="text-xs text-slate-400">Tugas</p>
        <?php if ($quiz_count > 0): ?><p class="text-[10px] text-amber-400 mt-1"><i class="fas fa-exclamation-circle mr-1"></i><?= $quiz_count ?> quiz aktif</p><?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
    <?php if (fitur_aktif('keuangan')): ?>
    <a href="<?= BASE_URL ?>siswa/tagihan.php" class="glass rounded-xl p-4 text-center hover:bg-white/10 transition-all group border border-white/5">
        <i class="fas fa-file-invoice text-amber-400 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-medium">Tagihan</p>
    </a>
    <?php endif; ?>
    <?php if (fitur_aktif('absensi')): ?>
    <a href="<?= BASE_URL ?>siswa/absensi.php" class="glass rounded-xl p-4 text-center hover:bg-white/10 transition-all group border border-white/5">
        <i class="fas fa-calendar-check text-emerald-400 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-medium">Absensi</p>
    </a>
    <?php endif; ?>
    <?php if (fitur_aktif('kesiswaan')): ?>
    <a href="<?= BASE_URL ?>siswa/eskul.php" class="glass rounded-xl p-4 text-center hover:bg-white/10 transition-all group border border-white/5">
        <i class="fas fa-futbol text-blue-400 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-medium">Eskul</p>
    </a>
    <a href="<?= BASE_URL ?>siswa/prestasi.php" class="glass rounded-xl p-4 text-center hover:bg-white/10 transition-all group border border-white/5">
        <i class="fas fa-trophy text-amber-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-medium">Prestasi</p>
    </a>
    <?php endif; ?>
    <?php if (fitur_aktif('akademik')): ?>
    <a href="<?= BASE_URL ?>siswa/tugas.php" class="glass rounded-xl p-4 text-center hover:bg-white/10 transition-all group border border-white/5">
        <i class="fas fa-book-open text-blue-400 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-medium">Tugas</p>
    </a>
    <a href="<?= BASE_URL ?>siswa/quiz.php" class="glass rounded-xl p-4 text-center hover:bg-white/10 transition-all group border border-white/5">
        <i class="fas fa-question-circle text-purple-400 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-medium">Quiz</p>
    </a>
    <?php endif; ?>
    <?php if (fitur_aktif('uks')): ?>
    <button onclick="document.getElementById('modalSakit').classList.remove('hidden')" class="glass rounded-xl p-4 text-center hover:bg-white/10 transition-all group border border-white/5 cursor-pointer">
        <i class="fas fa-hand-holding-medical text-rose-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
        <p class="text-xs font-medium">Lapor Sakit</p>
    </button>
    <?php endif; ?>
</div>

<!-- Modal Lapor Sakit -->
<div id="modalSakit" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[999] hidden flex items-center justify-center p-4">
    <div class="glass w-full max-w-md rounded-2xl border border-white/10 shadow-2xl overflow-hidden animate-zoom-in">
        <div class="p-6 border-b border-white/5 flex items-center justify-between">
            <h3 class="text-lg font-bold flex items-center gap-2"><i class="fas fa-hand-holding-medical text-rose-500"></i>Lapor Kondisi Kesehatan</h3>
            <button onclick="document.getElementById('modalSakit').classList.add('hidden')" class="text-slate-400 hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form id="formLaporSakit" class="p-6 space-y-4">
            <input type="hidden" name="action" value="lapor_sakit">
            <div>
                <label class="block text-xs text-slate-400 mb-2 uppercase tracking-widest font-bold">Keluhan / Gejala</label>
                <textarea name="keluhan" required placeholder="Cth: Pusing, demam tinggi, atau nyeri perut..." class="w-full bg-slate-900/50 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-rose-500 focus:outline-none min-h-[100px]"></textarea>
            </div>
            <p class="text-[10px] text-slate-500 italic">Laporan akan langsung diteruskan ke petugas UKS untuk ditindaklanjuti.</p>
            <div class="pt-2 flex gap-3">
                <button type="button" onclick="document.getElementById('modalSakit').classList.add('hidden')" class="flex-1 px-4 py-3 rounded-xl bg-slate-800 text-sm font-bold hover:bg-slate-700 transition-all">Batal</button>
                <button type="submit" class="flex-2 bg-rose-600 hover:bg-rose-500 px-6 py-3 rounded-xl text-sm font-bold shadow-lg shadow-rose-600/20 transition-all">Kirim Laporan</button>
            </div>
        </form>
    </div>
</div>

<script>
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

<?php if (fitur_aktif('akademik') && !empty($jadwal_hari_ini)): ?>
<!-- Jadwal Hari Ini -->
<div class="glass rounded-2xl p-5 mb-6 border border-blue-500/20 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl"></div>
    <div class="flex items-center justify-between mb-4 relative z-10">
        <h3 class="text-sm font-bold flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-blue-600/30 border border-blue-500/30 flex items-center justify-center">
                <i class="fas fa-calendar-day text-blue-400 text-xs"></i>
            </div>
            <span>Jadwal Hari Ini — <span class="text-blue-400"><?= $hari_ini ?></span></span>
        </h3>
        <a href="<?= BASE_URL ?>siswa/jadwal.php" class="text-xs text-blue-400 hover:underline">Lihat Mingguan →</a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 relative z-10">
        <?php foreach ($jadwal_hari_ini as $jd):
            $is_ist = (stripos($jd['nama_jam'], 'istirahat') !== false);
            $bg = $pastel_jadwal[abs(crc32($jd['nama_mapel'])) % count($pastel_jadwal)];
        ?>
        <div class="rounded-xl p-3 border text-center transition-all hover:scale-[1.02] <?= $is_ist ? 'border-amber-500/30 bg-amber-500/10' : 'border-white/10' ?>" <?= !$is_ist ? "style='background:{$bg}15; border-color:{$bg}30;'" : '' ?>>
            <div class="text-[10px] font-mono text-slate-400 mb-1">
                <?= substr($jd['jam_mulai'],0,5) ?> - <?= substr($jd['jam_selesai'],0,5) ?>
            </div>
            <?php if ($is_ist): ?>
                <div class="font-bold text-xs text-amber-300 italic"><i class="fas fa-coffee mr-1"></i>Istirahat</div>
            <?php else: ?>
                <div class="font-bold text-xs text-white leading-tight"><?= clean($jd['nama_mapel']) ?></div>
                <?php if (!empty($jd['nama_guru'])): ?>
                <div class="text-[9px] text-slate-400 mt-1 truncate" title="<?= clean($jd['nama_guru']) ?>">
                    <i class="fas fa-user-tie mr-0.5"></i><?= clean($jd['nama_guru']) ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Recent History -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <?php if (fitur_aktif('keuangan')): ?>
    <!-- Riwayat Pembayaran -->
    <div class="glass rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold"><i class="fas fa-receipt text-emerald-400 mr-2"></i>Pembayaran Terakhir</h3>
            <a href="<?= BASE_URL ?>siswa/tagihan.php" class="text-xs text-blue-400 hover:underline">Lihat Semua →</a>
        </div>
        <?php if ($riwayat_bayar): foreach ($riwayat_bayar as $r): ?>
        <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
            <div>
                <span class="text-sm font-medium"><?= clean($r['nama_jenis']) ?></span>
                <p class="text-[10px] text-slate-500"><?= $r['bulan'] ? bulan_indo($r['bulan']) . ' ' . $r['tahun'] : tgl_indo($r['tanggal_bayar']) ?></p>
            </div>
            <span class="text-sm text-emerald-400 font-medium"><?= rupiah($r['jumlah_bayar']) ?></span>
        </div>
        <?php endforeach; else: ?><p class="text-sm text-slate-500 text-center py-3">Belum ada riwayat.</p><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (fitur_aktif('absensi')): ?>
    <!-- Riwayat Absensi -->
    <div class="glass rounded-xl p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold"><i class="fas fa-calendar-check text-blue-400 mr-2"></i>Absensi Terakhir</h3>
            <a href="<?= BASE_URL ?>siswa/absensi.php" class="text-xs text-blue-400 hover:underline">Lihat Semua →</a>
        </div>
        <?php if ($riwayat_absen): foreach ($riwayat_absen as $r): ?>
        <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
            <div>
                <span class="text-sm"><?= tgl_indo($r['tanggal']) ?></span>
                <p class="text-[10px] text-slate-500">Masuk: <?= substr($r['jam_masuk'],0,5) ?><?= $r['jam_keluar'] ? ' · Pulang: '.substr($r['jam_keluar'],0,5) : '' ?></p>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full <?= $r['keterangan']=='Tepat Waktu'?'bg-emerald-500/20 text-emerald-400':($r['keterangan']=='Terlambat'?'bg-amber-500/20 text-amber-400':'bg-blue-500/20 text-blue-400') ?>"><?= $r['keterangan'] ?? $r['status'] ?></span>
        </div>
        <?php endforeach; else: ?><p class="text-sm text-slate-500 text-center py-3">Belum ada data absensi.</p><?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Guru AI Chat Widget -->
<?php if (fitur_aktif('ai_chat')): ?>
<?php require_once __DIR__ . '/template/guru_ai_widget.php'; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/template/footer.php'; ?>
