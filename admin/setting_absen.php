<?php
$page_title = 'Set Jadwal Absensi';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin']);
cek_fitur('absensi');

$days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

// 1. Simpan Global
if (isset($_POST['simpan_global'])) {
    $pdo->prepare("UPDATE tbl_setting_absen SET jam_masuk=?, batas_telat=?, jam_pulang=? WHERE id=1")
        ->execute([$_POST['masuk'], $_POST['telat'], $_POST['pulang']]);
    flash('msg', 'Jadwal Global berhasil diperbarui!');
    header('Location: setting_absen.php'); exit;
}

// 2. Simpan Khusus Kelas
if (isset($_POST['simpan_kelas'])) {
    $id_kelas = $_POST['id_kelas'];
    $pdo->prepare("DELETE FROM tbl_setting_absen_kelas WHERE id_kelas=?")->execute([$id_kelas]);
    
    foreach ($_POST['jadwal'] as $hari => $v) {
        if (!empty($v['masuk'])) {
            $pdo->prepare("INSERT INTO tbl_setting_absen_kelas (id_kelas, hari, jam_masuk, batas_telat, jam_pulang) VALUES (?,?,?,?,?)")
                ->execute([$id_kelas, $hari, $v['masuk'], $v['telat'], $v['pulang']]);
        }
    }
    flash('msg', 'Jadwal khusus kelas berhasil disimpan!');
    header('Location: setting_absen.php?tab=kelas&id_kelas='.$id_kelas); exit;
}

// 3. Salin Jadwal
if (isset($_POST['salin_jadwal'])) {
    $from = $_POST['from_kelas'];
    $to = $_POST['to_kelas'];
    
    $source = $pdo->prepare("SELECT * FROM tbl_setting_absen_kelas WHERE id_kelas=?");
    $source->execute([$from]);
    $items = $source->fetchAll();
    
    if ($items) {
        $pdo->prepare("DELETE FROM tbl_setting_absen_kelas WHERE id_kelas=?")->execute([$to]);
        foreach ($items as $item) {
            $pdo->prepare("INSERT INTO tbl_setting_absen_kelas (id_kelas, hari, jam_masuk, batas_telat, jam_pulang) VALUES (?,?,?,?,?)")
                ->execute([$to, $item['hari'], $item['jam_masuk'], $item['batas_telat'], $item['jam_pulang']]);
        }
        flash('msg', 'Jadwal berhasil disalin!');
    }
    header('Location: setting_absen.php?tab=kelas&id_kelas='.$to); exit;
}

// 4. Simpan Jadwal Guru
if (isset($_POST['simpan_guru'])) {
    $pdo->prepare("DELETE FROM tbl_setting_absen_guru")->execute();
    foreach ($_POST['jadwal'] as $hari => $v) {
        if (!empty($v['masuk'])) {
            $pdo->prepare("INSERT INTO tbl_setting_absen_guru (hari, jam_masuk, batas_telat, jam_pulang) VALUES (?,?,?,?)")
                ->execute([$hari, $v['masuk'], $v['telat'], $v['pulang']]);
        }
    }
    flash('msg', 'Jadwal GTK berhasil diperbarui!');
    header('Location: setting_absen.php?tab=guru'); exit;
}

$tab = $_GET['tab'] ?? 'kelas';
$id_kelas_edit = $_GET['id_kelas'] ?? null;

// 3. Simpan Hari Libur
if (isset($_POST['simpan_libur'])) {
    $tgl = $_POST['tanggal'];
    $ket = $_POST['keterangan'];
    try {
        $pdo->prepare("INSERT INTO tbl_hari_libur (tanggal, keterangan) VALUES (?, ?)")->execute([$tgl, $ket]);
        flash('msg', 'Tanggal merah berhasil ditambahkan!');
    } catch(PDOException $e) {
        flash('msg', 'Gagal: Tanggal tersebut mungkin sudah ada di daftar libur.', 'error');
    }
    header('Location: setting_absen.php?tab=libur'); exit;
}

// 4. Hapus Hari Libur
if (isset($_GET['hapus_libur'])) {
    $pdo->prepare("DELETE FROM tbl_hari_libur WHERE id=?")->execute([$_GET['hapus_libur']]);
    flash('msg', 'Tanggal merah berhasil dihapus!');
    header('Location: setting_absen.php?tab=libur'); exit;
}

$global = $pdo->query("SELECT * FROM tbl_setting_absen WHERE id=1")->fetch();
$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();

