<?php
$page_title = 'Profil Saya';
require_once __DIR__ . '/../config/init.php';
cek_role(['siswa']);
$id = $_SESSION['user_id'];
if (isset($_POST['update'])) {
    $sql = "UPDATE tbl_siswa SET no_hp_siswa=?"; $params = [$_POST['hp']];
    if (!empty($_POST['password'])) { $sql .= ",password=?"; $params[] = password_hash($_POST['password'],PASSWORD_DEFAULT); }
    $f = upload_file('foto','foto_siswa',['jpg','jpeg','png']); if ($f) { $sql .= ",foto=?"; $params[] = $f; }
    $sql .= " WHERE id_siswa=?"; $params[] = $id;
    $pdo->prepare($sql)->execute($params);
    flash('msg','Profil berhasil diupdate!'); header('Location: profil.php'); exit;
}
$stmt = $pdo->prepare("SELECT s.*,k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE s.id_siswa=?"); $stmt->execute([$id]); $me = $stmt->fetch();
require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<div class="max-w-2xl mx-auto">
    <div class="glass rounded-xl p-6 mb-6 text-center">
        <div class="w-24 h-24 mx-auto rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-3xl font-bold mb-4"><?= strtoupper(substr($me['nama'],0,1)) ?></div>
        <h3 class="text-xl font-bold"><?= clean($me['nama']) ?></h3>
        <p class="text-slate-400"><?= clean($me['nama_kelas']??'-') ?></p>
    </div>
    <div class="glass rounded-xl p-5 mb-6">
        <h4 class="text-sm font-semibold mb-4">Data Pribadi</h4>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <?php foreach ([['NISN',$me['nisn']],['NIS',$me['nis']],['JK',$me['jk']=='L'?'Laki-laki':'Perempuan'],['Tempat Lahir',$me['tempat_lahir']],['Tanggal Lahir',tgl_indo($me['tanggal_lahir'])],['Alamat',$me['alamat']],['HP Ortu',$me['no_hp_ortu']],['HP Siswa',$me['no_hp_siswa']]] as $f): ?>
            <div><p class="text-slate-400 text-xs"><?= $f[0] ?></p><p class="font-medium"><?= clean($f[1]??'-') ?></p></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="glass rounded-xl p-5 mb-6">
        <h4 class="text-sm font-semibold mb-4 text-emerald-400"><i class="fas fa-users mr-2"></i>Data Orang Tua / Wali</h4>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <?php foreach ([['Nama Ayah',$me['nama_ayah']],['Nama Ibu',$me['nama_ibu']],['Pekerjaan Ayah',$me['pekerjaan_ayah']],['Pekerjaan Ibu',$me['pekerjaan_ibu']],['Penghasilan Ortu',$me['penghasilan_ortu']]] as $f): ?>
            <div><p class="text-slate-400 text-[10px] uppercase font-bold tracking-wider mb-1"><?= $f[0] ?></p><p class="font-medium text-slate-200"><?= clean($f[1]?:'-') ?></p></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="glass rounded-xl p-5 mb-6">
        <h4 class="text-sm font-semibold mb-4 text-purple-400"><i class="fas fa-tags mr-2"></i>Status & Kategori</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <?php foreach ([['Status Keaktifan',$me['status']],['Status Keluarga',$me['status_keluarga']],['Kategori Siswa',$me['kategori']]] as $f): ?>
            <div>
                <p class="text-slate-400 text-[10px] uppercase font-bold tracking-wider mb-1"><?= $f[0] ?></p>
                <span class="inline-block px-2 xl py-0.5 rounded text-xs border border-white/10 bg-black/20 font-medium"><?= clean($f[1]?:'-') ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="glass rounded-xl p-5">
        <h4 class="text-sm font-semibold mb-4">Update Profil</h4>
        <form method="POST" enctype="multipart/form-data" class="space-y-3">
            <div><label class="block text-xs text-slate-400 mb-1">No. HP</label><input type="text" name="hp" value="<?= clean($me['no_hp_siswa']) ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs text-slate-400 mb-1">Password Baru</label><input type="password" name="password" placeholder="Kosongkan jika tidak diubah" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs text-slate-400 mb-1">Foto</label><input type="file" name="foto" accept="image/*" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-1.5 text-sm"></div>
            <button type="submit" name="update" class="bg-blue-600 hover:bg-blue-500 px-6 py-2 rounded-lg text-sm font-medium"><i class="fas fa-save mr-1"></i>Simpan</button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
