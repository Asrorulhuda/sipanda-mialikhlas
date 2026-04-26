<?php
$page_title = 'Prestasi Siswa';
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../api/wa_helper.php';
cek_role(['guru']);
cek_fitur('kesiswaan');
$id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_siswa'])) {
    $pdo->prepare("INSERT INTO tbl_prestasi (id_siswa,jenis,nama_prestasi,tingkat,tanggal,keterangan) VALUES (?,?,?,?,?,?)")
        ->execute([$_POST['id_siswa'], $_POST['jenis'], $_POST['nama_prestasi'], $_POST['tingkat'], $_POST['tanggal'], $_POST['keterangan']]);
    
    // Trigger WA & PWA Notification
    wa_notif_prestasi($_POST['id_siswa'], $_POST['nama_prestasi'], $_POST['tingkat'], $_POST['jenis']);
    
    $_SESSION['swal_success'] = "Data prestasi berhasil disimpan dan Notifikasi dikirim.";
    header('Location: prestasi.php');
    exit;
}

$siswa = $pdo->query("SELECT * FROM tbl_siswa WHERE status='Aktif' ORDER BY nama")->fetchAll();
$data = $pdo->query("SELECT p.*,s.nama,k.nama_kelas FROM tbl_prestasi p JOIN tbl_siswa s ON p.id_siswa=s.id_siswa LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas ORDER BY p.tanggal DESC LIMIT 50")->fetchAll();

require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
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
            icon: 'success',
            title: 'Berhasil!',
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

<div class="flex flex-wrap gap-2 mb-4 no-print">
    <button onclick="document.getElementById('frm').classList.toggle('hidden')" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-plus mr-1"></i>Tambah Prestasi</button>
    <button onclick="window.print()" class="bg-emerald-600 hover:bg-emerald-500 px-4 py-2 rounded-lg text-sm font-medium transition-colors"><i class="fas fa-print mr-1"></i>Cetak Laporan</button>
</div>

