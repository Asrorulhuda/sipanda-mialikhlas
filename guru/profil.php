<?php
$page_title = 'Profil Saya';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
$id = $_SESSION['user_id'];

if (isset($_POST['update'])) {
    $sql = "UPDATE tbl_guru SET no_hp=?, alamat=?, tempat_lahir=?, tanggal_lahir=?, jurusan=?, pendidikan_terakhir=?"; 
    $params = [
        $_POST['hp'], $_POST['alamat'], $_POST['tempat_lahir'], 
        empty($_POST['tanggal_lahir'])?null:$_POST['tanggal_lahir'], 
        $_POST['jurusan'], $_POST['pendidikan_terakhir']
    ];
    if (!empty($_POST['password'])) { 
        $sql .= ",password=?"; 
        $params[] = password_hash($_POST['password'],PASSWORD_DEFAULT); 
    }
    $f = upload_file('foto','gambar',['jpg','jpeg','png']); 
    if ($f) { 
        $sql .= ",foto=?"; 
        $params[] = $f; 
        $_SESSION['foto'] = $f; // update session foto 
    }
    $sql .= " WHERE id_guru=?"; 
    $params[] = $id;
    
    $pdo->prepare($sql)->execute($params);
    flash('msg','Profil berhasil diupdate!'); 
    header('Location: profil.php'); exit;
}

$stmt = $pdo->prepare("SELECT * FROM tbl_guru WHERE id_guru=?"); 
$stmt->execute([$id]); 
$me = $stmt->fetch();

require_once __DIR__ . '/../template/header.php'; 
require_once __DIR__ . '/../template/sidebar.php'; 
require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<div class="max-w-2xl mx-auto">
    <div class="glass rounded-xl p-8 mb-6 text-center border border-white/5 relative overflow-hidden">
        <div class="absolute inset-x-0 top-0 h-24 bg-gradient-to-r from-blue-600/30 to-purple-600/30"></div>
        <div class="w-24 h-24 mx-auto rounded-full bg-slate-800 border-4 border-slate-900 flex items-center justify-center text-white text-3xl font-bold mb-4 relative z-10 shadow-xl overflow-hidden">
            <?php if ($me['foto'] && $me['foto'] !== 'default.png'): ?>
                <img src="<?= BASE_URL ?>assets/uploads/gambar/<?= $me['foto'] ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <?= strtoupper(substr($me['nama'],0,1)) ?>
            <?php endif; ?>
        </div>
        <h3 class="text-2xl font-bold text-white relative z-10"><?= clean($me['nama']) ?></h3>
        <p class="text-blue-400 font-medium relative z-10"><?= clean($me['tugas_tambahan']?:'Guru Mata Pelajaran') ?></p>
        <p class="text-xs text-slate-400 mt-1 relative z-10">NIP: <?= clean($me['nip']?:'-') ?> · NUPTK: <?= clean($me['nuptk']?:'-') ?></p>
    </div>

    <!-- Data Info Readonly -->
    <div class="glass rounded-xl p-6 mb-6 border border-white/5">
        <h4 class="text-sm font-semibold mb-4 text-emerald-400"><i class="fas fa-id-card mr-2"></i>Data Profil</h4>
        <div class="grid grid-cols-2 gap-y-4 gap-x-2 text-sm">
            <?php foreach ([
                ['Jenis Kelamin', $me['jk']=='L'?'Laki-laki':'Perempuan'],
                ['Agama', $me['agama']],
                ['Tempat Lahir', $me['tempat_lahir']],
                ['Tanggal Lahir', tgl_indo($me['tanggal_lahir'])],
                ['Pendidikan Terakhir', $me['pendidikan_terakhir']],
                ['Jurusan/Prodi', $me['jurusan']],
                ['No. Handphone', $me['no_hp']],
                ['Alamat Domisili', $me['alamat']]
            ] as $f): ?>
            <div><p class="text-slate-400 text-[10px] uppercase font-bold tracking-wider mb-1"><?= $f[0] ?></p><p class="font-medium text-slate-200"><?= clean($f[1]?:'-') ?></p></div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Update Form -->
    <div class="glass rounded-xl p-6 border border-white/5">
        <h4 class="text-sm font-semibold mb-4 text-purple-400"><i class="fas fa-edit mr-2"></i>Update Profil</h4>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-xs text-slate-400 mb-1">No. Handphone / WA</label>
                    <input type="text" name="hp" value="<?= clean($me['no_hp']) ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div><label class="block text-xs text-slate-400 mb-1">Pendidikan Terakhir</label>
                    <select name="pendidikan_terakhir" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                        <option value="">-- Pilih --</option>
                        <?php foreach (['SMA/SMK','D3','D4','S1','S2','S3'] as $a): ?><option <?= ($me['pendidikan_terakhir']??'')==$a?'selected':'' ?>><?= $a ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div><label class="block text-xs text-slate-400 mb-1">Jurusan / Program Studi</label>
                    <input type="text" name="jurusan" value="<?= clean($me['jurusan']) ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div><label class="block text-xs text-slate-400 mb-1">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" value="<?= clean($me['tempat_lahir']) ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div><label class="block text-xs text-slate-400 mb-1">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" value="<?= clean($me['tanggal_lahir']) ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <div><label class="block text-xs text-slate-400 mb-1">Update Foto Profil</label>
                    <input type="file" name="foto" accept="image/*" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-1.5 text-sm file:mr-3 file:bg-blue-600 file:text-white file:border-0 file:rounded file:px-2 file:py-1 file:text-xs hover:file:bg-blue-500 transition-all cursor-pointer">
                </div>
            </div>
            
            <div><label class="block text-xs text-slate-400 mb-1">Alamat Domisili</label>
                <input type="text" name="alamat" value="<?= clean($me['alamat']) ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <!-- Spacer -->
            <div class="h-0.5 w-full bg-white/5 my-4"></div>

            <!-- Password -->
            <div><label class="block text-xs text-slate-400 mb-1">Ganti Password</label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                    <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password" class="w-full bg-slate-800/50 border border-white/10 rounded-lg py-2.5 pl-10 pr-4 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 transition-colors">
                </div>
                <p class="text-[10px] text-amber-500/80 mt-1"><i class="fas fa-exclamation-triangle mr-1"></i>Abaikan bidang ini jika sandi lama masih berlaku.</p>
            </div>

            <div class="mt-4 pt-4 border-t border-white/5 flex gap-2 justify-end">
                <button type="submit" name="update" class="bg-blue-600 hover:bg-blue-500 px-8 py-2.5 rounded-lg text-sm font-bold text-white transition-all shadow-lg shadow-blue-500/20"><i class="fas fa-save mr-2"></i>Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../template/footer.php'; ?>
