<?php
$page_title = 'Bimbingan Konseling';
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../api/wa_helper.php';
cek_role(['admin', 'waka_kesiswaan']);
cek_fitur('kesiswaan');

// Ambil Tahun Ajaran Aktif
$ta_aktif = $pdo->query("SELECT id_ta FROM tbl_tahun_ajaran WHERE status='aktif' LIMIT 1")->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_siswa'])) {
    $id_ta = $ta_aktif ?: 0;
    $id_siswa = $_POST['id_siswa'];
    $kategori = $_POST['kategori'];
    $poin = $_POST['poin'] ?? 0;
    $is_instant_sp3 = isset($_POST['is_instant_sp3']) ? 1 : 0;

    $pdo->prepare("INSERT INTO tbl_bk (id_siswa,id_ta,id_guru,tanggal,jenis,kategori,is_instant_sp3,deskripsi,tindakan,poin) VALUES (?,?,?,?,?,?,?,?,?,?)")
        ->execute([$id_siswa, $id_ta, $_POST['id_guru'] ?? $_SESSION['user_id'], $_POST['tanggal'], $_POST['jenis'], $kategori, $is_instant_sp3, $_POST['deskripsi'], $_POST['tindakan'], $poin]);
    
    // Recalculate points to show status in SweetAlert
    $total_poin = $pdo->prepare("SELECT SUM(poin) FROM tbl_bk WHERE id_siswa = ? AND id_ta = ?");
    $total_poin->execute([$id_siswa, $id_ta]);
    $curr_poin = $total_poin->fetchColumn();

    $status_msg = "Total Poin Ananda saat ini: " . ($curr_poin ?? 0);
    if($is_instant_sp3) $status_msg .= " (STATUS: LANGSUNG SP 3)";
    
    // Trigger WA & PWA Notification
    wa_notif_bk($id_siswa, ($_POST['jenis'] . " - " . $kategori), $_POST['deskripsi']);
    
    $_SESSION['swal_success'] = "Data bimbingan berhasil disimpan. " . $status_msg;
    header('Location: bk.php');
    exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_bk WHERE id=?")->execute([$_GET['hapus']]);
    flash('msg','Dihapus!','warning');
    header('Location: bk.php');
    exit;
}

$siswa = $pdo->query("SELECT * FROM tbl_siswa WHERE status='Aktif' ORDER BY nama")->fetchAll();
$guru_bk = $pdo->query("SELECT * FROM tbl_guru WHERE is_bk=1 AND status='Aktif'")->fetchAll();

// Query Data BK dengan detail poin tahunan
$data = $pdo->query("SELECT b.*, s.nama, g.nama as nama_guru, 
    (SELECT SUM(poin) FROM tbl_bk WHERE id_siswa = b.id_siswa AND id_ta = b.id_ta) as total_poin_ta,
    (SELECT COUNT(*) FROM tbl_bk WHERE id_siswa = b.id_siswa AND id_ta = b.id_ta AND is_instant_sp3 = 1) as has_instant
    FROM tbl_bk b 
    JOIN tbl_siswa s ON b.id_siswa=s.id_siswa 
    LEFT JOIN tbl_guru g ON b.id_guru=g.id_guru 
    WHERE b.id_ta = '$ta_aktif'
    ORDER BY b.tanggal DESC LIMIT 100")->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
@media print {
    body * { visibility: hidden; }
    #cetak-area, #cetak-area * { visibility: visible; }
    #cetak-area { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
    #sidebar, #topbar { display: none !important; }
}
</style>

<!-- Interaktif SweetAlert jika sukses -->
<?php if(isset($_SESSION['swal_success'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'info',
            title: 'Berhasil!',
            text: '<?= clean($_SESSION['swal_success']) ?>',
            timer: 5000,
            showConfirmButton: true,
            confirmButtonText: 'Oke',
            background: '#1e293b',
            color: '#fff',
            customClass: { popup: 'border border-white/10 rounded-2xl shadow-2xl', confirmButton: 'bg-blue-600 px-6 py-2 rounded-lg' }
        });
    });
</script>
<?php unset($_SESSION['swal_success']); endif; ?>

<?= alert_flash('msg') ?>
<div class="flex flex-wrap gap-2 mb-4 no-print">
    <button onclick="document.getElementById('frm').classList.toggle('hidden')" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium shadow-lg shadow-blue-600/20"><i class="fas fa-plus mr-1"></i>Input Bimbingan / Pelanggaran</button>
    <button onclick="window.print()" class="bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors border border-white/10"><i class="fas fa-print mr-1"></i>Cetak Log Harian</button>
