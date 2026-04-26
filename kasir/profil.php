<?php
$page_title = 'Profil Kasir';
require_once __DIR__ . '/../config/init.php';
cek_role(['kasir']);

$id = $_SESSION['user_id'];
if (isset($_POST['update'])) {
    $nama = $_POST['nama'];
    $pw = $_POST['password'];
    
    $sql = "UPDATE tbl_admin SET nama=?";
    $params = [$nama];
    if (!empty($pw)) {
        $sql .= ", password=?";
        $params[] = password_hash($pw, PASSWORD_DEFAULT);
    }
    $sql .= " WHERE id_admin=?"; $params[] = $id;
    $pdo->prepare($sql)->execute($params);
    
    $_SESSION['nama'] = $nama;
    flash('msg', 'Profil berhasil diperbarui!');
    header('Location: profil.php'); exit;
}

$u = $pdo->prepare("SELECT * FROM tbl_admin WHERE id_admin=?");
$u->execute([$id]); $user = $u->fetch();

require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<div class="max-w-xl mx-auto">
    <div class="glass rounded-3xl p-10 border border-white/5">
        <div class="text-center mb-10">
            <div class="w-24 h-24 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-3xl font-black mx-auto mb-6 shadow-xl shadow-blue-500/30">
                <?= strtoupper(substr($user['nama'], 0, 1)) ?>
            </div>
            <h4 class="font-black text-2xl tracking-widest uppercase italic"><?= clean($user['nama']) ?></h4>
            <p class="text-slate-500 text-xs font-mono uppercase tracking-widest mt-2"><?= $user['username'] ?> • KASIR KANTIN</p>
        </div>

        <form method="POST" class="space-y-6">
            <div class="space-y-2">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Nama Lengkap</label>
                <input type="text" name="nama" value="<?= clean($user['nama']) ?>" required class="w-full bg-slate-900 border border-white/10 rounded-2xl px-4 py-4 text-sm focus:border-blue-500 outline-none transition-all">
            </div>
            <div class="space-y-2">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Ganti Password (Kosongkan jika tidak diubah)</label>
                <input type="password" name="password" placeholder="••••••••" class="w-full bg-slate-900 border border-white/10 rounded-2xl px-4 py-4 text-sm focus:border-blue-500 outline-none transition-all">
            </div>
            
            <button type="submit" name="update" class="w-full py-4 bg-blue-600 hover:bg-blue-500 rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-blue-900/40 transition-all active:scale-95">SIMPAN PERUBAHAN</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
