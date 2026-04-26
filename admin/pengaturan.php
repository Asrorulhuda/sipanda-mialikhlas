<?php
$page_title = 'Pengaturan Sekolah';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);

if (isset($_POST['simpan'])) {
    $logo = upload_file('logo_web', 'gambar', ['jpg','jpeg','png']);
    $logo_kiri = upload_file('logo_kiri', 'gambar', ['jpg','jpeg','png']);
    $logo_kanan = upload_file('logo_kanan', 'gambar', ['jpg','jpeg','png']);
    $sql = "UPDATE tbl_setting SET npsn=?,nama_sekolah=?,instansi_atas=?,nama_yayasan=?,alamat=?,kota=?,telepon=?,email=?,akreditasi=?,kepsek=?,nip_kepsek=?,wa_kepsek=?,nama_tu=?,wa_tu=?,tema=?,midtrans_server_key=?,midtrans_client_key=?,midtrans_snap_url=?,midtrans_is_production=?,tripay_api_key=?,tripay_private_key=?,tripay_merchant_code=?,xendit_api_key=?,wa_gateway_url=?,wa_token=?,wa_sender=?,biaya_admin=?,fee_titipan=?,bk_sp1_min=?,bk_sp2_min=?,bk_sp3_min=?,hero_badge=?,hero_judul=?,hero_deskripsi=?,hero_floating_badge1=?,hero_floating_text1=?,hero_floating_badge2=?,hero_floating_text2=?,keunggulan_sub=?,keunggulan_judul=?,is_active_midtrans=?,is_active_tripay=?,is_active_xendit=?,is_scanner_restricted=?,gemini_api_key=?";
    $params = [$_POST['npsn'],$_POST['nama'],$_POST['instansi_atas'],$_POST['nama_yayasan'],$_POST['alamat'],$_POST['kota'],$_POST['telepon'],$_POST['email'],$_POST['akreditasi'],$_POST['kepsek'],$_POST['nip_kepsek'],$_POST['wa_kepsek'],$_POST['nama_tu'],$_POST['wa_tu'],$_POST['tema'],$_POST['mt_server'],$_POST['mt_client'],$_POST['mt_snap'],$_POST['mt_prod']??0,$_POST['tp_api'],$_POST['tp_private'],$_POST['tp_merchant'],$_POST['xd_api'],$_POST['wa_url'],$_POST['wa_token'],$_POST['wa_sender'],$_POST['biaya_admin'],$_POST['fee_titipan'],$_POST['bk_sp1'],$_POST['bk_sp2'],$_POST['bk_sp3'],$_POST['hero_badge'],$_POST['hero_judul'],$_POST['hero_deskripsi'],$_POST['hero_f_b1'],$_POST['hero_f_t1'],$_POST['hero_f_b2'],$_POST['hero_f_t2'],$_POST['keunggulan_sub'],$_POST['keunggulan_judul'],$_POST['mt_active']??0,$_POST['tp_active']??0,$_POST['xd_active']??0,$_POST['scanner_restricted']??0,$_POST['gemini_api']];
    if ($logo) { $sql .= ",logo_web=?"; $params[] = $logo; }
    if ($logo_kiri) { $sql .= ",logo_kiri=?"; $params[] = $logo_kiri; }
    if ($logo_kanan) { $sql .= ",logo_kanan=?"; $params[] = $logo_kanan; }
    $sql .= " WHERE id=1";
    $pdo->prepare($sql)->execute($params);
    flash('msg','Pengaturan berhasil disimpan!');
    header('Location: pengaturan.php'); exit;
}