</div>

<div id="frm" class="hidden glass rounded-xl p-5 mb-6 no-print border-t-4 border-t-amber-500 shadow-2xl relative overflow-hidden">
    <div class="absolute -top-10 -right-10 w-32 h-32 bg-amber-500/10 rounded-full blur-3xl"></div>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4" onsubmit="showLoading()">
        <div class="md:col-span-1">
            <label class="block text-xs text-slate-400 mb-1">Pilih Siswa</label>
            <select name="id_siswa" required class="w-full bg-slate-800/80 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-amber-500 outline-none">
                <option value="">-- Pilih Siswa --</option>
                <?php foreach ($siswa as $s): ?><option value="<?= $s['id_siswa'] ?>"><?= clean($s['nama']) ?></option><?php endforeach; ?>
            </select>
        </div>
        
        <div><label class="block text-xs text-slate-400 mb-1">Tanggal</label><input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" class="w-full bg-slate-800/80 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-amber-500 outline-none"></div>
        
        <div>
            <label class="block text-xs text-slate-400 mb-1">Jenis Kasus</label>
            <select name="jenis" class="w-full bg-slate-800/80 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-amber-500 outline-none">
                <option value="Pelanggaran">Pelanggaran</option>
                <option value="Bimbingan">Bimbingan (Konseling)</option>
            </select>
        </div>

        <div>
            <label class="block text-xs text-slate-400 mb-1 text-amber-400 font-bold">Kategori & Bobot</label>
            <select name="kategori" id="kat" onchange="updatePoin()" class="w-full bg-slate-800/80 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-amber-500 outline-none">
                <option value="Ringan">Ringan (5-10 Poin)</option>
                <option value="Sedang">Sedang (20-40 Poin)</option>
                <option value="Berat">Berat (75-100 Poin)</option>
                <option value="Reward">Reward / Pemutihan (- Poin)</option>
            </select>
        </div>

        <div><label class="block text-xs text-slate-400 mb-1">Poin Pelanggaran</label><input type="number" name="poin" id="poin_val" value="5" class="w-full bg-slate-800/80 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-amber-500 outline-none"></div>

        <div class="flex items-center pt-5">
            <label class="flex items-center gap-2 cursor-pointer group">
                <input type="checkbox" name="is_instant_sp3" value="1" class="w-4 h-4 rounded border-white/10 bg-slate-800 text-rose-600 focus:ring-rose-500">
                <span class="text-xs text-slate-300 group-hover:text-rose-400 transition-colors font-bold">Pelanggaran Khusus (Langsung SP 3)</span>
            </label>
        </div>

        <?php if ($_SESSION['role']=='admin'): ?>
        <div class="md:col-span-1">
            <label class="block text-xs text-slate-400 mb-1">Guru Pendamping</label>
            <select name="id_guru" class="w-full bg-slate-800/80 border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                <option value="<?= $_SESSION['user_id'] ?>">Saya Sendiri</option>
                <?php foreach ($guru_bk as $g): ?><option value="<?= $g['id_guru'] ?>"><?= clean($g['nama']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="md:col-span-2"><label class="block text-xs text-slate-400 mb-1">Deskripsi / Kronologi Kejadian</label><textarea name="deskripsi" rows="2" required placeholder="Contoh: Terlambat masuk sekolah 15 menit..." class="w-full bg-slate-800/80 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-amber-500 outline-none"></textarea></div>
        <div class="md:col-span-3"><label class="block text-xs text-slate-400 mb-1">Tindakan / Solusi yang Diberikan</label><input type="text" name="tindakan" placeholder="Contoh: Pembinaan lisan dan penugasan membersihkan kelas" class="w-full bg-slate-800/80 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-amber-500 outline-none"></div>
        
        <div class="md:col-span-3 pt-2">
            <button type="submit" name="simpan" id="btnSimpan" class="bg-amber-600 hover:bg-amber-500 px-10 py-2.5 rounded-lg text-sm font-bold transition-all shadow-lg shadow-amber-600/20"><i class="fas fa-save mr-2"></i>Simpan Target & Kirim Notifikasi</button>
        </div>
    </form>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="hidden fixed inset-0 z-[60] flex flex-col items-center justify-center bg-black/80 backdrop-blur-sm no-print">
    <div class="w-16 h-16 border-4 border-slate-700 border-t-amber-500 rounded-full animate-spin mb-4"></div>
    <h3 class="text-white text-lg font-bold animate-pulse tracking-wide">Memproses & Menganalisis Poin...</h3>
    <p class="text-slate-400 text-sm mt-2">Sinkronisasi data ke server PWA & WhatsApp. Mohon tunggu.</p>
</div>

<div class="glass rounded-xl p-5 border border-white/5 print:p-0 print:border-none print:shadow-none" id="cetak-area">
    <!-- ADVANCED UNIFIED KOP SURAT -->
    <div class="hidden print:block mb-6 border-b-[3px] border-black pb-4 text-black font-serif">
        <div class="flex items-center justify-between">
            <div class="w-24 text-center">
                <?php if(!empty($setting['logo_kiri'])): ?>
                    <img src="../gambar/<?= $setting['logo_kiri'] ?>" class="w-20 h-20 mx-auto object-contain">
                <?php else: ?>
                    <div class="w-20 h-20 mx-auto rounded-full border-2 border-black flex items-center justify-center font-bold text-2xl">S</div>
                <?php endif; ?>
            </div>
            <div class="flex-1 text-center px-4">
                <?php if(!empty($setting['instansi_atas'])): ?>
                    <h3 class="text-xs font-bold uppercase tracking-widest leading-none mb-1"><?= clean($setting['instansi_atas']) ?></h3>
                <?php endif; ?>
                <?php if(!empty($setting['nama_yayasan'])): ?>
                    <h2 class="text-sm font-bold uppercase tracking-wide leading-none mb-1"><?= clean($setting['nama_yayasan']) ?></h2>
                <?php endif; ?>
                <h1 class="text-2xl font-extrabold uppercase mt-1 tracking-wider"><?= clean($setting['nama_sekolah'] ?? 'SEKOLAH MASA DEPAN') ?></h1>
                <p class="text-[11px] mt-1 pr-2"><?= clean($setting['alamat'] ?? '-') ?></p>
                <p class="text-[10px] italic">Telp: <?= clean($setting['telepon'] ?? '-') ?> | Email: <?= clean($setting['email'] ?? '-') ?></p>
            </div>
            <div class="w-24 text-center">
                <?php if(!empty($setting['logo_kanan'])): ?>
                    <img src="../gambar/<?= $setting['logo_kanan'] ?>" class="w-20 h-20 mx-auto object-contain">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="text-center font-bold mb-6 hidden print:block text-xl text-black uppercase underline tracking-wider">LOG HARIAN BIMBINGAN KONSELING (TA: <?php $ta_print=get_ta_aktif($pdo); echo clean($ta_print['tahun'] ?? '-'); ?>)</div>

    <div class="table-container text-white print:text-black">
        <table class="w-full text-sm print:border-collapse print:border print:border-black">
            <thead><tr class="text-left text-slate-400 border-b border-white/10 print:text-black print:border-black">
                <th class="pb-3 px-2 print:p-2 print:border print:border-black print:bg-slate-100">#</th>
                <th class="pb-3 print:p-2 print:border print:border-black print:bg-slate-100 italic text-center">Tanggal</th>
                <th class="pb-3 print:p-2 print:border print:border-black print:bg-slate-100">Siswa / Santri</th>
                <th class="pb-3 text-center print:p-2 print:border print:border-black print:bg-slate-100">Kategori</th>
                <th class="pb-3 print:p-2 print:border print:border-black print:bg-slate-100">Kasus / Deskripsi</th>
                <th class="pb-3 text-center print:p-2 print:border print:border-black print:bg-slate-100">Status & Dokumen</th>
                <th class="pb-3 text-right no-print">Aksi</th>
            </tr></thead>
            <tbody>
            <?php foreach ($data as $i => $r): 
                $p = $r['total_poin_ta'] ?? 0;
                $sp_status = "Aman";
                $sp_level = 0;
                $sp_color = "text-emerald-400 border-emerald-500/20 bg-emerald-500/10";
                
                if($r['has_instant'] > 0 || $p >= ($setting['bk_sp3_min']??100)) { $sp_status = "SP 3 / DO"; $sp_level = 3; $sp_color = "text-rose-400 border-rose-500/20 bg-rose-500/20 animate-pulse"; }
                elseif($p >= ($setting['bk_sp2_min']??75)) { $sp_status = "SP 2"; $sp_level = 2; $sp_color = "text-orange-400 border-orange-500/20 bg-orange-500/10"; }
                elseif($p >= ($setting['bk_sp1_min']??50)) { $sp_status = "SP 1"; $sp_level = 1; $sp_color = "text-amber-400 border-amber-500/20 bg-amber-500/10"; }
                elseif($p >= 25) { $sp_status = "Teguran"; $sp_color = "text-blue-400 border-blue-500/20 bg-blue-500/10"; }
            ?>
            <tr class="border-b border-white/5 hover:bg-white/5 transition-colors print:border-black">
                <td class="py-4 px-2 text-slate-500 text-center print:border print:border-black"><?= $i+1 ?></td>
                <td class="font-mono text-[11px] text-center print:p-2 print:border print:border-black"><?= tgl_indo($r['tanggal']) ?></td>
                <td class="print:p-2 print:border print:border-black">
                    <span class="block font-bold"><?= clean($r['nama']) ?></span>
                    <span class="block text-[10px] text-slate-500 flex items-center gap-1 mt-0.5"><i class="fas fa-calculator text-[8px] opacity-70"></i>Akumulasi: <b class="text-amber-500"><?= $p ?> Poin</b></span>
                </td>
                <td class="text-center print:p-2 print:border print:border-black">
                    <span class="px-2 py-0.5 rounded text-[9px] font-extrabold uppercase <?= $r['kategori']=='Berat'?'text-rose-400':($r['kategori']=='Sedang'?'text-orange-400':'text-blue-400') ?>"><?= $r['kategori'] ?></span>
                </td>
                <td class="max-w-xs print:p-2 print:border print:border-black">
                    <p class="text-[11px] italic leading-relaxed text-slate-300 print:text-black"><?= clean($r['deskripsi']) ?></p>
                    <p class="text-[9px] text-slate-500 mt-1 uppercase font-bold print:hidden"><i class="fas fa-hand-holding-heart mr-1.5 opacity-50"></i><?= clean($r['tindakan']) ?: '-' ?></p>
                </td>
                <td class="text-center print:p-2 print:border print:border-black">
                    <div class="flex flex-col items-center gap-1.5">
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black border <?= $sp_color ?>"><?= $sp_status ?></span>
                        <?php if($sp_level > 0): ?>
                            <a href="cetak_sp.php?id_siswa=<?= $r['id_siswa'] ?>&level=<?= $sp_level ?>" target="_blank" class="text-[10px] text-blue-400 hover:text-white hover:underline flex items-center gap-1 no-print"><i class="fas fa-file-signature"></i> Cetak SP <?= $sp_level ?></a>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="text-right no-print">
                    <button onclick="confirmDelete('?hapus=<?= $r['id'] ?>')" class="p-2 rounded bg-rose-600/10 hover:bg-rose-600 text-rose-500 hover:text-white transition-all"><i class="fas fa-trash-alt text-xs"></i></button>
                </td>
            </tr>
            <?php endforeach; if(empty($data)) echo '<tr><td colspan="7" class="py-12 text-center text-slate-500">Belum ada catatan BK untuk periode ini.</td></tr>'; ?>
            </tbody>
        </table>
    </div>

    <!-- Kolom Tanda Tangan (Hanya Tampil Saat Print) -->
    <div class="hidden print:flex justify-end mt-12 text-black text-sm font-serif">
        <div class="text-center">
            <p><?= clean($setting['kota'] ?? 'Kabupaten/Kota') ?>, <?= tgl_indo(date('Y-m-d')) ?></p>
            <p class="mb-20">Kepala Tata Usaha,</p>
            <p class="font-bold underline uppercase"><?= clean($setting['nama_tu'] ?? '........................................') ?></p>
            <p>NIP. -</p>
        </div>
    </div>
</div>

<script>
function updatePoin() {
    const kat = document.getElementById('kat').value;
    const poin = document.getElementById('poin_val');
    if(kat === 'Ringan') poin.value = 5;
    if(kat === 'Sedang') poin.value = 25;
    if(kat === 'Berat') poin.value = 75;
    if(kat === 'Reward') poin.value = -10;
}

function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('btnSimpan').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menganalisis...';
        document.getElementById('btnSimpan').disabled = true;
    }, 10);
    return true;
}
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
