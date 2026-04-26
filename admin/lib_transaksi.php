<?php
$page_title = 'Sirkulasi Perpustakaan';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin', 'petugas']);
cek_fitur('perpustakaan');

if (isset($_POST['pinjam'])) {
    $tgl_pinjam = date('Y-m-d');
    $tgl_kembali = date('Y-m-d', strtotime('+7 days'));
    
    $pdo->prepare("INSERT INTO tbl_lib_pinjam (id_buku, user_id, user_role, tgl_pinjam, tgl_kembali_rencana, status) VALUES (?, ?, ?, ?, ?, 'Pinjam')")
        ->execute([$_POST['id_buku'], $_POST['user_id'], $_POST['user_role'], $tgl_pinjam, $tgl_kembali]);
    
    // Potong stok jika buku fisik
    $pdo->prepare("UPDATE tbl_lib_buku SET stok = stok - 1 WHERE id = ? AND stok > 0")->execute([$_POST['id_buku']]);
    
    flash('msg', 'Peminjaman berhasil dicatat!');
    header('Location: lib_transaksi.php'); exit;
}

if (isset($_GET['kembali'])) {
    $pinjam = $pdo->prepare("SELECT * FROM tbl_lib_pinjam WHERE id = ?");
    $pinjam->execute([$_GET['kembali']]);
    $p = $pinjam->fetch();
    
    if ($p) {
        $pdo->prepare("UPDATE tbl_lib_pinjam SET tgl_kembali_asli = NOW(), status = 'Kembali' WHERE id = ?")->execute([$_GET['kembali']]);
        $pdo->prepare("UPDATE tbl_lib_buku SET stok = stok + 1 WHERE id = ?")->execute([$p['id_buku']]);
        flash('msg', 'Buku telah dikembalikan!');
    }
    header('Location: lib_transaksi.php'); exit;
}

$status_filter = $_GET['status'] ?? 'Pinjam';
$query = "SELECT p.*, b.judul, b.cover, b.penulis, 
          COALESCE(s.nama, g.nama) as nama_peminjam,
          COALESCE(s.foto, g.foto) as foto_peminjam
          FROM tbl_lib_pinjam p 
          JOIN tbl_lib_buku b ON p.id_buku = b.id 
          LEFT JOIN tbl_siswa s ON p.user_id = s.id_siswa AND p.user_role = 'siswa'
          LEFT JOIN tbl_guru g ON p.user_id = g.id_guru AND p.user_role = 'guru'
          WHERE p.status = ? 
          ORDER BY p.tgl_pinjam DESC";
