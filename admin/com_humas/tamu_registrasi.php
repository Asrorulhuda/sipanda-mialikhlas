<?php
$page_title = 'Registrasi Tamu Baru';
require_once __DIR__ . '/../../config/init.php';

// Public access allowed
$s = $pdo->query("SELECT nama_sekolah, wa_tu, kepsek, wa_kepsek FROM tbl_setting WHERE id=1")->fetch();
$staff_list = $pdo->query("SELECT nama FROM tbl_guru WHERE status='Aktif' ORDER BY nama ASC")->fetchAll();

$rfid = $_GET['rfid'] ?? '';

// Auto-fill from past visit if RFID provided
$guest_data = null;
if ($rfid) {
    $past = $pdo->prepare("SELECT nama_tamu, instansi, no_hp FROM tbl_humas_tamu WHERE rfid_uid = ? ORDER BY id DESC LIMIT 1");
    $past->execute([$rfid]);
    $guest_data = $past->fetch();
}

// Handle Save Visit (Check-In)
if (isset($_POST['checkin'])) {
    $rfid_submit = $_POST['rfid_uid'] ?: NULL;
    $nama = clean($_POST['nama_tamu']);
    $hp = clean($_POST['no_hp']);
    $instansi = clean($_POST['instansi']);
    $tujuan = clean($_POST['tujuan']);
    $bertemu = clean($_POST['bertemu_dengan']);
    
    $pdo->prepare("INSERT INTO tbl_humas_tamu (rfid_uid, nama_tamu, no_hp, instansi, tujuan, bertemu_dengan, tanggal, status) VALUES (?,?,?,?,?,?,NOW(),'Masuk')")
        ->execute([$rfid_submit, $nama, $hp, $instansi, $tujuan, $bertemu]);
    
    // Notify Staff being visited
    require_once __DIR__ . '/../../api/wa_helper.php';
    if (strpos($bertemu, 'Kepala Sekolah') !== false) {
        $wa_staff = $s['wa_kepsek'];
    } else {
        $staff = $pdo->prepare("SELECT no_hp FROM tbl_guru WHERE nama LIKE ? LIMIT 1");
        $staff->execute(["%$bertemu%"]);
        $wa_staff = $staff->fetchColumn();
    }
    
    if ($wa_staff) {
        $msg_staff = "📢 *LAPORAN TAMU DATANG*\n\nHalo,\nAda tamu *{$nama}* ({$instansi}) ingin menemui Anda untuk keperluan: *{$tujuan}*.\n\nMohon segera ditemui di Lobby. 🙏";
        send_wa($wa_staff, $msg_staff);
    }

    flash('msg_kiosk', 'Selamat Datang, '.$nama.'! Silakan masuk.');
    header('Location: tamu_kiosk.php'); exit;
}

require_once __DIR__ . '/../../template/header.php';
?>

<div class="fixed top-0 left-0 w-full h-full -z-10 opacity-20 pointer-events-none">
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-rose-600 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-blue-600 rounded-full blur-[120px]"></div>
</div>

<div class="max-w-4xl mx-auto px-6 pt-12">
    <div class="text-center mb-12">
        <h1 class="text-3xl font-black tracking-tighter uppercase italic mb-2">PENDAFTARAN TAMU</h1>
        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest"><?= clean($s['nama_sekolah']) ?></p>
    </div>

    <div class="glass rounded-[2rem] p-10 border border-white/5 shadow-2xl animate-fade-in">
        <div class="flex items-center justify-between mb-10">
            <div>
                <h3 class="text-xl font-bold"><i class="fas fa-user-edit mr-3 text-rose-500"></i>INFORMASI KUNJUNGAN</h3>
                <?php if ($rfid): ?>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="text-[10px] bg-white/5 text-slate-500 px-2 py-0.5 rounded-full uppercase font-bold tracking-widest">ID KARTU</span>
                        <span class="text-xs font-mono font-bold text-rose-400 tracking-wider"><?= clean($rfid) ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <a href="tamu_kiosk.php" class="text-slate-500 hover:text-white transition-colors text-sm font-bold"><i class="fas fa-arrow-left mr-2"></i> KEMBALI</a>
        </div>

        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <input type="hidden" name="rfid_uid" value="<?= clean($rfid) ?>">
            
            <div class="md:col-span-2">
                <label class="block text-xs text-slate-500 mb-2 font-black uppercase tracking-[0.2em]">Nama Lengkap & Jabatan</label>
                <input type="text" name="nama_tamu" value="<?= clean($guest_data['nama_tamu'] ?? '') ?>" required placeholder="Contoh: Bpk. Andi (PT. Sumber Makmur)" 
                       class="w-full bg-slate-800/50 border border-white/10 rounded-2xl px-5 py-4 text-sm focus:border-rose-500 outline-none transition-all focus:bg-slate-800">
            </div>

            <div>
                <label class="block text-xs text-slate-500 mb-2 font-black uppercase tracking-[0.2em]">Nomor WhatsApp</label>
                <input type="text" name="no_hp" value="<?= clean($guest_data['no_hp'] ?? '') ?>" required placeholder="08xxxxxxxxxx" 
                       class="w-full bg-slate-800/50 border border-white/10 rounded-2xl px-5 py-4 text-sm focus:border-rose-500 outline-none transition-all focus:bg-slate-800">
            </div>

            <div>
                <label class="block text-xs text-slate-500 mb-2 font-black uppercase tracking-[0.2em]">Asal Instansi</label>
                <input type="text" name="instansi" value="<?= clean($guest_data['instansi'] ?? '') ?>" required placeholder="Nama Kantor / Organisasi" 
                       class="w-full bg-slate-800/50 border border-white/10 rounded-2xl px-5 py-4 text-sm focus:border-rose-500 outline-none transition-all focus:bg-slate-800">
            </div>

            <div>
                <label class="block text-xs text-slate-500 mb-2 font-black uppercase tracking-[0.2em]">Bertemu Dengan</label>
                <select name="bertemu_dengan" required class="w-full bg-slate-800/50 border border-white/10 rounded-2xl px-5 py-4 text-sm focus:border-rose-500 outline-none appearance-none transition-all">
                    <option value="">-- Pilih Staff / Guru --</option>
                    <option value="Kepala Sekolah (<?= clean($s['kepsek']) ?>)">Kepala Sekolah (<?= clean($s['kepsek']) ?>)</option>
                    <?php foreach($staff_list as $sl): ?>
                        <option value="<?= clean($sl['nama']) ?>"><?= clean($sl['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs text-slate-500 mb-2 font-black uppercase tracking-[0.2em]">Tujuan Kunjungan</label>
                <input type="text" name="tujuan" required placeholder="Misal: Diskusi Kerja Sama" 
                       class="w-full bg-slate-800/50 border border-white/10 rounded-2xl px-5 py-4 text-sm focus:border-rose-500 outline-none transition-all focus:bg-slate-800">
            </div>

            <div class="md:col-span-2 pt-6">
                <button type="submit" name="checkin" class="w-full py-5 bg-rose-600 hover:bg-rose-500 text-white rounded-2xl font-black uppercase tracking-widest shadow-xl shadow-rose-900/40 transform active:scale-[0.98] transition-all">
                    SIMPAN & CHECK-IN
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .animate-fade-in { animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
</style>

</body>
</html>
