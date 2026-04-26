<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../api/wa_helper.php';
cek_role(['admin','guru','kepsek']);
cek_fitur('absensi');

$tgl = $_GET['tgl'] ?? date('Y-m-d');
$kelas = (int)($_GET['kelas'] ?? 0);

// Handle Tambah Manual
if (isset($_POST['tambah_manual'])) {
    $id_siswa = (int)$_POST['id_siswa'];
    $keterangan = $_POST['keterangan'];
    $jam_masuk = date('H:i:s');
    
    // Cek jika sudah ada
    $check = $pdo->prepare("SELECT id FROM tbl_absensi_siswa WHERE id_siswa=? AND tanggal=?");
    $check->execute([$id_siswa, $tgl]);
    if (!$check->fetch()) {
        $pdo->prepare("INSERT INTO tbl_absensi_siswa (id_siswa, tanggal, jam_masuk, status, keterangan, metode) VALUES (?,?,?,?,?,?)")
            ->execute([$id_siswa, $tgl, $jam_masuk, 'IN', $keterangan, 'Manual']);
        wa_notif_absensi($id_siswa, "MASUK ($keterangan)", $jam_masuk);
    }
    header("Location: absensi_siswa.php?tgl=$tgl&kelas=$kelas&s=tambah_berhasil");
    exit;
}


// Handle Hapus
if (isset($_GET['hapus'])) {
    $id_abs = (int)$_GET['hapus'];
    $pdo->prepare("DELETE FROM tbl_absensi_siswa WHERE id = ?")->execute([$id_abs]);
    header("Location: absensi_siswa.php?tgl=$tgl&kelas=$kelas&s=hapus_berhasil");
    exit;
}

$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();
$data = [];
if ($kelas) {
    $stmt = $pdo->prepare("SELECT a.*, s.nama, s.nisn FROM tbl_absensi_siswa a JOIN tbl_siswa s ON a.id_siswa=s.id_siswa WHERE s.id_kelas=? AND a.tanggal=? ORDER BY a.jam_masuk");
    $stmt->execute([$kelas, $tgl]);
    $data = $stmt->fetchAll();
    // Untuk modal tambah manual
    $siswa_list = $pdo->prepare("SELECT id_siswa, nama FROM tbl_siswa WHERE id_kelas=? AND status='Aktif' ORDER BY nama");
    $siswa_list->execute([$kelas]);
    $list_siswa = $siswa_list->fetchAll();
}

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>
<div class="glass rounded-xl p-5 mb-6"><form method="GET" class="flex flex-wrap gap-3 items-end">
    <div><label class="block text-xs text-slate-400 mb-1">Tanggal</label><input type="date" name="tgl" value="<?= $tgl ?>" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()"></div>
    <div><label class="block text-xs text-slate-400 mb-1">Kelas</label><select name="kelas" onchange="this.form.submit()" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm text-white"><option value="">-- Pilih --</option><?php foreach ($kelas_list as $k): ?><option value="<?= $k['id_kelas'] ?>" <?= $kelas==$k['id_kelas']?'selected':'' ?>><?= clean($k['nama_kelas']) ?></option><?php endforeach; ?></select></div>
    <?php if ($kelas): ?>
    <a href="com_laporan/cetak_absensi.php?tgl=<?= $tgl ?>&kelas=<?= $kelas ?>" target="_blank" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-sm transition-all shadow-lg shadow-purple-600/20"><i class="fas fa-print mr-2"></i>Cetak</a>
    <button type="button" onclick="openModal('modalTambah')" class="bg-emerald-600 hover:bg-emerald-700 px-4 py-2 rounded-lg text-sm transition-all shadow-lg shadow-emerald-600/20"><i class="fas fa-plus mr-2"></i>Tambah Absen</button>
    <?php endif; ?>
</form></div>