$data = $pdo->prepare($query); $data->execute([$status_filter]); $data = $data->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex flex-col lg:flex-row gap-8">
    <!-- Left: Sirkulasi List -->
    <div class="flex-1">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold tracking-tight italic uppercase"><i class="fas fa-exchange-alt mr-2 text-blue-500"></i>Sirkulasi Buku</h2>
            <div class="flex gap-2 bg-slate-800/50 p-1 rounded-xl border border-white/5">
                <a href="?status=Pinjam" class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all <?= $status_filter=='Pinjam'?'bg-blue-600 shadow-lg shadow-blue-600/20':'text-slate-400 hover:text-white' ?>">Dipinjam</a>
                <a href="?status=Kembali" class="px-4 py-1.5 rounded-lg text-xs font-bold transition-all <?= $status_filter=='Kembali'?'bg-emerald-600 shadow-lg shadow-emerald-600/20':'text-slate-400 hover:text-white' ?>">Kembali</a>
            </div>
        </div>

        <?= alert_flash('msg') ?>

        <div class="grid grid-cols-1 gap-4">
            <?php if (empty($data)): ?>
                <div class="glass rounded-2xl p-20 text-center opacity-30 border-2 border-dashed border-white/10">
                    <i class="fas fa-history text-4xl mb-3 block"></i>
                    <p class="text-xs font-bold uppercase tracking-widest">Tidak ada data sirkulasi</p>
                </div>
            <?php else: foreach ($data as $r): ?>
                <div class="glass rounded-2xl p-4 flex items-center justify-between border border-white/5 hover:border-blue-500/20 transition-all group">
                    <div class="flex items-center gap-4">
                        <img src="<?= BASE_URL ?>assets/uploads/lib_covers/<?= $r['cover'] ?>" class="w-12 h-16 object-cover rounded-lg shadow-lg">
                        <div>
                            <h4 class="font-bold text-sm text-white"><?= clean($r['judul']) ?></h4>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Peminjam: <span class="text-blue-400"><?= clean($r['nama_peminjam']) ?></span> (<?= ucfirst($r['user_role']) ?>)</p>
                            <div class="flex items-center gap-3 mt-2">
                                <span class="text-[9px] text-slate-500"><i class="far fa-calendar mr-1"></i>Pinjam: <?= tgl_indo($r['tgl_pinjam']) ?></span>
                                <span class="text-[9px] font-bold <?= strtotime($r['tgl_kembali_rencana']) < time() && $r['status']=='Pinjam' ? 'text-red-400':'text-slate-500' ?>"><i class="far fa-clock mr-1"></i>Tempo: <?= tgl_indo($r['tgl_kembali_rencana']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php if ($r['status'] == 'Pinjam'): ?>
                    <button onclick="confirmAction('?kembali=<?= $r['id'] ?>', 'Buku akan dikembalikan ke stok?')" class="bg-emerald-600/20 hover:bg-emerald-600 text-emerald-400 hover:text-white px-4 py-2 rounded-xl text-xs font-bold transition-all opacity-0 group-hover:opacity-100">
                        <i class="fas fa-undo mr-2"></i>Kembalikan
                    </button>
                    <?php else: ?>
                    <div class="text-right">
                        <span class="text-[9px] text-slate-500 block">Kembali Pada:</span>
                        <span class="text-xs font-bold text-emerald-400 italic"><?= tgl_indo($r['tgl_kembali_asli']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Right: RFID Live Tap & Form -->
    <div class="lg:w-96">
        <div class="sticky top-6 space-y-6">
            <!-- RFID Live Monitor -->
            <div class="glass rounded-3xl p-6 border border-white/5 relative overflow-hidden bg-gradient-to-br from-blue-600/10 to-transparent">
                <div class="absolute top-4 right-4 animate-pulse">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-lg shadow-emerald-500/50 block"></span>
                </div>
                <h3 class="text-sm font-black uppercase tracking-widest mb-6">RFID Live Monitor</h3>
                
                <div id="live-tap-area" class="flex flex-col items-center py-6">
                    <div id="tap-idle">
                        <div class="w-20 h-20 rounded-full bg-slate-800 flex items-center justify-center text-3xl text-slate-600 border-4 border-slate-700 animate-pulse mb-4">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest text-center">Menunggu Tap Kartu...</p>
                    </div>
                    
                    <div id="tap-user" class="hidden w-full text-center">
                        <div class="w-24 h-24 rounded-full mx-auto p-1 bg-gradient-to-tr from-blue-500 to-purple-600 mb-4 shadow-xl">
                            <img id="user-foto" src="" class="w-full h-full object-cover rounded-full border-2 border-white/20">
                        </div>
                        <h4 id="user-nama" class="font-black text-lg text-white italic"></h4>
                        <p id="user-role-label" class="text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-6"></p>
                        
                        <div class="p-4 bg-white/5 rounded-2xl border border-white/5 text-left mb-6">
                            <p id="user-info-extra" class="text-[10px] text-slate-500 leading-relaxed"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manual Pinjam Form -->
            <div id="form-pinjam-manual" class="glass rounded-3xl p-6 border border-white/5">
                <h3 class="text-sm font-black uppercase tracking-widest mb-4">Input Peminjaman</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="user_id" id="input_user_id">
                    <input type="hidden" name="user_role" id="input_user_role">
                    
                    <div>
                        <label class="block text-[10px] text-slate-500 uppercase font-bold tracking-widest mb-1.5 px-1">Pilih Buku</label>
                        <select name="id_buku" required class="w-full bg-slate-800/50 border border-white/10 rounded-xl px-4 py-3 text-xs focus:border-blue-500 transition-all">
                            <option value="">-- Cari Judul Buku --</option>
                            <?php 
                            $books = $pdo->query("SELECT id, judul, stok FROM tbl_lib_buku WHERE stok > 0 ORDER BY judul")->fetchAll();
                            foreach($books as $b):
                            ?>
                            <option value="<?= $b['id'] ?>"><?= clean($b['judul']) ?> (Stok: <?= $b['stok'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="pinjam" id="btn-submit-pinjam" disabled class="w-full py-4 rounded-2xl bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-xs transition-all border border-white/5">
                        Lengkapi Data User
                    </button>
                    <p class="text-[9px] text-slate-500 italic text-center">Silakan tap kartu untuk mengisi data peminjam secara otomatis.</p>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let lastCheckId = 0;
function checkRfid() {
    fetch('<?= BASE_URL ?>api/rfid.php?action=cek_monitor')
        .then(res => res.json())
        .then(res => {
            if (res.status === 'ok') {
                // Get Detailed User Info
                fetch('<?= BASE_URL ?>api/rfid.php?action=lib_tap&rfid=' + res.rfid)
                    .then(r => r.json())
                    .then(user => {
                        if (user.status === 'ok') {
                            showUser(user);
                        }
                    });
            }
        });
}

function showUser(u) {
    document.getElementById('tap-idle').classList.add('hidden');
    document.getElementById('tap-user').classList.remove('hidden');
    
    document.getElementById('user-nama').innerText = u.nama;
    document.getElementById('user-role-label').innerText = u.role;
    
    // Auto fill hidden forms
    document.getElementById('input_user_id').value = u.id_siswa || u.id_guru;
    document.getElementById('input_user_role').value = u.role;
    
    const btn = document.getElementById('btn-submit-pinjam');
    btn.disabled = false;
    btn.classList.remove('bg-slate-800', 'text-slate-500');
    btn.classList.add('bg-blue-600', 'text-white', 'shadow-lg', 'shadow-blue-600/20');
}

setInterval(checkRfid, 2000);
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