$s = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<form method="POST" enctype="multipart/form-data" class="space-y-6">
    <!-- Identitas -->
    <div class="glass rounded-xl p-5">
        <h3 class="text-sm font-semibold mb-4 flex items-center gap-2"><i class="fas fa-school text-blue-400"></i>Identitas Sekolah</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ([['NPSN','npsn'],['Nama Sekolah','nama','nama_sekolah'],['Instansi Atas (Kemen/Dinas)','instansi_atas'],['Nama Yayasan','nama_yayasan'],['Kabupaten/Kota','kota'],['Akreditasi','akreditasi'],['Kepala Sekolah','kepsek'],['NIP Kepsek','nip_kepsek'],['No. WA Kepsek','wa_kepsek'],['Kepala Tata Usaha (TU)','nama_tu'],['No. WA TU','wa_tu'],['Telepon Sekolah','telepon'],['Email','email']] as $f): ?>
            <div><label class="block text-xs text-slate-400 mb-1"><?= $f[0] ?></label><input type="text" name="<?= $f[1] ?>" value="<?= clean($s[$f[2]??$f[1]]??'') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
            <?php endforeach; ?>
            <div class="md:col-span-2"><label class="block text-xs text-slate-400 mb-1">Alamat Lengkap Sekolah</label><textarea name="alamat" rows="2" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><?= clean($s['alamat']??'') ?></textarea></div>
        </div>
    </div>

    <!-- Logo & Tema -->
    <div class="glass rounded-xl p-5">
        <h3 class="text-sm font-semibold mb-4 flex items-center gap-2"><i class="fas fa-palette text-purple-400"></i>Logo & Tema</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><label class="block text-xs text-slate-400 mb-1">Logo Web</label><input type="file" name="logo_web" accept="image/*" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-1.5 text-sm"></div>
            <div><label class="block text-xs text-slate-400 mb-1">Logo Kiri</label><input type="file" name="logo_kiri" accept="image/*" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-1.5 text-sm"></div>
            <div><label class="block text-xs text-slate-400 mb-1">Logo Kanan</label><input type="file" name="logo_kanan" accept="image/*" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-1.5 text-sm"></div>
        </div>
        <div class="mt-4"><label class="block text-xs text-slate-400 mb-1">Tema Warna</label>
            <select name="tema" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                <?php foreach (['blue','red','green','purple','amber','teal'] as $t): ?><option value="<?= $t ?>" <?= ($s['tema']??'')==$t?'selected':'' ?>><?= ucfirst($t) ?></option><?php endforeach; ?>
            </select></div>
    </div>

    <!-- Midtrans -->
    <div class="glass rounded-xl p-5 border-l-4 border-emerald-500/50">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold flex items-center gap-2 text-emerald-400"><i class="fas fa-credit-card"></i>Konfigurasi Midtrans</h3>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="mt_active" value="1" <?= ($s['is_active_midtrans']??0)?'checked':'' ?> class="sr-only peer">
                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
            </label>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-xs text-slate-400 mb-1">Server Key</label><input type="text" name="mt_server" value="<?= clean($s['midtrans_server_key']??'') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm font-mono"></div>
            <div><label class="block text-xs text-slate-400 mb-1">Client Key</label><input type="text" name="mt_client" value="<?= clean($s['midtrans_client_key']??'') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm font-mono"></div>
            <div class="flex items-center gap-3"><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="mt_prod" value="1" <?= ($s['midtrans_is_production']??0)?'checked':'' ?>> Mode Production</label></div>
        </div>
    </div>

    <!-- Tripay -->
    <div class="glass rounded-xl p-5 border-l-4 border-orange-500/50">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold flex items-center gap-2 text-orange-400"><i class="fas fa-bolt"></i>Konfigurasi Tripay</h3>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="tp_active" value="1" <?= ($s['is_active_tripay']??0)?'checked':'' ?> class="sr-only peer">
                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
            </label>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><label class="block text-xs text-slate-400 mb-1">API Key</label><input type="text" name="tp_api" value="<?= clean($s['tripay_api_key']??'') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm font-mono"></div>
            <div><label class="block text-xs text-slate-400 mb-1">Private Key</label><input type="text" name="tp_private" value="<?= clean($s['tripay_private_key']??'') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm font-mono"></div>
            <div><label class="block text-xs text-slate-400 mb-1">Merchant Code</label><input type="text" name="tp_merchant" value="<?= clean($s['tripay_merchant_code']??'') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        </div>
    </div>

    <!-- Xendit -->
    <div class="glass rounded-xl p-5 border-l-4 border-purple-500/50">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold flex items-center gap-2 text-purple-400"><i class="fas fa-shield-alt"></i>Konfigurasi Xendit</h3>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="xd_active" value="1" <?= ($s['is_active_xendit']??0)?'checked':'' ?> class="sr-only peer">
                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-500"></div>
            </label>
        </div>
        <div class="max-w-md"><label class="block text-xs text-slate-400 mb-1">Secret/API Key</label><input type="text" name="xd_api" value="<?= clean($s['xendit_api_key']??'') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm font-mono"></div>
    </div>

    <!-- WhatsApp -->
    <div class="glass rounded-xl p-5">
        <h3 class="text-sm font-semibold mb-4 flex items-center gap-2"><i class="fab fa-whatsapp text-green-400"></i>Konfigurasi WhatsApp</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><label class="block text-xs text-slate-400 mb-1">Gateway URL</label><input type="text" name="wa_url" value="<?= clean($s['wa_gateway_url']??'') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs text-slate-400 mb-1">Token</label><input type="text" name="wa_token" value="<?= clean($s['wa_token']??'') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm font-mono"></div>
            <div><label class="block text-xs text-slate-400 mb-1">No. Pengirim</label><input type="text" name="wa_sender" value="<?= clean($s['wa_sender']??'') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        </div>
    </div>

    <!-- Biaya Admin -->
    <div class="glass rounded-xl p-5">
        <h3 class="text-sm font-semibold mb-4 flex items-center gap-2"><i class="fas fa-money-bill text-amber-400"></i>Biaya Admin & Titipan</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-xs text-slate-400 mb-1">Biaya Admin Pembayaran Online</label><input type="number" name="biaya_admin" value="<?= $s['biaya_admin']??0 ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs text-slate-400 mb-1">Bagi Hasil Kantin (%) - Titip Jualan Guru</label><input type="number" name="fee_titipan" value="<?= $s['fee_titipan']??10 ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        </div>
    </div>

    <!-- Konfigurasi BK -->
    <div class="glass rounded-xl p-5">
        <h3 class="text-sm font-semibold mb-4 flex items-center gap-2"><i class="fas fa-balance-scale text-rose-400"></i>Ambang Batas Poin BK (Surat Peringatan)</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><label class="block text-xs text-slate-400 mb-1">Minimal Poin SP 1</label><input type="number" name="bk_sp1" value="<?= $s['bk_sp1_min']??50 ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs text-slate-400 mb-1">Minimal Poin SP 2</label><input type="number" name="bk_sp2" value="<?= $s['bk_sp2_min']??75 ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs text-slate-400 mb-1">Minimal Poin SP 3 / DO</label><input type="number" name="bk_sp3" value="<?= $s['bk_sp3_min']??100 ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        </div>
        <p class="text-[10px] text-slate-500 mt-2 italic">* Surat Peringatan akan otomatis ditawarkan cetak jika poin siswa mencapai ambang batas ini dalam 1 tahun ajaran.</p>
    </div>

    <!-- Konfigurasi AI -->
    <div class="glass rounded-xl p-5 border-l-4 border-blue-500/50">
        <h3 class="text-sm font-semibold mb-4 flex items-center gap-2 text-blue-400"><i class="fas fa-brain"></i>Konfigurasi AI (Google Gemini)</h3>
        <div class="max-w-md">
            <label class="block text-xs text-slate-400 mb-1">Gemini API Key</label>
            <input type="text" name="gemini_api" value="<?= clean($s['gemini_api_key']??'') ?>" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm font-mono placeholder:italic" placeholder="Paste API Key Anda di sini...">
            <p class="text-[10px] text-slate-500 mt-2 italic">Dapatkan API Key secara gratis di <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-blue-400 hover:underline">Google AI Studio</a>. Digunakan untuk Generator RPP otomatis.</p>
        </div>
    </div>

    <!-- Keamanan & Akses -->
    <div class="glass rounded-xl p-5 border-l-4 border-rose-500/50">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h3 class="text-sm font-semibold flex items-center gap-2 text-rose-400"><i class="fas fa-lock"></i>Keamanan Scanner QR</h3>
                <p class="text-[10px] text-slate-500 mt-1">Gunakan ini untuk membatasi perangkat mana saja yang boleh melakukan scan presensi.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="scanner_restricted" value="1" <?= ($s['is_scanner_restricted']??0)?'checked':'' ?> class="sr-only peer">
                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-500"></div>
            </label>
        </div>
        <div class="mt-4 pt-4 border-t border-white/5">
            <a href="authorized_devices.php" class="text-xs font-bold text-blue-400 hover:text-blue-300 flex items-center gap-2"><i class="fas fa-mobile-alt"></i> Kelola Perangkat Terdaftar &rarr;</a>
        </div>
    </div>

    <!-- Konten Hero & Keunggulan -->
    <div class="glass rounded-xl p-5">
        <h3 class="text-sm font-semibold mb-4 flex items-center gap-2 text-gold-400"><i class="fas fa-desktop"></i>Konten Hero (Homepage Area)</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2"><label class="block text-xs text-slate-400 mb-1">Badge Hero (Label Kecil)</label><input type="text" name="hero_badge" value="<?= clean($s['hero_badge']??'') ?>" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs text-slate-400 mb-1">Judul Hero (Bisa pakai &lt;br&gt; dan &lt;span class="text-gradient"&gt; untuk gradasi)</label><textarea name="hero_judul" rows="2" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm"><?= clean($s['hero_judul']??'') ?></textarea></div>
            <div><label class="block text-xs text-slate-400 mb-1">Deskripsi Hero (Subtitle)</label><textarea name="hero_deskripsi" rows="2" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm"><?= clean($s['hero_deskripsi']??'') ?></textarea></div>
            
            <div class="p-3 border border-white/5 rounded-lg bg-white/5">
                <p class="text-[10px] font-bold text-slate-500 uppercase mb-2">Kartu Melayang 1 (Atas)</p>
                <div class="space-y-2">
                    <input type="text" name="hero_f_b1" placeholder="Label, misal: Akreditasi" value="<?= clean($s['hero_floating_badge1']??'') ?>" class="w-full bg-slate-800 border border-white/10 rounded px-2 py-1.5 text-xs">
                    <input type="text" name="hero_f_t1" placeholder="Nilai, misal: A (Sangat Baik)" value="<?= clean($s['hero_floating_text1']??'') ?>" class="w-full bg-slate-800 border border-white/10 rounded px-2 py-1.5 text-xs">
                </div>
            </div>
            
            <div class="p-3 border border-white/5 rounded-lg bg-white/5">
                <p class="text-[10px] font-bold text-slate-500 uppercase mb-2">Kartu Melayang 2 (Bawah)</p>
                <div class="space-y-2">
                    <input type="text" name="hero_f_b2" placeholder="Label, misal: Program" value="<?= clean($s['hero_floating_badge2']??'') ?>" class="w-full bg-slate-800 border border-white/10 rounded px-2 py-1.5 text-xs">
                    <input type="text" name="hero_f_t2" placeholder="Nilai, misal: Tahfidz & IT" value="<?= clean($s['hero_floating_text2']??'') ?>" class="w-full bg-slate-800 border border-white/10 rounded px-2 py-1.5 text-xs">
                </div>
            </div>
        </div>
        
        <h3 class="text-sm font-semibold mb-4 mt-8 flex items-center gap-2 text-blue-400"><i class="fas fa-star"></i>Header Section Keunggulan</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-xs text-slate-400 mb-1">Sub-Title (Label Kecil)</label><input type="text" name="keunggulan_sub" value="<?= clean($s['keunggulan_sub']??'') ?>" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
            <div><label class="block text-xs text-slate-400 mb-1">Judul Utama</label><input type="text" name="keunggulan_judul" value="<?= clean($s['keunggulan_judul']??'') ?>" class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
        </div>
    </div>

    <button type="submit" name="simpan" class="bg-blue-600 hover:bg-blue-500 px-8 py-3 rounded-lg text-sm font-medium"><i class="fas fa-save mr-2"></i>Simpan Pengaturan</button>
</form>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
