<?php
$page_title = 'Data Siswa';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin','kepsek']);

// --- CSV TEMPLATE GENERATOR ---
if (isset($_GET['template_csv'])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_siswa.csv"');
    $output = fopen('php://output', 'w');
    // Header
    fputcsv($output, [
        'NISN', 'NIS', 'Nama Lengkap', 'JK (L/P)', 'Tempat Lahir', 'Tgl Lahir (YYYY-MM-DD)', 
        'Nama Kelas', 'Username', 'Password', 'Alamat', 
        'HP Ortu', 'HP Siswa', 'Nama Ayah', 'Nama Ibu', 'Pekerjaan Ayah', 'Pekerjaan Ibu', 
        'Penghasilan Ortu', 'Status Keluarga', 'Kategori'
    ]);
    // Example row
    fputcsv($output, [
        '00123456', '1234', 'Budi Santoso', 'L', 'Jakarta', '2010-05-15', 
        'X IPA 1', 'budi', '123456', 'Jl. Sudirman No 1', 
        '0812345', '0812346', 'Anton', 'Siti', 'PNS', 'Wiraswasta', 
        '5.000.000', 'Lengkap', 'Reguler'
    ]);
    fputcsv($output, [
        '', '', 'Contoh Status Keluarga: Lengkap / Yatim / Piatu / Yatim Piatu', '', '', '', 
        '', '', '', '', 
        '', '', '', '', '', '', 
        '', 'Contoh Kategori: Reguler / Yatim / Piatu / Yatim Piatu / Prestasi / Miskin', ''
    ]);
    fclose($output);
    exit;
}

// --- CSV IMPORT LOGIC ---
if (isset($_POST['import'])) {
    if (isset($_FILES['file_csv']['tmp_name']) && is_uploaded_file($_FILES['file_csv']['tmp_name'])) {
        $file = fopen($_FILES['file_csv']['tmp_name'], "r");
        $header = fgetcsv($file); // skip header line
        
        // Caching kelas to translate name to ID
        $kls = $pdo->query("SELECT id_kelas, nama_kelas FROM tbl_kelas")->fetchAll(PDO::FETCH_KEY_PAIR);
        $kls_rev = array_flip($kls);
        
        $count = 0;
        while (($d = fgetcsv($file)) !== FALSE) {
            // Minimal nama ada
            if (empty($d[2]) || $d[2] == 'Contoh Status Keluarga: Lengkap / Yatim / Piatu / Yatim Piatu') continue; 
            
            $id_kls = null;
            if (!empty($d[6]) && isset($kls_rev[$d[6]])) {
                $id_kls = $kls_rev[$d[6]];
            }
            
            $pw = password_hash(empty($d[8]) ? '123456' : $d[8], PASSWORD_DEFAULT);
            $jk = strtoupper(trim($d[3] ?? 'L'));
            if (!in_array($jk, ['L','P'])) $jk = 'L';

            $stat_kel = ucfirst(trim($d[17] ?? 'Lengkap'));
            if (!in_array($stat_kel, ['Lengkap','Yatim','Piatu','Yatim Piatu'])) $stat_kel = 'Lengkap';

            $kat = ucfirst(trim($d[18] ?? 'Reguler'));
            if (!in_array($kat, ['Reguler', 'Yatim', 'Piatu', 'Yatim Piatu', 'Prestasi', 'Miskin'])) $kat = 'Reguler';

            $stmt = $pdo->prepare("INSERT INTO tbl_siswa (nisn,nis,nama,jk,tempat_lahir,tanggal_lahir,id_kelas,username,password,alamat,no_hp_ortu,no_hp_siswa,nama_ayah,nama_ibu,pekerjaan_ayah,pekerjaan_ibu,penghasilan_ortu,status_keluarga,kategori) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $d[0]??'', $d[1]??'', $d[2]??'', $jk, $d[4]??'', empty($d[5])?null:$d[5],
                $id_kls, $d[7]??'', $pw, $d[9]??'', $d[10]??'', $d[11]??'',
                $d[12]??'', $d[13]??'', $d[14]??'', $d[15]??'', $d[16]??'',
                $stat_kel, $kat
            ]);
            $count++;
        }
        fclose($file);
        flash('msg', "$count data siswa berhasil diimport!"); header('Location: siswa.php'); exit;
    }
}