<?php if ($data): ?>
<div class="glass rounded-xl p-5 border border-white/5 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/5 rounded-full blur-3xl"></div>
    <div class="flex items-center justify-between mb-4 relative z-10">
        <h3 class="text-sm font-bold text-white"><i class="fas fa-list mr-2 text-blue-400"></i>Data Kehadiran - <?= tgl_indo($tgl) ?></h3>
        <span class="text-xs px-2 py-1 rounded bg-blue-600/20 text-blue-400 font-bold"><?= count($data) ?> Siswa</span>
    </div>

    <div class="table-container relative z-10"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-400 border-b border-white/10 italic font-medium"><th class="pb-3 px-2">#</th><th class="pb-3">Siswa</th><th class="pb-3 text-center">Masuk/Keluar</th><th class="pb-3 text-center">Metode</th><th class="pb-3">Keterangan</th><th class="pb-3">Bukti & Lokasi</th><th class="pb-3 text-right">Aksi</th></tr></thead>
        <tbody class="divide-y divide-white/5"><?php foreach ($data as $i => $r): 
            $colors = [
                'Tepat Waktu' => 'text-emerald-400',
                'Terlambat' => 'text-amber-400',
                'Izin' => 'text-blue-400',
                'Sakit' => 'text-blue-400',
                'Pulang Cepat' => 'text-orange-400'
            ];
            $status_color = $colors[$r['keterangan']] ?? 'text-rose-400';
            $metode_color = [
                'RFID' => 'bg-purple-500/20 text-purple-400',
                'QR-Code' => 'bg-blue-500/20 text-blue-400',
                'Manual' => 'bg-slate-500/20 text-slate-400'
            ];
            $m_color = $metode_color[$r['metode']] ?? 'bg-slate-500/20 text-slate-400';
        ?>
        <tr class="hover:bg-white/5 transition-all group">
            <td class="py-3 px-2 text-slate-500"><?= $i+1 ?></td>
            <td class="font-bold text-white">
                <?= clean($r['nama']) ?>
                <p class="text-[9px] text-slate-500 font-normal"><?= clean($r['nisn']) ?></p>
            </td>
            <td class="text-center">
                <div class="flex flex-col">
                    <span class="font-mono text-[10px] text-emerald-400"><?= $r['jam_masuk']?substr($r['jam_masuk'],0,5):'--:--' ?></span>
                    <span class="font-mono text-[10px] text-amber-400"><?= $r['jam_keluar']?substr($r['jam_keluar'],0,5):'--:--' ?></span>
                </div>
            </td>
            <td class="text-center">
                <span class="text-[9px] px-1.5 py-0.5 rounded font-bold <?= $m_color ?>"><?= $r['metode'] ?: 'Manual' ?></span>
            </td>
            <td>
                <span class="text-xs font-bold <?= $status_color ?>"><?= $r['keterangan'] ?></span>
            </td>
            <td>
                <div class="flex gap-2">
                    <?php if($r['foto']): ?>
                        <button onclick="viewBukti('<?= $r['foto'] ?>')" class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-blue-400 hover:bg-blue-600 hover:text-white transition-all shadow-lg" title="Lihat Foto"><i class="fas fa-camera text-xs"></i></button>
                    <?php endif; ?>
                    
                    <?php if($r['lat'] && $r['lng']): ?>
                        <a href="https://www.google.com/maps?q=<?= $r['lat'] ?>,<?= $r['lng'] ?>" target="_blank" class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-rose-400 hover:bg-rose-600 hover:text-white transition-all shadow-lg" title="Lihat Lokasi GPS"><i class="fas fa-map-marker-alt text-xs"></i></a>
                    <?php endif; ?>

                    <?php if($r['surat_dokter']): ?>
                        <a href="<?= BASE_URL ?>assets/uploads/absensi_bukti/<?= $r['surat_dokter'] ?>" target="_blank" class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-amber-400 hover:bg-amber-600 hover:text-white transition-all shadow-lg" title="Lihat Surat Dokter"><i class="fas fa-file-medical text-xs"></i></a>
                    <?php endif; ?>
                </div>
            </td>
            <td class="text-right whitespace-nowrap">
                    <button onclick="confirmDelete('?hapus=<?= $r['id'] ?>&tgl=<?= $tgl ?>&kelas=<?= $kelas ?>')" class="text-slate-500 hover:text-rose-500 transition-colors p-1"><i class="fas fa-trash-alt text-xs"></i></button>
            </td>
        </tr>
        <?php endforeach; ?></tbody>
    </table></div>
</div>

<?php endif; ?>

<!-- Modal Tambah Manual -->
<div id="modalTambah" class="fixed inset-0 z-[100] flex items-center justify-center px-4 hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModal('modalTambah')"></div>
    <div class="relative z-10 w-full max-w-md bg-slate-900 border border-white/10 rounded-2xl p-6 shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-4">Tambah Absen Manual</h3>
        <form method="POST">
            <input type="hidden" name="tambah_manual" value="1">
            <div class="mb-4">
                <label class="block text-xs text-slate-400 mb-1">Pilih Siswa</label>
                <select name="id_siswa" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                    <option value="">-- Pilih Siswa --</option>
                    <?php foreach ($list_siswa as $ls): 
                        // Cek jika sudah absen
                        $sudah = false;
                        foreach($data as $d) { if($d['id_siswa'] == $ls['id_siswa']) { $sudah = true; break; } }
                        if(!$sudah):
                    ?>
                        <option value="<?= $ls['id_siswa'] ?>"><?= clean($ls['nama']) ?></option>
                    <?php endif; endforeach; ?>
                </select>
            </div>
            <div class="mb-6">
                <label class="block text-xs text-slate-400 mb-1">Keterangan</label>
                <select name="keterangan" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm text-white">
                    <option value="Tepat Waktu">Tepat Waktu</option>
                    <option value="Terlambat">Terlambat</option>
                    <option value="Izin">Izin</option>
                    <option value="Sakit">Sakit</option>
                </select>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeModal('modalTambah')" class="flex-1 px-4 py-2 bg-white/5 hover:bg-white/10 rounded-lg text-sm text-white transition-all">Batal</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg text-sm text-white font-bold transition-all shadow-lg shadow-emerald-600/20">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

function viewBukti(file) {
    document.getElementById('imgBukti').src = '<?= BASE_URL ?>assets/uploads/absensi_bukti/' + file;
    openModal('modalBukti');
}
function confirmDelete(url) {
    if (confirm('Hapus data absensi ini?')) window.location.href = url;
}
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