$jadwal_khusus = [];
if ($id_kelas_edit) {
    $q = $pdo->prepare("SELECT * FROM tbl_setting_absen_kelas WHERE id_kelas=?");
    $q->execute([$id_kelas_edit]);
    foreach ($q->fetchAll() as $r) {
        $jadwal_khusus[$r['hari']] = $r;
    }
}

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-white">Pengaturan Waktu Absensi</h2>
</div>

<?= alert_flash('msg') ?>

<!-- Tab Navigation -->
<div class="flex gap-2 mb-6 border-b border-white/5 pb-px">
    <a href="?tab=kelas" class="px-6 py-3 text-sm font-bold transition-all border-b-2 <?= $tab=='kelas' ? 'border-blue-500 text-blue-400 bg-blue-500/5' : 'border-transparent text-slate-500 hover:text-white' ?>">
        <i class="fas fa-users-cog mr-2"></i>Jadwal Khusus Kelas
    </a>
    <a href="?tab=guru" class="px-6 py-3 text-sm font-bold transition-all border-b-2 <?= $tab=='guru' ? 'border-amber-500 text-amber-400 bg-amber-500/5' : 'border-transparent text-slate-500 hover:text-white' ?>">
        <i class="fas fa-chalkboard-teacher mr-2"></i>Jadwal Guru (GTK)
    </a>
    <a href="?tab=libur" class="px-6 py-3 text-sm font-bold transition-all border-b-2 <?= $tab=='libur' ? 'border-rose-500 text-rose-400 bg-rose-500/5' : 'border-transparent text-slate-500 hover:text-white' ?>">
        <i class="fas fa-calendar-times mr-2"></i>Tanggal Merah (Libur)
    </a>
</div>

<?php if ($tab == 'libur'): ?>
    <?php $libur_list = $pdo->query("SELECT * FROM tbl_hari_libur ORDER BY tanggal DESC")->fetchAll(); ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fade-in">
        <div class="lg:col-span-1">
            <div class="glass rounded-2xl p-6 border border-white/5 shadow-2xl">
                <h3 class="text-sm font-bold text-white mb-4">Tambah Tanggal Merah</h3>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Tanggal</label>
                        <input type="date" name="tanggal" required class="w-full bg-slate-800 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-rose-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Keterangan / Nama Libur</label>
                        <input type="text" name="keterangan" required placeholder="Cth: Hari Kemerdekaan RI" class="w-full bg-slate-800 border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:border-rose-500 focus:outline-none">
                    </div>
                    <button type="submit" name="simpan_libur" class="w-full bg-rose-600 hover:bg-rose-500 py-3 rounded-xl text-sm font-bold shadow-lg shadow-rose-600/20 transition-all">
                        <i class="fas fa-plus mr-2"></i>Tambahkan
                    </button>
                    <p class="text-[10px] text-slate-500 text-center mt-2">Menambahkan tanggal merah akan memblokir fitur absen (RFID/QR) pada hari tersebut, dan dianggap sebagai libur di laporan.</p>
                </form>
            </div>
        </div>
        <div class="lg:col-span-2">
            <div class="glass rounded-2xl p-6 border border-white/5 shadow-2xl overflow-hidden">
                <h3 class="text-sm font-bold text-white mb-4">Daftar Tanggal Merah</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500 border-b border-white/5">
                                <th class="pb-3 px-2">Tanggal</th>
                                <th class="pb-3 px-2">Keterangan</th>
                                <th class="pb-3 px-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php if($libur_list): foreach($libur_list as $l): ?>
                            <tr class="hover:bg-white/5 transition-all">
                                <td class="py-3 px-2 font-bold text-rose-400"><?= tgl_indo($l['tanggal']) ?></td>
                                <td class="py-3 px-2 text-white"><?= clean($l['keterangan']) ?></td>
                                <td class="py-3 px-2 text-right">
                                    <button onclick="if(confirm('Hapus tanggal libur ini?')) window.location.href='?hapus_libur=<?= $l['id'] ?>'" class="text-slate-500 hover:text-rose-500 transition-colors p-2"><i class="fas fa-trash-alt"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="3" class="py-6 text-center text-slate-500">Belum ada data tanggal merah / libur nasional.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>
