<?php
$page_title = 'Sertifikasi Keagamaan Siswa';
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../api/wa_helper.php';
cek_role(['admin', 'waka_keagamaan']);
cek_fitur('agama');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_siswa'])) {
    $pdo->prepare("INSERT INTO tbl_agama_sertifikasi (id_siswa, jenis_sertifikasi, predikat, tanggal_lulus, catatan) VALUES (?,?,?,?,?)")
        ->execute([$_POST['id_siswa'], $_POST['jenis_sertifikasi'], $_POST['predikat'], $_POST['tanggal_lulus'], $_POST['catatan']]);
    
    // Auto Trigger WA & PWA
    wa_notif_sertifikasi($_POST['id_siswa'], $_POST['jenis_sertifikasi'], $_POST['predikat']);
    
    $_SESSION['swal_success'] = "Data sertifikasi berhasil disimpan. Notifikasi telah dikirim ke PWA & WA Wali Murid.";
    header('Location: sertifikasi.php'); 
    exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_agama_sertifikasi WHERE id=?")->execute([$_GET['hapus']]);
    flash('msg', 'Data sertifikasi dihapus!', 'warning'); 
    header('Location: sertifikasi.php'); 
    exit;
}

$siswa = $pdo->query("SELECT * FROM tbl_siswa WHERE status='Aktif' ORDER BY nama")->fetchAll();
$data = $pdo->query("SELECT s.*, m.nama, m.nisn FROM tbl_agama_sertifikasi s JOIN tbl_siswa m ON s.id_siswa=m.id_siswa ORDER BY s.tanggal_lulus DESC")->fetchAll();

require_once __DIR__ . '/../../template/header.php';
require_once __DIR__ . '/../../template/sidebar.php';
require_once __DIR__ . '/../../template/topbar.php';
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Interaktif SweetAlert jika sukses -->
<?php if(isset($_SESSION['swal_success'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'success',
            title: 'Alhamdulillah Berhasil!',
            text: '<?= clean($_SESSION['swal_success']) ?>',
            timer: 4000,
            showConfirmButton: false,
            background: '#1e293b',
            color: '#fff',
            customClass: { popup: 'border border-white/10 rounded-2xl shadow-2xl' }
        });
    });
</script>
<?php unset($_SESSION['swal_success']); endif; ?>

<?= alert_flash('msg') ?>
<button onclick="document.getElementById('frm').classList.toggle('hidden')" class="bg-teal-600 hover:bg-teal-500 px-4 py-2 rounded-lg text-sm font-medium mb-4 shadow-lg shadow-teal-500/20 no-print">
    <i class="fas fa-certificate mr-1"></i>Input Kelulusan Sertifikasi Baru
</button>

<div id="frm" class="hidden glass rounded-xl p-5 mb-6 border border-white/5 border-t-teal-500 border-t-4 shadow-2xl no-print">
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4" id="formSertif" onsubmit="showLoading()">
        <div><label class="block text-xs text-slate-400 mb-1">Pilih Santri / Siswa</label>
            <select name="id_siswa" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-teal-500 focus:outline-none">
                <option value="">-- Cari Siswa --</option>
                <?php foreach($siswa as $s): ?><option value="<?= $s['id_siswa'] ?>"><?= clean($s['nama']) ?> (<?= clean($s['nisn']) ?>)</option><?php endforeach; ?>
            </select>
        </div>
        <div><label class="block text-xs text-slate-400 mb-1">Program Sertifikasi</label>
            <select name="jenis_sertifikasi" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-teal-500 focus:outline-none">
                <?php foreach(['Tahfidz Al-Qur\'an','Tartil Al-Qur\'an','Praktek Sholat','Khotmil Qur\'an'] as $k): ?><option><?= $k ?></option><?php endforeach; ?>
            </select>
        </div>
        <div><label class="block text-xs text-slate-400 mb-1">Status Predikat Kelulusan</label>
            <select name="predikat" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-teal-500 focus:outline-none">
                <option value="Mumtaz (Sangat Baik)">Mumtaz (Sangat Baik)</option>
                <option value="Jayyid Jiddan (Baik Sekali)">Jayyid Jiddan (Baik Sekali)</option>
                <option value="Jayyid (Baik)">Jayyid (Baik)</option>
                <option value="Maqbul (Cukup)">Maqbul (Cukup)</option>
            </select>
        </div>
        <div><label class="block text-xs text-slate-400 mb-1">Tanggal Kelulusan Ujian</label><input type="date" name="tanggal_lulus" value="<?= date('Y-m-d') ?>" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-teal-500 focus:outline-none"></div>
        <div class="md:col-span-2"><label class="block text-xs text-slate-400 mb-1">Catatan Tambahan Dewan Penguji</label><input type="text" name="catatan" placeholder="Misal: Lulus Uji Hafalan Juz 30" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-teal-500 focus:outline-none"></div>
        
        <div class="md:col-span-2 pt-2">
            <button type="submit" id="btnSimpan" class="bg-teal-600 hover:bg-teal-500 px-8 py-2.5 rounded-lg text-sm font-bold transition-all"><i class="fas fa-save mr-1"></i>Simpan & Kirim Notifikasi</button>
        </div>
    </form>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="hidden fixed inset-0 z-[60] flex flex-col items-center justify-center bg-black/80 backdrop-blur-sm no-print">
    <div class="w-16 h-16 border-4 border-slate-700 border-t-teal-500 rounded-full animate-spin mb-4"></div>
    <h3 class="text-white text-lg font-bold animate-pulse tracking-wide">Memproses Sertifikasi...</h3>
    <p class="text-slate-400 text-sm mt-2">Menyebarkan kabar gembira ke WA & PWA Orang Tua. Mohon tunggu.</p>
