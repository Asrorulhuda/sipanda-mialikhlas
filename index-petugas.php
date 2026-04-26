<?php
$page_title = 'Dashboard Petugas Perpustakaan';
require_once __DIR__ . '/config/init.php';
cek_role(['admin', 'petugas']);

$today = date('Y-m-d');
$jml_kunjung = $pdo->prepare("SELECT COUNT(*) FROM tbl_lib_kunjung WHERE tanggal=?");
$jml_kunjung->execute([$today]); $kunjung_today = $jml_kunjung->fetchColumn();

$jml_pinjam = $pdo->prepare("SELECT COUNT(*) FROM tbl_lib_pinjam WHERE status='Pinjam'");
$jml_pinjam->execute(); $pinjam_aktif = $jml_pinjam->fetchColumn();

$jml_jatuh_tempo = $pdo->prepare("SELECT COUNT(*) FROM tbl_lib_pinjam WHERE status='Pinjam' AND tgl_kembali_rencana < ?");
$jml_jatuh_tempo->execute([$today]); $jatuh_tempo = $jml_jatuh_tempo->fetchColumn();

$total_buku = $pdo->query("SELECT COUNT(*) FROM tbl_lib_buku")->fetchColumn();

require_once __DIR__ . '/template/header.php';
require_once __DIR__ . '/template/sidebar.php';
require_once __DIR__ . '/template/topbar.php';
?>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="glass rounded-3xl p-6 border border-white/5 bg-gradient-to-br from-blue-600/20 to-transparent">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-500/20 flex items-center justify-center text-blue-400">
                <i class="fas fa-users text-xl"></i>
            </div>
            <span class="text-[10px] font-bold text-blue-400 uppercase tracking-widest bg-blue-500/10 px-2 py-1 rounded-lg border border-blue-500/20">Hari Ini</span>
        </div>
        <p class="text-slate-400 text-xs font-medium mb-1">Kunjungan Perpus</p>
        <h3 class="text-3xl font-black text-white italic"><?= $kunjung_today ?> <span class="text-sm font-normal not-italic text-slate-500">Siswa</span></h3>
    </div>

    <div class="glass rounded-3xl p-6 border border-white/5 bg-gradient-to-br from-indigo-600/20 to-transparent">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 flex items-center justify-center text-indigo-400">
                <i class="fas fa-exchange-alt text-xl"></i>
            </div>
        </div>
        <p class="text-slate-400 text-xs font-medium mb-1">Peminjaman Aktif</p>
        <h3 class="text-3xl font-black text-white italic"><?= $pinjam_aktif ?> <span class="text-sm font-normal not-italic text-slate-500">Buku</span></h3>
    </div>

    <div class="glass rounded-3xl p-6 border border-white/5 bg-gradient-to-br from-red-600/20 to-transparent">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 rounded-2xl bg-red-500/20 flex items-center justify-center text-red-500">
                <i class="fas fa-clock text-xl"></i>
            </div>
            <?php if ($jatuh_tempo > 0): ?>
            <span class="animate-pulse text-[10px] font-bold text-red-500 uppercase tracking-widest bg-red-500/10 px-2 py-1 rounded-lg border border-red-500/20">Perlu Tindakan</span>
            <?php endif; ?>
        </div>
        <p class="text-slate-400 text-xs font-medium mb-1">Jatuh Tempo</p>
        <h3 class="text-3xl font-black text-white italic"><?= $jatuh_tempo ?> <span class="text-sm font-normal not-italic text-slate-500">Buku</span></h3>
    </div>

    <div class="glass rounded-3xl p-6 border border-white/5 bg-gradient-to-br from-emerald-600/20 to-transparent">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                <i class="fas fa-book text-xl"></i>
            </div>
        </div>
        <p class="text-slate-400 text-xs font-medium mb-1">Total Koleksi</p>
        <h3 class="text-3xl font-black text-white italic"><?= $total_buku ?> <span class="text-sm font-normal not-italic text-slate-500">Judul</span></h3>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-stretch">
    <!-- Quick Access & RFID -->
    <div class="glass rounded-[2.5rem] p-8 border border-white/5 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-blue-600/5 rounded-full blur-3xl -mr-16 -mt-16"></div>
        <h4 class="text-lg font-black italic uppercase tracking-tighter mb-8"><i class="fas fa-bolt text-blue-500 mr-2"></i>Aksi Cepat Petugas</h4>
        
        <div class="grid grid-cols-1 gap-4 mb-10">
            <a href="admin/lib_transaksi.php" class="flex items-center justify-between p-5 bg-blue-600 hover:bg-blue-500 text-white rounded-[2rem] shadow-xl shadow-blue-600/20 transition-all transform hover:-translate-y-1 group">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-xl">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <p class="font-black italic text-lg leading-tight uppercase">Buka Peminjaman (RFID)</p>
                        <p class="text-[10px] text-white/70 font-bold uppercase tracking-widest">Tap kartu siswa untuk melayani</p>
                    </div>
                </div>
                <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
            </a>

            <a href="admin/lib_buku.php" class="flex items-center justify-between p-5 bg-white/5 hover:bg-white/10 rounded-[2rem] border border-white/5 transition-all group">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-xl">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div>
                        <p class="font-bold text-white text-sm">Tambah Koleksi Baru</p>
                        <p class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">Input buku fisik atau e-book</p>
                    </div>
                </div>
            </a>
        </div>

        <h4 class="text-xs font-black uppercase tracking-widest text-slate-500 mb-4 border-b border-white/5 pb-2">Kunjungan Terakhir</h4>
        <div class="space-y-4">
            <?php 
            $recent_k = $pdo->prepare("SELECT k.*, s.nama FROM tbl_lib_kunjung k JOIN tbl_siswa s ON k.id_siswa=s.id_siswa WHERE k.tanggal=? ORDER BY k.id DESC LIMIT 4");
            $recent_k->execute([$today]);
            $rk = $recent_k->fetchAll();
            if (empty($rk)): ?>
                <p class="text-xs text-slate-500 italic p-4 text-center">Belum ada kunjungan hari ini...</p>
            <?php else: foreach($rk as $kv): ?>
                <div class="flex items-center justify-between p-3 bg-white/5 rounded-2xl border border-white/5">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-[10px] font-black"><?= strtoupper(substr($kv['nama'], 0, 1)) ?></div>
                        <span class="text-xs font-bold"><?= clean($kv['nama']) ?></span>
                    </div>
                    <span class="text-[10px] font-mono text-slate-500"><?= date('H:i', strtotime($kv['jam'])) ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Peminjaman Jatuh Tempo List -->
    <div class="glass rounded-[2.5rem] p-8 border border-white/5">
        <h4 class="text-lg font-black italic uppercase tracking-tighter mb-8 text-red-400"><i class="fas fa-exclamation-triangle mr-2"></i>Segera Lewat Tempo</h4>
        <div class="space-y-4">
            <?php 
            $overdue = $pdo->prepare("SELECT p.*, b.judul, s.nama FROM tbl_lib_pinjam p JOIN tbl_lib_buku b ON p.id_buku=b.id JOIN tbl_siswa s ON p.user_id=s.id_siswa WHERE p.status='Pinjam' AND p.tgl_kembali_rencana <= ? ORDER BY p.tgl_kembali_rencana ASC LIMIT 5");
            $overdue->execute([date('Y-m-d', strtotime('+2 days'))]);
            $ov = $overdue->fetchAll();
            if (empty($ov)): ?>
                <div class="flex flex-col items-center justify-center py-20 opacity-30 text-center">
                    <i class="fas fa-check-double text-4xl mb-4 text-emerald-500"></i>
                    <p class="text-xs font-bold uppercase tracking-widest">Semua Aman! Tidak ada pinjaman kritis.</p>
                </div>
            <?php else: foreach($ov as $o): ?>
                <?php $is_late = strtotime($o['tgl_kembali_rencana']) < time(); ?>
                <div class="p-5 bg-white/5 rounded-3xl border <?= $is_late ? 'border-red-500/20' : 'border-white/5' ?> hover:bg-white/10 transition-all flex justify-between items-center group">
                    <div>
                        <h5 class="font-bold text-sm leading-tight mb-1"><?= clean($o['judul']) ?></h5>
                        <p class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">Peminjam: <span class="text-white"><?= clean($o['nama']) ?></span></p>
                        <div class="mt-2 text-[10px] font-bold <?= $is_late ? 'text-red-400' : 'text-amber-400' ?> uppercase tracking-widest px-2 py-0.5 rounded-lg inline-block bg-white/5">
                            <i class="fas fa-calendar-times mr-1"></i>Hingga: <?= tgl_indo($o['tgl_kembali_rencana']) ?>
                        </div>
                    </div>
                    <a href="admin/lib_transaksi.php" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-blue-600 flex items-center justify-center text-slate-500 hover:text-white transition-all opacity-0 group-hover:opacity-100"><i class="fas fa-chevron-right text-xs"></i></a>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/template/footer.php'; ?>