<?php elseif ($tab == 'guru'): ?>
    <?php
    $q = $pdo->query("SELECT * FROM tbl_setting_absen_guru");
    $j_guru = [];
    foreach ($q->fetchAll() as $r) { $j_guru[$r['hari']] = $r; }
    ?>
    <div class="animate-fade-in">
        <div class="glass rounded-2xl p-8 border border-white/5 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-amber-500/5 rounded-full blur-3xl"></div>
            <div class="flex items-center justify-between mb-8 relative z-10">
                <div>
                    <h3 class="text-xl font-bold text-white leading-none mb-1">Set Jadwal Kerja Guru & Staf (GTK)</h3>
                    <p class="text-xs text-slate-500">Tentukan jam operasional harian untuk seluruh pegawai sekolah.</p>
                </div>
            </div>

            <form method="POST" class="relative z-10">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500 border-b border-white/5">
                                <th class="pb-4 font-black uppercase tracking-tighter text-[10px]">Hari</th>
                                <th class="pb-4 font-black uppercase tracking-tighter text-[10px]">Jam Masuk</th>
                                <th class="pb-4 font-black uppercase tracking-tighter text-[10px]">Batas Telat</th>
                                <th class="pb-4 font-black uppercase tracking-tighter text-[10px]">Jam Pulang</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($days as $d): 
                                $row = $j_guru[$d] ?? ['jam_masuk'=>'', 'batas_telat'=>'', 'jam_pulang'=>''];
                            ?>
                            <tr class="group hover:bg-white/5 first:border-none">
                                <td class="py-4 font-bold text-slate-200">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2 h-2 rounded-full bg-amber-500/40 group-hover:bg-amber-500 transition-all"></div>
                                        <?= $d ?>
                                    </div>
                                </td>
                                <td class="py-3 px-1"><input type="time" name="jadwal[<?= $d ?>][masuk]" value="<?= $row['jam_masuk'] ?>" class="bg-slate-900 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-amber-500 focus:outline-none transition-all"></td>
                                <td class="py-3 px-1"><input type="time" name="jadwal[<?= $d ?>][telat]" value="<?= $row['batas_telat'] ?>" class="bg-slate-900 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-amber-500 focus:outline-none transition-all"></td>
                                <td class="py-3 px-1"><input type="time" name="jadwal[<?= $d ?>][pulang]" value="<?= $row['jam_pulang'] ?>" class="bg-slate-900 border border-white/10 rounded-lg px-4 py-2.5 text-xs text-white focus:border-amber-500 focus:outline-none transition-all"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-8 pt-6 border-t border-white/5 flex items-center justify-between">
                    <button type="submit" name="simpan_guru" class="bg-amber-600 hover:bg-amber-500 px-10 py-3.5 rounded-xl text-sm font-bold text-white shadow-lg shadow-amber-600/20 transition-all uppercase italic tracking-widest">
                        <i class="fas fa-save mr-2"></i>Simpan Jadwal GTK
                    </button>
                    <div class="text-right">
                        <p class="text-[10px] text-slate-500 italic">Guru tidak diabsen (Alpha) pada hari yang jam masuknya kosong.</p>
                        <p class="text-[10px] text-slate-500 italic">Jadwal ini berlaku secara global untuk seluruh GTK Aktif.</p>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 animate-fade-in">
        <!-- Sidebar Kelas -->
        <div class="lg:col-span-3 space-y-2">
            <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4 ml-2">Daftar Kelas</h3>
            <?php foreach ($kelas_list as $k): ?>
                <a href="?tab=kelas&id_kelas=<?= $k['id_kelas'] ?>" class="flex items-center justify-between p-4 rounded-xl border transition-all <?= $id_kelas_edit == $k['id_kelas'] ? 'bg-blue-600 border-blue-500 text-white shadow-lg shadow-blue-600/20' : 'bg-white/5 border-white/5 text-slate-400 hover:bg-white/10 hover:text-white' ?>">
                    <span class="font-bold text-sm"><?= clean($k['nama_kelas']) ?></span>
                    <i class="fas fa-chevron-right text-[10px] opacity-50"></i>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Editor Jadwal -->
        <div class="lg:col-span-9">
            <?php if ($id_kelas_edit): ?>
                <?php 
                    $selected_kelas = array_filter($kelas_list, function($k) use ($id_kelas_edit) { return $k['id_kelas'] == $id_kelas_edit; });
                    $current_kelas_name = reset($selected_kelas)['nama_kelas'];
                ?>
                <div class="glass rounded-2xl p-6 border border-white/5 shadow-2xl relative overflow-hidden">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-bold text-white leading-none mb-1">Edit Jadwal: <?= clean($current_kelas_name) ?></h3>
                            <p class="text-xs text-slate-500">Tentukan waktu masuk dan pulang khusus untuk kelas ini.</p>
                        </div>
                        <button onclick="document.getElementById('modalSalin').classList.remove('hidden')" class="bg-white/5 hover:bg-white/10 text-xs font-bold px-4 py-2 rounded-lg border border-white/10 transition-all">
                            <i class="fas fa-copy mr-2 text-blue-400"></i>Salin Dari Kelas Lain
                        </button>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="id_kelas" value="<?= $id_kelas_edit ?>">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-slate-500 border-b border-white/5">
                                        <th class="pb-4 font-black uppercase tracking-tighter text-[10px]">Hari</th>
                                        <th class="pb-4 font-black uppercase tracking-tighter text-[10px]">Jam Masuk</th>
                                        <th class="pb-4 font-black uppercase tracking-tighter text-[10px]">Batas Telat</th>
                                        <th class="pb-4 font-black uppercase tracking-tighter text-[10px]">Jam Pulang</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <?php foreach ($days as $d): 
                                        $row = $jadwal_khusus[$d] ?? ['jam_masuk'=>'', 'batas_telat'=>'', 'jam_pulang'=>''];
                                    ?>
                                    <tr class="group hover:bg-white/5 first:border-none">
                                        <td class="py-4 font-bold text-slate-300">
                                            <div class="flex items-center gap-3">
                                                <div class="w-2 h-2 rounded-full bg-blue-500/40 group-hover:bg-blue-500 transition-all"></div>
                                                <?= $d ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-1"><input type="time" name="jadwal[<?= $d ?>][masuk]" value="<?= $row['jam_masuk'] ?>" class="bg-slate-900 border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:border-blue-500 focus:outline-none transition-all"></td>
                                        <td class="py-3 px-1"><input type="time" name="jadwal[<?= $d ?>][telat]" value="<?= $row['batas_telat'] ?>" class="bg-slate-900 border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:border-blue-500 focus:outline-none transition-all"></td>
                                        <td class="py-3 px-1"><input type="time" name="jadwal[<?= $d ?>][pulang]" value="<?= $row['jam_pulang'] ?>" class="bg-slate-900 border border-white/10 rounded-lg px-3 py-2 text-xs text-white focus:border-blue-500 focus:outline-none transition-all"></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-8 pt-6 border-t border-white/5">
                            <button type="submit" name="simpan_kelas" class="bg-blue-600 hover:bg-blue-500 px-10 py-3 rounded-xl text-sm font-bold shadow-lg shadow-blue-600/20 transition-all">
                                <i class="fas fa-save mr-2"></i>Simpan Jadwal <?= clean($current_kelas_name) ?>
                            </button>
                            <p class="mt-4 text-[10px] text-slate-600 italic">Kosongkan jam masuk jika pada hari tersebut kelas tidak memiliki jadwal absen (akan dianggap sebagai HARI LIBUR/TANGGAL MERAH).</p>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="glass rounded-2xl p-10 border-2 border-dashed border-white/5 flex flex-col items-center justify-center text-center opacity-40">
                    <div class="w-20 h-20 bg-slate-800 rounded-full flex items-center justify-center text-4xl mb-6 text-slate-600"><i class="fas fa-calendar-alt"></i></div>
                    <h4 class="text-xl font-bold text-white mb-2">Pilih Kelas Terlebih Dahulu</h4>
                    <p class="text-sm text-slate-500 max-w-xs">Silakan pilih kelas dari daftar di sebelah kiri untuk mulai mengatur jadwal khusus mingguan.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Modal Salin Jadwal -->