</div>

<div class="glass rounded-xl p-5 border border-white/5">
    <form action="cetak_sertifikasi.php" method="POST" target="_blank">
        <div class="flex justify-between items-center mb-4 no-print">
            <h3 class="text-white font-bold"><i class="fas fa-list mr-2 text-teal-400"></i>Data Sertifikasi</h3>
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium shadow-lg shadow-blue-500/20"><i class="fas fa-print mr-2"></i>Cetak Terpilih</button>
        </div>
        <div class="table-container">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-slate-400 border-b border-white/10">
                    <th class="pb-3 w-10 text-center no-print"><input type="checkbox" onchange="document.querySelectorAll('.chk-cetak').forEach(c => c.checked = this.checked)" class="rounded border-white/10 bg-slate-800 text-blue-500"></th>
                    <th class="pb-3 w-64">Nama Siswa</th><th class="pb-3 w-48">Materi Sertifikasi</th><th class="pb-3 w-48">Nilai / Predikat</th><th class="pb-3">Catatan Munaqis</th><th class="pb-3 text-right no-print">Aksi</th>
                </tr></thead>
                <tbody><?php foreach ($data as $r): ?>
                <tr class="border-b border-white/5 hover:bg-white/5">
                    <td class="py-3 text-center no-print"><input type="checkbox" name="id_cetak[]" value="<?= $r['id'] ?>" class="chk-cetak rounded border-white/10 bg-slate-800 text-blue-500"></td>
                    <td>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-teal-400 border border-teal-500/20"><i class="fas fa-user-graduate text-xs"></i></div>
                <div>
                    <span class="block font-bold text-white"><?= clean($r['nama']) ?></span>
                    <span class="block text-[10px] text-slate-500 mt-0.5 font-mono">NISN: <?= clean($r['nisn']) ?></span>
                </div>
            </div>
        </td>
        <td>
            <span class="block text-xs font-bold text-teal-400"><i class="fas fa-quran mr-1.5 opacity-70"></i><?= clean($r['jenis_sertifikasi']) ?></span>
            <span class="block text-[10px] text-slate-500 mt-1"><i class="far fa-calendar-check mr-1"></i><?= tgl_indo($r['tanggal_lulus']) ?></span>
        </td>
        <td>
            <?php 
            $c = 'bg-amber-500/20 text-amber-400';
            if (strpos($r['predikat'], 'Mumtaz') !== false) $c = 'bg-rose-500/20 text-rose-400 shadow-[0_0_10px_rgba(244,63,94,0.3)] shadow-rose-500/50';
            if (strpos($r['predikat'], 'Jayyid') !== false) $c = 'bg-emerald-500/20 text-emerald-400';
            ?>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?= $c ?>"><?= clean($r['predikat']) ?></span>
        </td>
        <td class="text-[11px] text-slate-300 w-1/4 leading-relaxed italic"><i class="fas fa-quote-left text-slate-600 mr-1 text-[8px]"></i><?= clean($r['catatan']) ?: '-' ?></td>
        <td class="text-right no-print">
            <button onclick="confirmDelete('?hapus=<?= $r['id'] ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 hover:bg-red-600/40 text-[10px]"><i class="fas fa-trash"></i></button>
        </td>
    </tr>
    <?php endforeach; if(!$data) echo '<tr><td colspan="6" class="text-center py-8 text-slate-400"><i class="fas fa-certificate text-3xl mb-2 opacity-50 block"></i>Belum ada data murid yang disertifikasi.</td></tr>'; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('btnSimpan').innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Memproses...';
        document.getElementById('btnSimpan').disabled = true;
    }, 10);
    return true;
}
</script>

<?php require_once __DIR__ . '/../../template/footer.php'; ?>
