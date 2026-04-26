<?php
$page_title = 'Data Guru';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin','kepsek']);

// Ambil list kelas untuk dropdown Walikelas
$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();

// --- CSV TEMPLATE GENERATOR ---
if (isset($_GET['template_csv'])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_guru.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'NIP', 'NUPTK', 'Nama Lengkap', 'JK (L/P)', 'Agama', 'Tempat Lahir', 'Tgl Lahir (YYYY-MM-DD)', 
        'Alamat', 'No HP', 'Pendidikan Terakhir', 'Jurusan', 'Tugas Tambahan', 'UUID RFID', 'Username', 'Password'
    ]);
    fputcsv($output, [
        '198001012005011001', '12345678', 'Ahmad Dani, S.Pd', 'L', 'Islam', 'Surabaya', '1980-01-01', 
        'Jl. Pahlawan No 10', '08123456789', 'S1', 'Pendidikan Matematika', 'Waka Kurikulum', '', 'ahmad', '123456'
    ]);
    fclose($output);
    exit;
}

// --- CSV IMPORT LOGIC ---
if (isset($_POST['import'])) {
    if (isset($_FILES['file_csv']['tmp_name']) && is_uploaded_file($_FILES['file_csv']['tmp_name'])) {
        $file = fopen($_FILES['file_csv']['tmp_name'], "r");
        $header = fgetcsv($file); // skip header
        
        $count = 0;
        while (($d = fgetcsv($file)) !== FALSE) {
            if (empty($d[2])) continue; // Nama is required
            
            $pw = password_hash(empty($d[14]) ? '123456' : $d[14], PASSWORD_DEFAULT);
            $jk = strtoupper(trim($d[3] ?? 'L'));
            if (!in_array($jk, ['L','P'])) $jk = 'L';
            
            $agama = trim($d[4] ?? 'Islam');
            if (!in_array($agama, ['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'])) $agama = 'Islam';

            $stmt = $pdo->prepare("INSERT INTO tbl_guru (nip,nuptk,nama,jk,agama,tempat_lahir,tanggal_lahir,alamat,no_hp,pendidikan_terakhir,jurusan,tugas_tambahan,uuid_kartu,username,password) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $d[0]??'', $d[1]??'', $d[2]??'', $jk, $agama, $d[5]??'', empty($d[6])?null:$d[6],
                $d[7]??'', $d[8]??'', $d[9]??'', $d[10]??'', $d[11]??'', $d[12]??'', $d[13]??'', $pw
            ]);
            $count++;
        }
        fclose($file);
        flash('msg', "$count data guru berhasil diimport! Atur jabatan Wali Kelas secara manual via Edit."); header('Location: guru.php'); exit;
    }
}

// --- CRUD LOGIC ---
if (isset($_POST['simpan'])) {
    $tugas_val = $_POST['tugas_select'] ?? '';
    if ($tugas_val === 'Lainnya') $tugas_val = $_POST['tugas_lainnya'] ?? '';
    $id_kelas_wali = !empty($_POST['id_kelas_wali']) ? $_POST['id_kelas_wali'] : null;

    $foto = upload_file('foto', 'gambar', ['jpg','jpeg','png']) ?? 'default.png';
    $pw = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO tbl_guru (nip,nuptk,nama,jk,agama,tempat_lahir,tanggal_lahir,alamat,no_hp,pendidikan_terakhir,jurusan,tugas_tambahan,id_kelas_wali,uuid_kartu,foto,username,password,is_bk,status,tmt) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $_POST['nip'], $_POST['nuptk'], $_POST['nama'], $_POST['jk'], $_POST['agama'], $_POST['tempat_lahir'], empty($_POST['tanggal_lahir'])?null:$_POST['tanggal_lahir'], $_POST['alamat'], $_POST['no_hp'], $_POST['pendidikan_terakhir'], $_POST['jurusan'], $tugas_val, $id_kelas_wali, $_POST['uuid_kartu'], $foto, $_POST['username'], $pw, $_POST['is_bk']??0, $_POST['status']??'Aktif', empty($_POST['tmt'])?null:$_POST['tmt']
        ]);
    flash('msg', 'Guru berhasil ditambahkan!'); header('Location: guru.php'); exit;
}