<div id="modalSalin" class="fixed inset-0 z-50 flex items-center justify-center px-4 hidden">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="glass w-full max-w-md relative z-10 p-6 rounded-2xl border border-white/10 shadow-2xl animate-zoom-in">
        <h3 class="text-lg font-bold text-white mb-4">Salin Jadwal Absen</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="to_kelas" value="<?= $id_kelas_edit ?>">
            <div>
                <label class="block text-xs text-slate-400 mb-1">Salin Dari Kelas:</label>
                <select name="from_kelas" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                    <?php foreach ($kelas_list as $k): if($k['id_kelas'] != $id_kelas_edit): ?>
                        <option value="<?= $k['id_kelas'] ?>"><?= clean($k['nama_kelas']) ?></option>
                    <?php endif; endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-400 mb-1">Tujuan:</label>
                <div class="p-3 bg-blue-600/20 border border-blue-500/30 rounded-lg text-blue-300 text-sm font-bold">
                    <?= clean($current_kelas_name ?? '') ?>
                </div>
            </div>
            <div class="pt-2 flex gap-2">
                <button type="submit" name="salin_jadwal" class="flex-1 bg-blue-600 hover:bg-blue-500 py-2.5 rounded-xl text-sm font-bold transition-all shadow-lg shadow-blue-600/20">Konfirmasi Salin</button>
                <button type="button" onclick="this.closest('#modalSalin').classList.add('hidden')" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-400 hover:text-white transition-all">Batal</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