// --- CRUD LOGIC ---
if (isset($_POST['simpan'])) {
    $foto = upload_file('foto', 'foto_siswa', ['jpg','jpeg','png']) ?? 'default.png';
    $pw = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO tbl_siswa (nisn,nis,nama,jk,tempat_lahir,tanggal_lahir,alamat,no_hp_ortu,no_hp_siswa,uuid_kartu,foto,username,password,id_kelas,nama_ayah,nama_ibu,pekerjaan_ayah,pekerjaan_ibu,penghasilan_ortu,status_keluarga,kategori,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['nisn'], $_POST['nis'], $_POST['nama'], $_POST['jk'], $_POST['tempat_lahir'], empty($_POST['tanggal_lahir'])?null:$_POST['tanggal_lahir'], $_POST['alamat'], $_POST['no_hp_ortu'], $_POST['no_hp_siswa'], $_POST['uuid_kartu'], $foto, $_POST['username'], $pw, $_POST['id_kelas'],
        $_POST['nama_ayah'], $_POST['nama_ibu'], $_POST['pekerjaan_ayah'], $_POST['pekerjaan_ibu'], $_POST['penghasilan_ortu'], $_POST['status_keluarga'], $_POST['kategori'], $_POST['status']
    ]);
    flash('msg', 'Siswa berhasil ditambahkan!'); header('Location: siswa.php'); exit;
}

if (isset($_POST['update'])) {
    $foto_baru = upload_file('foto', 'foto_siswa', ['jpg','jpeg','png']);
    $sql = "UPDATE tbl_siswa SET nisn=?,nis=?,nama=?,jk=?,tempat_lahir=?,tanggal_lahir=?,alamat=?,no_hp_ortu=?,no_hp_siswa=?,uuid_kartu=?,id_kelas=?,username=?,nama_ayah=?,nama_ibu=?,pekerjaan_ayah=?,pekerjaan_ibu=?,penghasilan_ortu=?,status_keluarga=?,kategori=?,status=?";
    $params = [
        $_POST['nisn'], $_POST['nis'], $_POST['nama'], $_POST['jk'], $_POST['tempat_lahir'], empty($_POST['tanggal_lahir'])?null:$_POST['tanggal_lahir'], $_POST['alamat'], $_POST['no_hp_ortu'], $_POST['no_hp_siswa'], $_POST['uuid_kartu'], $_POST['id_kelas'], $_POST['username'],
        $_POST['nama_ayah'], $_POST['nama_ibu'], $_POST['pekerjaan_ayah'], $_POST['pekerjaan_ibu'], $_POST['penghasilan_ortu'], $_POST['status_keluarga'], $_POST['kategori'], $_POST['status']
    ];
    if ($foto_baru) { $sql .= ",foto=?"; $params[] = $foto_baru; }
    if (!empty($_POST['password'])) { $sql .= ",password=?"; $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT); }
    $sql .= " WHERE id_siswa=?"; $params[] = $_POST['id'];
    $pdo->prepare($sql)->execute($params);
    flash('msg', 'Berhasil diupdate!'); header('Location: siswa.php'); exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_siswa WHERE id_siswa=?")->execute([$_GET['hapus']]);
    flash('msg', 'Berhasil dihapus!', 'warning'); header('Location: siswa.php'); exit;
}

$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();
$filter_kelas = $_GET['kelas'] ?? '';
$search = $_GET['q'] ?? '';