if (isset($_POST['update'])) {
    $tugas_val = $_POST['tugas_select'] ?? '';
    if ($tugas_val === 'Lainnya') $tugas_val = $_POST['tugas_lainnya'] ?? '';
    $id_kelas_wali = !empty($_POST['id_kelas_wali']) ? $_POST['id_kelas_wali'] : null;

    $foto_baru = upload_file('foto', 'gambar', ['jpg','jpeg','png']);
    $sql = "UPDATE tbl_guru SET nip=?,nuptk=?,nama=?,jk=?,agama=?,tempat_lahir=?,tanggal_lahir=?,alamat=?,no_hp=?,pendidikan_terakhir=?,jurusan=?,tugas_tambahan=?,id_kelas_wali=?,uuid_kartu=?,username=?,is_bk=?,status=?,tmt=?";
    $params = [
        $_POST['nip'], $_POST['nuptk'], $_POST['nama'], $_POST['jk'], $_POST['agama'], $_POST['tempat_lahir'], empty($_POST['tanggal_lahir'])?null:$_POST['tanggal_lahir'], $_POST['alamat'], $_POST['no_hp'], $_POST['pendidikan_terakhir'], $_POST['jurusan'], $tugas_val, $id_kelas_wali, $_POST['uuid_kartu'], $_POST['username'], $_POST['is_bk']??0, $_POST['status']??'Aktif', empty($_POST['tmt'])?null:$_POST['tmt']
    ];
    if ($foto_baru) { $sql .= ",foto=?"; $params[] = $foto_baru; }
    if (!empty($_POST['password'])) { $sql .= ",password=?"; $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT); }
    $sql .= " WHERE id_guru=?"; $params[] = $_POST['id'];
    $pdo->prepare($sql)->execute($params);
    flash('msg', 'Berhasil diupdate!'); header('Location: guru.php'); exit;
}

if (isset($_GET['hapus'])) { $pdo->prepare("DELETE FROM tbl_guru WHERE id_guru=?")->execute([$_GET['hapus']]); flash('msg','Dihapus!','warning'); header('Location: guru.php'); exit; }

$data = $pdo->query("SELECT g.*, k.nama_kelas FROM tbl_guru g LEFT JOIN tbl_kelas k ON g.id_kelas_wali=k.id_kelas ORDER BY g.nama")->fetchAll();
if (isset($_GET['edit'])) { $stmt = $pdo->prepare("SELECT * FROM tbl_guru WHERE id_guru=?"); $stmt->execute([(int)$_GET['edit']]); $edit = $stmt->fetch(); } else { $edit = null; }

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<!-- Action Buttons -->
<div class="mb-4 flex flex-wrap gap-2">
    <button onclick="document.getElementById('formGuru').classList.toggle('hidden')" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
        <i class="fas fa-plus mr-1"></i><?= $edit ? 'Edit Guru' : 'Tambah Guru' ?>
    </button>
    <button onclick="document.getElementById('importModal').classList.remove('hidden')" class="bg-emerald-600 hover:bg-emerald-500 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
        <i class="fas fa-file-excel mr-1"></i>Import CSV
    </button>
</div>