<div id="frm" class="hidden glass rounded-xl p-5 mb-6 no-print">
    <h4 class="text-sm font-semibold mb-4"><i class="fas fa-trophy text-amber-400 mr-1"></i>Form Prestasi Baru</h4>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4" id="formPrestasi" onsubmit="showLoading()">
        <div><label class="block text-xs text-slate-400 mb-1">Siswa</label>
            <select name="id_siswa" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                <option value="">-- Pilih Siswa --</option>
                <?php foreach ($siswa as $s): ?><option value="<?= $s['id_siswa'] ?>"><?= clean($s['nama']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div><label class="block text-xs text-slate-400 mb-1">Nama Prestasi</label>
            <input type="text" name="nama_prestasi" required placeholder="Juara 1 Lomba Cerdas Cermat" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
        </div>
        <div><label class="block text-xs text-slate-400 mb-1">Jenis</label>
            <select name="jenis" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                <option value="Akademik">Akademik</option>
                <option value="Non-Akademik">Non-Akademik</option>
                <option value="Olahraga">Olahraga</option>
                <option value="Seni">Seni</option>
                <option value="Lainnya">Lainnya</option>
            </select>
        </div>
        <div><label class="block text-xs text-slate-400 mb-1">Tingkat</label>
            <select name="tingkat" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                <option value="Sekolah">Sekolah</option>
                <option value="Kecamatan">Kecamatan</option>
                <option value="Kabupaten/Kota">Kabupaten/Kota</option>
                <option value="Provinsi">Provinsi</option>
                <option value="Nasional">Nasional</option>
                <option value="Internasional">Internasional</option>
            </select>
        </div>
        <div><label class="block text-xs text-slate-400 mb-1">Tanggal</label>
            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
        </div>
        <div><label class="block text-xs text-slate-400 mb-1">Keterangan</label>
            <input type="text" name="keterangan" placeholder="Detail tambahan..." class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="md:col-span-2">
            <button type="submit" name="simpan" id="btnSimpan" class="bg-amber-600 hover:bg-amber-500 px-6 py-2 rounded-lg text-sm font-medium transition-all"><i class="fas fa-save mr-1"></i>Simpan Target & Notifikasi</button>
        </div>
    </form>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="hidden fixed inset-0 z-[60] flex flex-col items-center justify-center bg-black/80 backdrop-blur-sm no-print">
    <div class="w-16 h-16 border-4 border-slate-700 border-t-blue-500 rounded-full animate-spin mb-4"></div>
    <h3 class="text-white text-lg font-bold animate-pulse tracking-wide">Menyimpan & Mengirim Notifikasi...</h3>
    <p class="text-slate-400 text-sm mt-2">Menyalurkan sinyal ke Endpoint WhatsApp & PWA. Mohon tunggu.</p>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="glass rounded-xl p-4 text-center">
        <p class="text-2xl font-bold text-amber-400"><?= count($data) ?></p>
        <p class="text-xs text-slate-400">Total Prestasi</p>
    </div>
    <?php
    $akademik = count(array_filter($data, fn($r) => $r['jenis'] === 'Akademik'));
    $non = count(array_filter($data, fn($r) => $r['jenis'] !== 'Akademik'));
    $tingkat_nasional = count(array_filter($data, fn($r) => in_array($r['tingkat'], ['Nasional','Internasional'])));
    ?>
    <div class="glass rounded-xl p-4 text-center">
        <p class="text-2xl font-bold text-blue-400"><?= $akademik ?></p>
        <p class="text-xs text-slate-400">Akademik</p>
    </div>
    <div class="glass rounded-xl p-4 text-center">
        <p class="text-2xl font-bold text-purple-400"><?= $non ?></p>
        <p class="text-xs text-slate-400">Non-Akademik</p>
    </div>
    <div class="glass rounded-xl p-4 text-center">
        <p class="text-2xl font-bold text-emerald-400"><?= $tingkat_nasional ?></p>
        <p class="text-xs text-slate-400">Nasional+</p>
    </div>
</div>

<!-- Table -->
<div class="glass rounded-xl p-5 print:p-0 print:border-none print:shadow-none" id="cetak-area">
    <!-- KOP SURAT RESMI (Hanya Tampil Saat Print) -->
    <div class="hidden print:block mb-6 border-b-[3px] border-black pb-4 text-black">
        <div class="flex items-center justify-between">
            <div class="w-24 text-center">
                <div class="w-20 h-20 mx-auto rounded-full border-2 border-black flex items-center justify-center font-bold text-2xl">S</div>
            </div>
            <div class="flex-1 text-center px-4">
                <h2 class="text-xl font-bold uppercase tracking-wide">YAYASAN PENDIDIKAN SIPANDA</h2>
                <h1 class="text-2xl font-extrabold uppercase mt-1 tracking-wider"><?= clean($setting['nama_sekolah'] ?? 'SEKOLAH MASA DEPAN') ?></h1>
                <p class="text-sm mt-1">Alamat: Jl. Pendidikan No. 1, Kabupaten/Kota, Kodepos: 12345</p>
                <p class="text-xs italic">Email: info@sipanda.sch.id | Website: www.sipanda.sch.id</p>
            </div>
            <div class="w-24"></div>
        </div>
    </div>

    <div class="text-center font-bold mb-6 hidden print:block text-xl text-black uppercase underline tracking-wider">
        Laporan Data Prestasi Siswa
    </div>

    <div class="table-container">
        <table class="w-full text-sm print:text-black print:border-collapse print:border print:border-black">
            <thead>
                <tr class="text-left text-slate-400 border-b border-white/10 print:text-black print:border-black">
                    <th class="pb-3 print:p-2 print:border print:border-black print:bg-slate-100">#</th>
                    <th class="pb-3 print:p-2 print:border print:border-black print:bg-slate-100">Siswa</th>
                    <th class="pb-3 print:p-2 print:border print:border-black print:bg-slate-100">Kelas</th>
                    <th class="pb-3 print:p-2 print:border print:border-black print:bg-slate-100">Prestasi</th>
                    <th class="pb-3 print:p-2 print:border print:border-black print:bg-slate-100">Jenis</th>
                    <th class="pb-3 print:p-2 print:border print:border-black print:bg-slate-100">Tingkat</th>
                    <th class="pb-3 print:p-2 print:border print:border-black print:bg-slate-100">Tanggal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $i => $r): ?>
                <tr class="border-b border-white/5 hover:bg-white/5 print:border-black">
                    <td class="py-2 print:p-2 print:border print:border-black text-center"><?= $i+1 ?></td>
                    <td class="font-medium text-blue-400 print:text-black print:font-semibold print:p-2 print:border print:border-black"><?= clean($r['nama']) ?></td>
                    <td class="print:p-2 print:border print:border-black text-center"><span class="px-2 py-0.5 rounded-full text-xs bg-purple-500/20 text-purple-400 print:bg-transparent print:text-black print:p-0"><?= clean($r['nama_kelas'] ?? '-') ?></span></td>
                    <td class="text-slate-200 print:text-black print:p-2 print:border print:border-black"><?= clean($r['nama_prestasi']) ?></td>
                    <td class="print:p-2 print:border print:border-black text-center"><span class="px-2 py-0.5 rounded-full text-xs <?= $r['jenis']==='Akademik' ? 'bg-blue-500/20 text-blue-400' : 'bg-amber-500/20 text-amber-400' ?> print:bg-transparent print:text-black print:p-0"><?= clean($r['jenis']) ?></span></td>
                    <td class="text-slate-300 print:text-black print:p-2 print:border print:border-black"><?= clean($r['tingkat']) ?></td>
                    <td class="text-slate-400 text-xs print:text-black print:p-2 print:border print:border-black"><?= tgl_indo($r['tanggal']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($data)): ?>
                <tr><td colspan="7" class="text-center py-6 text-slate-500 print:border print:border-black">Belum ada data prestasi.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Kolom Tanda Tangan (Hanya Tampil Saat Print) -->
    <div class="hidden print:flex justify-end mt-12 text-black text-sm">
        <div class="text-center">
            <p>Mengetahui,</p>
            <p class="mb-20">Guru Walikelas</p>
            <p class="font-bold underline uppercase"><?= clean($_SESSION['nama'] ?? 'Nama Guru') ?></p>
            <p>NIP. -</p>
        </div>
    </div>
</div>

<script>
function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('btnSimpan').innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Memproses...';
        document.getElementById('btnSimpan').disabled = true;
    }, 10);
    return true; // Allow native form submission
}
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