$where = "WHERE 1=1";
$params = [];
if ($filter_kelas) { $where .= " AND s.id_kelas=?"; $params[] = $filter_kelas; }
if ($search) { $where .= " AND (s.nama LIKE ? OR s.nisn LIKE ? OR s.nis LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$page = max(1, (int)($_GET['p'] ?? 1));
$result = paginate($pdo, "SELECT s.*, k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas $where ORDER BY s.nama", $params, $page, 15);

if (isset($_GET['edit'])) { $stmt = $pdo->prepare("SELECT * FROM tbl_siswa WHERE id_siswa=?"); $stmt->execute([(int)$_GET['edit']]); $edit = $stmt->fetch(); } else { $edit = null; }

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<!-- Action Buttons -->
<div class="mb-4 flex flex-wrap gap-2">
    <button onclick="document.getElementById('formSiswa').classList.toggle('hidden')" class="bg-blue-600 hover:bg-blue-500 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
        <i class="fas fa-plus mr-1"></i><?= $edit ? 'Edit Siswa' : 'Tambah Siswa' ?>
    </button>
    <button onclick="document.getElementById('importModal').classList.remove('hidden')" class="bg-emerald-600 hover:bg-emerald-500 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
        <i class="fas fa-file-excel mr-1"></i>Import CSV
    </button>
    <div class="relative group ml-auto">
        <button type="button" class="bg-amber-600 hover:bg-amber-500 px-4 py-2 rounded-lg text-sm font-bold transition-all flex items-center gap-2">
            <i class="fas fa-print"></i> Aksi Cetak Masal <i class="fas fa-chevron-down text-[10px]"></i>
        </button>
        <div class="absolute right-0 top-full mt-1 w-56 bg-slate-800 border border-white/10 rounded-xl shadow-2xl py-2 hidden group-hover:block z-[50]">
            <button type="button" onclick="bulkPrint('card')" class="w-full text-left px-4 py-2 text-xs hover:bg-white/10 transition-colors flex items-center gap-2"><i class="fas fa-id-card text-blue-400"></i> Cetak Kartu (Terpilih)</button>
            <button type="button" onclick="bulkPrint('qr')" class="w-full text-left px-4 py-2 text-xs hover:bg-white/10 transition-colors flex items-center gap-2"><i class="fas fa-qrcode text-amber-400"></i> Cetak QR Saja (Terpilih)</button>
            <div class="border-t border-white/5 my-1"></div>
            <a href="cetak_kartu.php?kelas=<?= $filter_kelas ?>&type=card" target="_blank" class="w-full text-left px-4 py-2 text-[10px] text-slate-400 hover:text-white transition-colors flex items-center gap-2 italic"><i class="fas fa-users-viewfinder"></i> Cetak Seluruh Kelas (Card)</a>
            <a href="cetak_kartu.php?kelas=<?= $filter_kelas ?>&type=qr" target="_blank" class="w-full text-left px-4 py-2 text-[10px] text-slate-400 hover:text-white transition-colors flex items-center gap-2 italic"><i class="fas fa-border-all"></i> Cetak Seluruh Kelas (QR)</a>
        </div>
    </div>
</div>

<!-- Modal Import CSV -->
<div id="importModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4">
    <div class="glass w-full max-w-md rounded-2xl p-6 border border-white/10 shadow-2xl relative">
        <button onclick="document.getElementById('importModal').classList.add('hidden')" class="absolute top-4 right-4 text-slate-400 hover:text-white"><i class="fas fa-times"></i></button>
        <h3 class="text-lg font-bold mb-2">Import Data Siswa (CSV)</h3>
        <p class="text-sm text-slate-400 mb-6">Gunakan format CSV yang sudah disediakan untuk import data masal.</p>
        
        <a href="?template_csv=1" download="template_siswa.csv" class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-700 border border-white/10 px-4 py-2 rounded-lg text-sm transition-colors w-full justify-center mb-4">
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

<div id="formSiswa" class="<?= $edit ? '' : 'hidden' ?> glass rounded-xl p-5 mb-6 border border-white/5">
    <form method="POST" enctype="multipart/form-data">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id_siswa'] ?>"><?php endif; ?>
        
        <!-- Tabbed layout style using headers -->
        <h4 class="text-sm font-bold border-b border-white/10 pb-2 mb-4 text-blue-400"><i class="fas fa-user mr-2"></i>A. Data Pribadi & Login</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <?php foreach ([['NISN','nisn','text',true],['NIS','nis','text',false],['Nama Lengkap','nama','text',true],['Username','username','text',true],['Password'.($edit?' (kosongkan jika tetap)':''),'password','password',false],['Tempat Lahir','tempat_lahir','text',false],['Tanggal Lahir','tanggal_lahir','date',false],['No. HP Siswa','no_hp_siswa','text',false],['UUID Kartu RFID','uuid_kartu','text',false]] as $f): ?>
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
            <div><label class="block text-xs text-slate-400 mb-1">Kelas <span class="text-red-400">*</span></label>
                <select name="id_kelas" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                    <option value="">-- Pilih --</option>
                    <?php foreach ($kelas_list as $k): ?><option value="<?= $k['id_kelas'] ?>" <?= ($edit['id_kelas']??'')==$k['id_kelas']?'selected':'' ?>><?= clean($k['nama_kelas']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2"><label class="block text-xs text-slate-400 mb-1">Alamat</label>
                <input type="text" name="alamat" value="<?= clean($edit['alamat'] ?? '') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </div>
        </div>

        <h4 class="text-sm font-bold border-b border-white/10 pb-2 mb-4 text-emerald-400"><i class="fas fa-users mr-2"></i>B. Data Orang Tua / Wali</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <?php foreach ([['Nama Ayah','nama_ayah'],['Nama Ibu','nama_ibu'],['Pekerjaan Ayah','pekerjaan_ayah'],['Pekerjaan Ibu','pekerjaan_ibu'],['Penghasilan Ortu / Bulan','penghasilan_ortu'],['No. HP Ortu/Wali','no_hp_ortu']] as $f): ?>
            <div><label class="block text-xs text-slate-400 mb-1"><?= $f[0] ?></label>
                <input type="text" name="<?= $f[1] ?>" value="<?= clean($edit[$f[1]] ?? '') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </div>
            <?php endforeach; ?>
        </div>

        <h4 class="text-sm font-bold border-b border-white/10 pb-2 mb-4 text-purple-400"><i class="fas fa-tags mr-2"></i>C. Status & Kategori</h4>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div><label class="block text-xs text-slate-400 mb-1">Status Keaktifan</label>
                <select name="status" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                    <?php foreach(['Aktif','Lulus','Pindah','Keluar'] as $s): ?><option <?= ($edit['status']??'Aktif')==$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
                </select>
            </div>
            <div><label class="block text-xs text-slate-400 mb-1">Status Keluarga</label>
                <select name="status_keluarga" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                    <?php foreach(['Lengkap','Yatim','Piatu','Yatim Piatu'] as $s): ?><option <?= ($edit['status_keluarga']??'Lengkap')==$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
                </select>
            </div>
            <div><label class="block text-xs text-slate-400 mb-1">Kategori Siswa / Beasiswa</label>
                <select name="kategori" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                    <?php foreach(['Reguler', 'Yatim', 'Piatu', 'Yatim Piatu', 'Prestasi', 'Miskin'] as $s): ?><option <?= ($edit['kategori']??'Reguler')==$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
                </select>
            </div>
            <div><label class="block text-xs text-slate-400 mb-1">Ganti Foto Siswa</label>
                <input type="file" name="foto" accept="image/*" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-1 text-sm file:mr-3 file:bg-blue-600 file:text-white file:border-0 file:rounded file:px-2 file:py-1 file:text-xs">
            </div>
        </div>

        <div class="flex gap-2 border-t border-white/10 pt-4 mt-2">
            <button type="submit" name="<?= $edit ? 'update' : 'simpan' ?>" class="bg-blue-600 hover:bg-blue-500 px-8 py-2.5 rounded-lg text-sm font-bold transition-colors"><i class="fas fa-save mr-2"></i><?= $edit ? 'Simpan Perubahan' : 'Tambahkan Siswa' ?></button>
            <?php if ($edit): ?><a href="siswa.php" class="bg-slate-600 hover:bg-slate-500 px-6 py-2.5 rounded-lg text-sm font-bold transition-colors">Batal</a><?php endif; ?>
        </div>
    </form>
</div>

<!-- Filters -->
<div class="flex flex-wrap gap-3 mb-4">
    <form method="GET" class="flex flex-wrap gap-2 items-center">
        <select name="kelas" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" onchange="this.form.submit()">
            <option value="">Semua Kelas</option>
            <?php foreach ($kelas_list as $k): ?><option value="<?= $k['id_kelas'] ?>" <?= $filter_kelas==$k['id_kelas']?'selected':'' ?>><?= clean($k['nama_kelas']) ?></option><?php endforeach; ?>
        </select>
        <input type="text" name="q" value="<?= clean($search) ?>" placeholder="Cari nama/NISN..." class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
        <button class="bg-blue-600 hover:bg-blue-500 px-3 py-2 rounded-lg text-sm"><i class="fas fa-search"></i></button>
    </form>
    <span class="text-xs text-slate-400 self-center">Total: <?= $result['total'] ?> siswa</span>
</div>

<!-- Table -->
<div class="glass rounded-xl p-5 border border-white/5">
    <div class="table-container"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-400 border-b border-white/10">
            <th class="pb-3 w-8"><input type="checkbox" onclick="toggleSelectAll(this)" class="rounded border-white/10 bg-slate-800"></th>
            <th class="pb-3">#</th><th class="pb-3">NISN</th><th class="pb-3">Nama</th><th class="pb-3">Kelas</th><th class="pb-3">Kategori</th><th class="pb-3">Status</th><th class="pb-3">Aksi</th>
        </tr></thead>
        <tbody>
        <?php foreach ($result['data'] as $i => $r): 
            $kat_key = ucwords(strtolower($r['kategori'] ?? 'Reguler'));
            $colors = [
                'Reguler' => 'bg-slate-500/20 text-slate-300',
                'Prestasi' => 'bg-emerald-500/20 text-emerald-400',
                'Miskin' => 'bg-purple-500/20 text-purple-400',
                'Yatim' => 'bg-amber-500/20 text-amber-400',
                'Piatu' => 'bg-amber-500/20 text-amber-400',
                'Yatim Piatu' => 'bg-rose-500/20 text-rose-400'
            ];
            $kat_color = $colors[$kat_key] ?? 'bg-slate-500/20 text-slate-300';
        ?>
        <tr class="border-b border-white/5 hover:bg-white/5">
            <td class="py-2.5">
                <input type="checkbox" name="siswa_ids[]" value="<?= $r['id_siswa'] ?>" class="siswa-checkbox rounded border-white/10 bg-slate-800">
            </td>
            <td class="py-2.5"><?= ($page-1)*15+$i+1 ?></td>
            <td class="font-mono text-xs"><?= clean($r['nisn']) ?></td>
            <td class="font-medium text-blue-400">
                <?= clean($r['nama']) ?>
                <?= $r['status_keluarga']!='Lengkap' ? '<i class="fas fa-circle text-[6px] text-amber-500 ml-1" title="'.clean($r['status_keluarga']).'"></i>' : '' ?>
            </td>
            <td class="text-slate-300"><?= clean($r['nama_kelas'] ?? '-') ?></td>
            <td><span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-sm <?= $kat_color ?>"><?= empty($r['kategori']) ? 'REGULER' : clean($r['kategori']) ?></span></td>
            <td><span class="px-2 py-0.5 rounded-full text-xs <?= $r['status']=='Aktif'?'bg-emerald-500/20 text-emerald-400':'bg-red-500/20 text-red-400' ?>"><?= $r['status'] ?></span></td>
            <td class="flex gap-1">
                <div class="relative group">
                    <button type="button" class="p-1.5 rounded bg-amber-600/20 text-amber-500 hover:bg-amber-600/40 text-xs" title="Cetak"><i class="fas fa-print"></i></button>
                    <!-- Diturunkan sedikit dan ditambahkan Padding agar Hover lebih Lengket -->
                    <div class="absolute right-0 top-full pt-1 w-32 hidden group-hover:block z-[50]">
                        <div class="bg-slate-800 border border-white/10 rounded-lg shadow-2xl py-1 overflow-hidden">
                            <a href="cetak_kartu.php?id=<?= $r['id_siswa'] ?>&type=card" target="_blank" class="block px-3 py-2 text-[10px] hover:bg-white/10 transition-colors border-b border-white/5"><i class="fas fa-id-card mr-1 text-blue-400"></i> ID Card</a>
                            <a href="cetak_kartu.php?id=<?= $r['id_siswa'] ?>&type=qr" target="_blank" class="block px-3 py-2 text-[10px] hover:bg-white/10 transition-colors"><i class="fas fa-qrcode mr-1 text-amber-400"></i> QR Only</a>
                        </div>
                    </div>
                </div>
                <a href="?edit=<?= $r['id_siswa'] ?>" class="p-1.5 rounded bg-blue-600/20 text-blue-400 hover:bg-blue-600/40 text-xs"><i class="fas fa-edit"></i></a>
                <button onclick="confirmDelete('?hapus=<?= $r['id_siswa'] ?>','<?= clean($r['nama']) ?>')" class="p-1.5 rounded bg-red-600/20 text-red-400 hover:bg-red-600/40 text-xs"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if(!$result['data']): ?><tr><td colspan="7" class="text-center py-4 text-slate-400">Data siswa kosong.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <!-- Pagination -->
    <?php if ($result['pages'] > 1): ?>
    <div class="flex justify-center gap-1 mt-4">
        <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
        <a href="?p=<?= $p ?>&kelas=<?= $filter_kelas ?>&q=<?= urlencode($search) ?>" class="px-3 py-1 rounded text-xs <?= $p==$page?'bg-blue-600 text-white':'bg-slate-700 text-slate-300 hover:bg-slate-600' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<script>
// Validasi file csv size < 5MB
document.querySelector('form[enctype="multipart/form-data"]').addEventListener('submit', function(e) {
    const file = this.querySelector('input[type="file"][accept=".csv"]');
    if (file && file.files[0] && file.files[0].size > 5 * 1024 * 1024) {
        alert('File CSV terlalu besar. Maksimal 5MB.');
        e.preventDefault();
    }
});

function toggleSelectAll(source) {
    document.querySelectorAll('.siswa-checkbox').forEach(c => c.checked = source.checked);
}

function bulkPrint(type) {
    const ids = Array.from(document.querySelectorAll('.siswa-checkbox:checked')).map(c => c.value);
    if (ids.length === 0) return alert('Silakan pilih siswa terlebih dahulu!');
    window.open(`cetak_kartu.php?ids=${ids.join(',')}&type=${type}`, '_blank');
}
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