<!-- Modal Import CSV -->
<div id="importModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4">
    <div class="glass w-full max-w-md rounded-2xl p-6 border border-white/10 shadow-2xl relative">
        <button onclick="document.getElementById('importModal').classList.add('hidden')" class="absolute top-4 right-4 text-slate-400 hover:text-white"><i class="fas fa-times"></i></button>
        <h3 class="text-lg font-bold mb-2">Import Data Guru (CSV)</h3>
        <p class="text-sm text-slate-400 mb-6">Gunakan format CSV yang sudah disediakan untuk import data masal.</p>
        
        <a href="?template_csv=1" download="template_guru.csv" class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-700 border border-white/10 px-4 py-2 rounded-lg text-sm transition-colors w-full justify-center mb-4">
            <i class="fas fa-download text-emerald-400"></i> Download Template CSV
        </a>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="document.getElementById('loadingOverlay').classList.remove('hidden')">
            <div>
                <label class="block text-sm font-medium mb-2">Upload File .csv</label>
                <input type="file" name="file_csv" accept=".csv" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none file:mr-4 file:py-1 file:px-3 file:rounded file:border-0 file:bg-blue-500/20 file:text-blue-400 file:text-xs">
            </div>
            <button type="submit" name="import" class="w-full bg-emerald-600 hover:bg-emerald-500 py-2.5 rounded-lg text-sm font-bold transition-all"><i class="fas fa-upload mr-2"></i>Mulai Import</button>
        </form>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="hidden fixed inset-0 z-[60] flex flex-col items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="w-16 h-16 border-4 border-slate-700 border-t-emerald-500 rounded-full animate-spin mb-4"></div>
    <h3 class="text-white text-lg font-bold animate-pulse tracking-wide">Mengimport Data...</h3>
    <p class="text-slate-400 text-sm mt-2">Mohon jangan tutup halaman ini.</p>
</div>

<div id="formGuru" class="<?= $edit?'':'hidden' ?> glass rounded-xl p-5 mb-6 border border-white/5">
    <form method="POST" enctype="multipart/form-data">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id_guru'] ?>"><?php endif; ?>
        
        <h4 class="text-sm font-bold border-b border-white/10 pb-2 mb-4 text-blue-400"><i class="fas fa-user-tie mr-2"></i>A. Identitas Utama</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <?php foreach ([['NIP','nip','text',false],['NUPTK','nuptk','text',false],['Nama Lengkap (+Gelar)','nama','text',true],['Username','username','text',true],['Password'.($edit?' (kosongkan jika tetap)':''),'password','password',false],['Tempat Lahir','tempat_lahir','text',false],['Tanggal Lahir','tanggal_lahir','date',false],['TMT (Terhitung Mulai Tanggal)','tmt','date',false],['No. HP / WA','no_hp','text',false],['UUID Kartu RFID','uuid_kartu','text',false]] as $f): ?>
            <div><label class="block text-xs text-slate-400 mb-1"><?= $f[0] ?> <?= $f[3]?'<span class="text-red-400">*</span>':'' ?></label>
                <input type="<?= $f[2] ?>" name="<?= $f[1] ?>" value="<?= $f[1] !== 'password' ? clean($edit[$f[1]] ?? '') : '' ?>" <?= $f[3]?'required':'' ?> class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </div>
            <?php endforeach; ?>
            <div><label class="block text-xs text-slate-400 mb-1">Jenis Kelamin</label>
                <select name="jk" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                    <option value="L" <?= ($edit['jk']??'')=='L'?'selected':'' ?>>Laki-laki</option>
                    <option value="P" <?= ($edit['jk']??'')=='P'?'selected':'' ?>>Perempuan</option>
                </select>
            </div>
            <div><label class="block text-xs text-slate-400 mb-1">Agama</label>
                <select name="agama" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                    <?php foreach (['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $a): ?><option <?= ($edit['agama']??'')==$a?'selected':'' ?>><?= $a ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-1"><label class="block text-xs text-slate-400 mb-1">Alamat Domisili</label>
                <input type="text" name="alamat" value="<?= clean($edit['alamat'] ?? '') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </div>
        </div>

        <h4 class="text-sm font-bold border-b border-white/10 pb-2 mb-4 text-emerald-400"><i class="fas fa-graduation-cap mr-2"></i>B. Akademik & Tugas Khusus</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 relative">
            <div><label class="block text-xs text-slate-400 mb-1">Pendidikan Terakhir</label>
                <select name="pendidikan_terakhir" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                    <option value="">-- Pilih --</option>
                    <?php foreach (['SMA/SMK','D3','D4','S1','S2','S3'] as $a): ?><option <?= ($edit['pendidikan_terakhir']??'')==$a?'selected':'' ?>><?= $a ?></option><?php endforeach; ?>
                </select>
            </div>
            <div><label class="block text-xs text-slate-400 mb-1">Jurusan / Program Studi</label>
                <input type="text" name="jurusan" value="<?= clean($edit['jurusan'] ?? '') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </div>

            <!-- TUGAS TAMBAHAN KHUSUS -->
            <div class="col-span-1 border border-emerald-500/20 p-3 rounded-lg bg-emerald-500/5 relative row-span-2">
                <label class="block text-xs text-emerald-400 mb-1 font-bold"><i class="fas fa-star mr-1"></i>Tugas Khusus / Jabatan</label>
                <?php 
                    $tt = $edit['tugas_tambahan'] ?? ''; 
                    $isWakaKur = ($tt === 'Waka Kurikulum');
                    $isWakaKes = ($tt === 'Waka Kesiswaan');
                    $isWakaSar = ($tt === 'Waka Sarpras');
                    $isWakaHum = ($tt === 'Waka Humas');
                    $isWakaAga = ($tt === 'Waka Keagamaan');
                    $isWakaUks = ($tt === 'Waka UKS');
                    // Jika teks sebelumnya adalah Wali Kelas (karena dipisah), ganti ke tidak ada di select list
                    if ($tt === 'Wali Kelas') $tt = '';  
                    $isLainnya = ($tt && !$isWakaKur && !$isWakaKes && !$isWakaSar && !$isWakaHum && !$isWakaAga && !$isWakaUks);
                    $valSelect = $isWakaKur ? 'Waka Kurikulum' : ($isWakaKes ? 'Waka Kesiswaan' : ($isWakaSar ? 'Waka Sarpras' : ($isWakaHum ? 'Waka Humas' : ($isWakaAga ? 'Waka Keagamaan' : ($isWakaUks ? 'Waka UKS' : ($isLainnya ? 'Lainnya' : ''))))));
                ?>
                <select id="tugas_select" name="tugas_select" onchange="document.getElementById('wrapLainnya').classList.toggle('hidden', this.value !== 'Lainnya')" class="w-full bg-slate-900 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none shadow-inner mb-3">
                    <option value="">-- Tidak Ada --</option>
                    <option value="Waka Kurikulum" <?= $valSelect=='Waka Kurikulum'?'selected':'' ?>>Waka Kurikulum (Akses Admin Akademik)</option>
                    <option value="Waka Kesiswaan" <?= $valSelect=='Waka Kesiswaan'?'selected':'' ?>>Waka Kesiswaan (Akses Admin Kesiswaan)</option>
                    <option value="Waka Sarpras" <?= $valSelect=='Waka Sarpras'?'selected':'' ?>>Waka Sarpras (Akses Admin Sarpras)</option>
                    <option value="Waka Humas" <?= $valSelect=='Waka Humas'?'selected':'' ?>>Waka Humas (Akses Admin Humas)</option>
                    <option value="Waka Keagamaan" <?= $valSelect=='Waka Keagamaan'?'selected':'' ?>>Waka Keagamaan (Akses Admin Agama)</option>
                    <option value="Waka UKS" <?= $valSelect=='Waka UKS'?'selected':'' ?>>Waka UKS (Akses Admin UKS)</option>
                    <option value="Lainnya" <?= $valSelect=='Lainnya'?'selected':'' ?>>Lainnya (Ketik Manual)...</option>
                </select>

                <div id="wrapLainnya" class="<?= $valSelect=='Lainnya'?'':'hidden' ?> bg-black/20 p-2 rounded border border-white/5">
                    <label class="block text-[10px] text-amber-400 mb-1"><i class="fas fa-pen mr-1"></i>Tulis Tugas Lainnya</label>
                    <input type="text" name="tugas_lainnya" value="<?= $isLainnya ? clean($tt) : '' ?>" placeholder="Misal: Kepala Perpus" class="w-full bg-slate-800 border border-white/10 rounded px-2 py-1.5 text-xs focus:border-amber-500 focus:outline-none">
                </div>
            </div>
            <!-- END TUGAS -->

            <!-- WALI KELAS DITAMBAHKAN TERPISAH -->
            <div class="col-span-1 border border-blue-500/20 p-3 rounded-lg bg-blue-500/5 relative">
                <label class="block text-xs text-blue-400 mb-1 font-bold"><i class="fas fa-school mr-1"></i>Penugasan Wali Kelas</label>
                <select name="id_kelas_wali" class="w-full bg-slate-900 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none shadow-inner">
                    <option value="">-- Bukan Wali Kelas --</option>
                    <?php foreach($kelas_list as $k): ?><option value="<?= $k['id_kelas'] ?>" <?= ($edit['id_kelas_wali']??'')==$k['id_kelas']?'selected':'' ?>><?= clean($k['nama_kelas']) ?></option><?php endforeach; ?>
                </select>
                <p class="text-[10px] text-slate-500 mt-1">Jika kelas dipilih, guru ini berfungsi sebagai Wali Kelas-nya.</p>
            </div>

            <div><label class="block text-xs text-slate-400 mb-1">Guru BK?</label>
                <select name="is_bk" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                    <option value="0">Bukan Guru BK</option>
                    <option value="1" <?= ($edit['is_bk']??0)==1?'selected':'' ?>>Ya, Guru BK</option>
                </select>
            </div>
            <div><label class="block text-xs text-slate-400 mb-1">Status Keaktifan</label>
                <select name="status" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                    <option value="Aktif" <?= ($edit['status']??'Aktif')=='Aktif'?'selected':'' ?>>Aktif Mengajar</option>
                    <option value="Nonaktif" <?= ($edit['status']??'')=='Nonaktif'?'selected':'' ?>>Nonaktif / Resign</option>
                </select>
            </div>
            <div><label class="block text-xs text-slate-400 mb-1">Foto Profil</label>
                <input type="file" name="foto" accept="image/*" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-1 text-sm file:mr-3 file:bg-blue-600 file:text-white file:border-0 file:rounded file:px-2 file:py-1 file:text-xs">
            </div>
        </div>

        <div class="flex gap-2 border-t border-white/10 pt-4 mt-2">
            <button type="submit" name="<?= $edit?'update':'simpan' ?>" class="bg-blue-600 hover:bg-blue-500 px-8 py-2.5 rounded-lg text-sm font-bold transition-colors"><i class="fas fa-save mr-2"></i><?= $edit?'Simpan Perubahan':'Tambahkan Guru' ?></button>
            <?php if ($edit): ?><a href="guru.php" class="bg-slate-600 hover:bg-slate-500 px-6 py-2.5 rounded-lg text-sm font-bold transition-colors">Batal</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="glass rounded-xl p-5 border border-white/5"><div class="table-container"><table class="w-full text-sm">
    <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">NIP/NUPTK</th><th class="pb-3">Nama</th><th class="pb-3">JK</th><th class="pb-3">TMT</th><th class="pb-3">Tugas & Jabatan</th><th class="pb-3">Status</th><th class="pb-3">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($data as $i => $r): ?>
    <tr class="border-b border-white/5 hover:bg-white/5">
        <td class="py-2.5"><?= $i+1 ?></td>
        <td class="font-mono text-xs">
            <span class="block text-slate-300"><?= clean($r['nip'] ?: '-') ?></span>
            <span class="block text-[10px] text-slate-500 mt-0.5" title="NUPTK"><?= clean($r['nuptk'] ?: '-') ?></span>
        </td>
        <td class="font-medium text-blue-400"><?= clean($r['nama']) ?></td>
        <td><?= $r['jk'] ?></td>
        <td class="text-xs font-mono text-slate-400"><?= $r['tmt'] ? tgl_indo($r['tmt']) : '<span class="text-rose-400 italic">-</span>' ?></td>
        <td class="text-xs text-slate-400 space-y-1">
            <?php if (in_array($r['tugas_tambahan'], ['Waka Kurikulum', 'Waka Kesiswaan'])): ?>
                <span class="inline-block px-2 py-0.5 bg-blue-500/20 text-blue-400 rounded text-[10px]"><i class="fas fa-star mr-1"></i><?= clean($r['tugas_tambahan']) ?></span>
            <?php elseif ($r['tugas_tambahan'] === 'Waka Sarpras'): ?>
                <span class="inline-block px-2 py-0.5 bg-indigo-500/20 text-indigo-400 rounded text-[10px]"><i class="fas fa-boxes mr-1"></i><?= clean($r['tugas_tambahan']) ?></span>
            <?php elseif ($r['tugas_tambahan'] === 'Waka Humas'): ?>
                <span class="inline-block px-2 py-0.5 bg-rose-500/20 text-rose-400 rounded text-[10px]"><i class="fas fa-handshake mr-1"></i><?= clean($r['tugas_tambahan']) ?></span>
            <?php elseif ($r['tugas_tambahan'] === 'Waka Keagamaan'): ?>
                <span class="inline-block px-2 py-0.5 bg-teal-500/20 text-teal-400 rounded text-[10px]"><i class="fas fa-mosque mr-1"></i><?= clean($r['tugas_tambahan']) ?></span>
            <?php elseif ($r['tugas_tambahan'] === 'Waka UKS'): ?>
                <span class="inline-block px-2 py-0.5 bg-emerald-500/20 text-emerald-400 rounded text-[10px]"><i class="fas fa-hand-holding-medical mr-1"></i><?= clean($r['tugas_tambahan']) ?></span>
            <?php elseif ($r['tugas_tambahan'] && $r['tugas_tambahan'] !== 'Wali Kelas'): ?>
                <span class="inline-block px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-[10px]"><i class="fas fa-pen mr-1"></i><?= clean($r['tugas_tambahan']) ?></span>
            <?php endif; ?>

            <?php if ($r['id_kelas_wali']): ?>
                <span class="inline-block px-2 py-0.5 bg-emerald-500/20 text-emerald-400 rounded text-[10px]"><i class="fas fa-school mr-1"></i>Wali Kelas <?= clean($r['nama_kelas']??'') ?></span>
            <?php endif; ?>
            
            <?php if (!$r['tugas_tambahan'] && !$r['id_kelas_wali'] && !$r['is_bk']): ?>
                 <span class="text-slate-500">-</span>
            <?php endif; ?>

            <?php if($r['is_bk']): ?>
                <span class="inline-block px-2 py-0.5 bg-purple-500/20 text-purple-400 rounded text-[10px]"><i class="fas fa-shield-alt mr-1"></i>Guru BK</span>
            <?php endif; ?>
        </td>
        <td><span class="px-2 py-0.5 rounded-full text-[10px] uppercase font-bold <?= $r['status']=='Aktif'?'bg-emerald-500/20 text-emerald-400':'bg-red-500/20 text-red-400' ?>"><?= $r['status'] ?></span></td>
        <td class="flex gap-1">
            <a href="?edit=<?= $r['id_guru'] ?>" class="p-1.5 rounded bg-blue-600/20 text-blue-400 hover:bg-blue-600/40 text-xs"><i class="fas fa-edit"></i></a>
            <button onclick="confirmDelete('?hapus=<?= $r['id_guru'] ?>','<?= clean($r['nama']) ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 hover:bg-red-600/40 text-xs"><i class="fas fa-trash"></i></button>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if(!$data): ?><tr><td colspan="8" class="text-center py-4 text-slate-400">Data guru kosong.</td></tr><?php endif; ?>
    </tbody>
</table></div></div>

<script>
// Validasi file csv size < 5MB
document.querySelector('form[enctype="multipart/form-data"]').addEventListener('submit', function(e) {
    const file = this.querySelector('input[type="file"][accept=".csv"]');
    if (file && file.files[0] && file.files[0].size > 5 * 1024 * 1024) {
        alert('File CSV terlalu besar. Maksimal 5MB.');
        e.preventDefault();
    }
});
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
