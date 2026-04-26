<?php
$page_title = 'Pengaturan Tarif Granular';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin', 'bendahara']);
cek_fitur('keuangan');

// --- POST HANDLERS ---
if (isset($_POST['save_tarif'])) {
    $id_jenis = (int)$_POST['id_jenis'];
    $id_kelas = (int)$_POST['id_kelas'];
    $mode = $_POST['mode']; // 'kelas', 'kategori', 'siswa'
    $nominal = (float)($_POST['nominal'] ?? 0);
    $bulan = !empty($_POST['bulan']) ? (int)$_POST['bulan'] : null;

    if ($mode == 'kelas') {
        // Hapus spesifik lain jika ingin mereset ke kelas? Atau biarkan overlay?
        // Untuk kemudahan, kita simpan sebagai tarif dasar kelas
        save_tarif($pdo, $id_jenis, $id_kelas, null, null, null, $nominal);
    } 
    elseif ($mode == 'kategori') {
        $kat = $_POST['kategori_siswa'];
        save_tarif($pdo, $id_jenis, $id_kelas, null, $kat, null, $nominal);
    }
    elseif ($mode == 'siswa') {
        $id_siswa = (int)$_POST['id_siswa'];
        save_tarif($pdo, $id_jenis, $id_kelas, $id_siswa, null, $bulan, $nominal);
    }

    flash('msg', 'Tarif berhasil diperbarui!', 'success');
    header("Location: tarif.php?id_jenis=$id_jenis&id_kelas=$id_kelas");
    exit;
}

if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM tbl_tarif WHERE id_tarif=?")->execute([$_GET['hapus']]);
    flash('msg', 'Tarif dihapus!', 'warning');
    header("Location: tarif.php");
    exit;
}

function save_tarif($pdo, $id_jenis, $id_kelas, $id_siswa, $kategori, $bulan, $nominal) {
    // 1. Ensure students stay synced if being saved by category
    if($kategori) {
        $pdo->prepare("UPDATE tbl_siswa SET kategori=? WHERE status_keluarga=? AND kategori='Reguler'")->execute([$kategori, $kategori]);
    }
    
    // Check existing with exact same scope
    $sql = "SELECT id_tarif FROM tbl_tarif WHERE id_jenis=? AND id_kelas=?";
    $params = [$id_jenis, $id_kelas];
    
    if ($id_siswa) { $sql .= " AND id_siswa=?"; $params[] = $id_siswa; } else { $sql .= " AND id_siswa IS NULL"; }
    if ($bulan) { $sql .= " AND bulan=?"; $params[] = $bulan; } else { $sql .= " AND bulan IS NULL"; }
    if ($kategori) { $sql .= " AND kategori_siswa=?"; $params[] = $kategori; } else { $sql .= " AND kategori_siswa IS NULL"; }

    $check = $pdo->prepare($sql);
    $check->execute($params);
    $id = $check->fetchColumn();

    if ($id) {
        $pdo->prepare("UPDATE tbl_tarif SET nominal=? WHERE id_tarif=?")->execute([$nominal, $id]);
    } else {
        $pdo->prepare("INSERT INTO tbl_tarif (id_jenis, id_kelas, id_siswa, kategori_siswa, bulan, nominal) VALUES (?,?,?,?,?,?)")
            ->execute([$id_jenis, $id_kelas, $id_siswa, $kategori, $bulan, $nominal]);
    }
}

// --- DATA FETCHING ---
$jenis_list = $pdo->query("SELECT * FROM tbl_jenis_bayar ORDER BY nama_jenis")->fetchAll();
$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();

$sel_jenis = (int)($_GET['id_jenis'] ?? ($jenis_list[0]['id_jenis'] ?? 0));
$sel_kelas = (int)($_GET['id_kelas'] ?? ($kelas_list[0]['id_kelas'] ?? 0));
$sel_tab = $_GET['tab'] ?? 'kelas';

$current_jenis = null;
foreach($jenis_list as $j) if($j['id_jenis'] == $sel_jenis) $current_jenis = $j;

$siswa_list = [];
if ($sel_kelas) {
    $siswa_list = $pdo->prepare("SELECT id_siswa, nama, nisn, kategori FROM tbl_siswa WHERE id_kelas=? AND status='Aktif' ORDER BY nama");
    $siswa_list->execute([$sel_kelas]);
    $siswa_list = $siswa_list->fetchAll();
}

$bulan_list = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex flex-col lg:flex-row gap-6">
    <!-- Sidebar Selector -->
    <div class="w-full lg:w-80 shrink-0 space-y-4">
        <div class="glass rounded-2xl p-5 border border-white/10 shadow-xl">
            <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2"><i class="fas fa-filter text-blue-400"></i> Pilih Konteks</h3>
            <form method="GET" class="space-y-4">
                <input type="hidden" name="tab" id="active-tab-field" value="<?= $sel_tab ?>">
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1.5 tracking-widest">Jenis Pembayaran</label>
                    <select name="id_jenis" onchange="this.form.submit()" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-blue-500 outline-none">
                        <?php foreach($jenis_list as $j): ?>
                        <option value="<?= $j['id_jenis'] ?>" <?= $sel_jenis==$j['id_jenis']?'selected':'' ?>><?= clean($j['nama_jenis']) ?> (<?= $j['tipe'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1.5 tracking-widest">Kelas</label>
                    <select name="id_kelas" onchange="this.form.submit()" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm focus:border-blue-500 outline-none">
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach($kelas_list as $k): ?>
                        <option value="<?= $k['id_kelas'] ?>" <?= $sel_kelas==$k['id_kelas']?'selected':'' ?>><?= clean($k['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="glass rounded-2xl p-5 border border-amber-500/10 bg-amber-500/5">
            <h4 class="text-xs font-bold text-amber-500 mb-2 italic"><i class="fas fa-info-circle mr-1"></i> Penjelasan Hirarki</h4>
            <ul class="text-[10px] text-slate-400 space-y-2 leading-relaxed">
                <li>1. <b>Siswa + Bulan:</b> Tarif paling prioritas untuk siswa dan bulan tertentu.</li>
                <li>2. <b>Siswa:</b> Berlaku untuk siswa tersebut di semua bulan.</li>
                <li>3. <b>Kategori:</b> Berlaku untuk semua siswa yang berstatus Yatim/Miskin/dll.</li>
                <li>4. <b>Kelas:</b> Tarif dasar jika tidak ada pengaturan khusus di atas.</li>
            </ul>
        </div>
    </div>

    <!-- Main Workspace -->
    <div class="flex-1 min-w-0 space-y-6">
        <?php if(!$sel_kelas): ?>
            <div class="glass rounded-3xl p-12 text-center border border-dashed border-white/10">
                <i class="fas fa-hand-pointer text-4xl text-slate-600 mb-4 animate-bounce"></i>
                <h3 class="text-xl font-bold text-slate-400">Silakan pilih kelas terlebih dahulu</h3>
                <p class="text-sm text-slate-500">Pilih jenis pembayaran dan kelas di sidebar kiri untuk mulai mengatur tarif.</p>
            </div>
        <?php else: ?>
            
            <?= alert_flash('msg') ?>

            <!-- Tab Switcher (UI Only) -->
            <div class="flex gap-2 p-1 bg-slate-900/50 rounded-2xl border border-white/5 w-fit mb-4">
                <button onclick="switchTab('kelas')" id="tab-kelas" class="tab-btn <?= $sel_tab=='kelas'?'active':'' ?> px-4 py-2 rounded-xl text-xs font-bold">Global Kelas</button>
                <button onclick="switchTab('kategori')" id="tab-kategori" class="tab-btn <?= $sel_tab=='kategori'?'active':'' ?> px-4 py-2 rounded-xl text-xs font-bold">Kategori Status</button>
                <button onclick="switchTab('siswa')" id="tab-siswa" class="tab-btn <?= $sel_tab=='siswa'?'active':'' ?> px-4 py-2 rounded-xl text-xs font-bold">Per Siswa</button>
            </div>

            <!-- Global Kelas Section -->
            <section id="section-kelas" class="tarif-section <?= $sel_tab=='kelas'?'':'hidden' ?>">
                <div class="glass rounded-3xl p-8 border border-blue-500/10 relative overflow-hidden">
                    <div class="absolute -top-12 -right-12 w-48 h-48 bg-blue-500/10 rounded-full blur-3xl"></div>
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2"><i class="fas fa-globe text-blue-400"></i> Tarif Dasar Kelas</h3>
                    <p class="text-xs text-slate-400 mb-6">Tarif ini akan berlaku untuk SELURUH SISWA di kelas ini, kecuali jika ada pengaturan khusus di tab lain.</p>
                    
                    <form method="POST" class="flex items-end gap-4 max-w-md">
                        <input type="hidden" name="id_jenis" value="<?= $sel_jenis ?>">
                        <input type="hidden" name="id_kelas" value="<?= $sel_kelas ?>">
                        <input type="hidden" name="mode" value="kelas">
                        <div class="flex-1">
                            <label class="block text-[10px] font-bold text-slate-500 mb-1 tracking-widest uppercase">Nominal Tarif Dasar (Rp)</label>
                            <input type="number" name="nominal" required placeholder="0" class="w-full bg-slate-900 border border-white/10 rounded-2xl px-6 py-4 text-xl font-bold text-emerald-400 focus:border-emerald-500 outline-none shadow-inner">
                        </div>
                        <button type="submit" name="save_tarif" class="bg-emerald-600 hover:bg-emerald-500 text-white px-8 py-4 rounded-2xl font-bold transition-all shadow-lg shadow-emerald-600/20">Simpan</button>
                    </form>
                    
                    <?php 
                    $stmt_g = $pdo->prepare("SELECT id_tarif, nominal FROM tbl_tarif WHERE id_jenis=? AND id_kelas=? AND id_siswa IS NULL AND kategori_siswa IS NULL AND bulan IS NULL");
                    $stmt_g->execute([$sel_jenis, $sel_kelas]);
                    if($existing_kelas_tarif = $stmt_g->fetch()):
                    ?>
                    <div class="mt-6 p-4 bg-white/5 border border-white/10 rounded-2xl max-w-md flex items-center justify-between">
                        <div>
                            <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest mb-1">Tarif Saat Ini</p>
                            <p class="text-lg font-bold text-emerald-400"><?= rupiah($existing_kelas_tarif['nominal']) ?></p>
                        </div>
                        <a href="?hapus=<?= $existing_kelas_tarif['id_tarif'] ?>&id_jenis=<?= $sel_jenis ?>&id_kelas=<?= $sel_kelas ?>" onclick="return confirm('Hapus tarif dasar kelas ini?');" class="bg-rose-500/10 text-rose-500 hover:bg-rose-500 hover:text-white px-4 py-2 rounded-xl text-xs font-bold transition-all border border-rose-500/20">
                            <i class="fas fa-trash-alt mr-2"></i>Hapus Tarif
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Kategori Section -->
            <section id="section-kategori" class="tarif-section <?= $sel_tab=='kategori'?'':'hidden' ?>">
                <div class="glass rounded-3xl p-8 border border-purple-500/10">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2"><i class="fas fa-tags text-purple-400"></i> Tarif Berdasarkan Kategori</h3>
                    <p class="text-xs text-slate-400 mb-6">Atur tarif khusus untuk kelompok siswa tertentu (misal: Siswa Yatim gratis).</p>
                    
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-white/5 p-6 rounded-2xl border border-white/5 mb-8">
                        <input type="hidden" name="id_jenis" value="<?= $sel_jenis ?>">
                        <input type="hidden" name="id_kelas" value="<?= $sel_kelas ?>">
                        <input type="hidden" name="mode" value="kategori">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase tracking-widest">Kategori Siswa</label>
                            <select name="kategori_siswa" required class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm">
                                <?php foreach(['Reguler','Yatim','Piatu','Yatim Piatu','Prestasi','Miskin'] as $k): ?><option value="<?= $k ?>"><?= $k ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase tracking-widest">Nominal Tarif</label>
                            <input type="number" name="nominal" required placeholder="0" class="w-full bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm font-bold text-emerald-400">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" name="save_tarif" class="w-full bg-purple-600 hover:bg-purple-500 py-2.5 rounded-xl text-sm font-bold transition-all">Terapkan Kategori</button>
                        </div>
                    </form>

                    <div class="table-container">
                        <table class="w-full text-xs">
                            <thead class="text-slate-500 border-b border-white/10"><tr class="text-left"><th class="pb-2">Kategori</th><th class="pb-2">Nominal</th><th class="pb-2 text-right">Aksi</th></tr></thead>
                            <tbody class="divide-y divide-white/5">
                                <?php 
                                $stmt = $pdo->prepare("SELECT * FROM tbl_tarif WHERE id_jenis=? AND id_kelas=? AND kategori_siswa IS NOT NULL");
                                $stmt->execute([$sel_jenis, $sel_kelas]);
                                while($r = $stmt->fetch()):
                                ?>
                                <tr class="hover:bg-white/5 font-medium">
                                    <td class="py-3 px-1 text-purple-400"><?= $r['kategori_siswa'] ?></td>
                                    <td class="py-3 text-emerald-400"><?= rupiah($r['nominal']) ?></td>
                                    <td class="py-3 text-right"><a href="?hapus=<?= $r['id_tarif'] ?>" class="text-rose-500 hover:text-rose-400 p-1.5"><i class="fas fa-trash"></i></a></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Per Siswa Section -->
            <section id="section-siswa" class="tarif-section <?= $sel_tab=='siswa'?'':'hidden' ?>">
                <div class="glass rounded-3xl p-8 border border-emerald-500/10">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2"><i class="fas fa-user-graduate text-emerald-400"></i> Tarif Spesifik Siswa</h3>
                    <p class="text-xs text-slate-400 mb-6">Gunakan mode ini untuk penyesuaian person-to-person atau penyesuaian nominal di bulan tertentu.</p>
                    
                    <div class="table-container bg-white/5 rounded-2xl border border-white/10 p-2 overflow-x-auto">
                        <table class="w-full text-[11px]">
                            <thead class="text-slate-500 border-b border-white/10 uppercase tracking-tighter">
                                <tr class="text-left">
                                    <th class="py-3 px-4">Nama Siswa</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3">Setting Khusus</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach($siswa_list as $s): 
                                    $v_colors = [
                                        'Reguler' => 'bg-slate-500/10 text-slate-400',
                                        'Prestasi' => 'bg-emerald-500/10 text-emerald-400',
                                        'Miskin' => 'bg-purple-500/10 text-purple-400',
                                        'Yatim' => 'bg-amber-500/10 text-amber-400',
                                        'Piatu' => 'bg-amber-500/10 text-amber-400',
                                        'Yatim Piatu' => 'bg-rose-500/10 text-rose-400'
                                    ];
                                    $kat_v_color = $v_colors[$s['kategori']] ?? 'bg-blue-500/10 text-blue-400';
                                ?>
                                <tr class="hover:bg-white/5 group">
                                    <td class="py-4 px-4 font-bold text-white">
                                        <?= clean($s['nama']) ?>
                                        <p class="text-[9px] text-slate-500 font-normal mb-1"><?= clean($s['nisn']) ?></p>
                                        <?php $tarif_aktif = get_tarif($pdo, $s['id_siswa'], $sel_jenis); ?>
                                        <?php if($tarif_aktif > 0): ?>
                                            <span class="inline-block bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded text-[9px] font-bold">
                                                Tarif Aktif: <?= rupiah($tarif_aktif) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-block bg-rose-500/10 text-rose-400 border border-rose-500/20 px-2 py-0.5 rounded text-[9px] font-bold">
                                                Belum Ada Tarif
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4"><span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase <?= $kat_v_color ?>"><?= empty($s['kategori']) ? 'Reguler' : $s['kategori'] ?></span></td>
                                    <td class="py-4">
                                        <form method="POST" class="flex items-center gap-2">
                                            <input type="hidden" name="id_jenis" value="<?= $sel_jenis ?>">
                                            <input type="hidden" name="id_kelas" value="<?= $sel_kelas ?>">
                                            <input type="hidden" name="id_siswa" value="<?= $s['id_siswa'] ?>">
                                            <input type="hidden" name="mode" value="siswa">
                                            
                                            <?php if($current_jenis['tipe'] == 'Bulanan'): ?>
                                            <select name="bulan" class="bg-slate-900 border border-white/10 rounded-lg px-2 py-1.5 text-[10px]">
                                                <option value="">Semua Bln</option>
                                                <?php foreach($bulan_list as $num => $nama): ?><option value="<?= $num ?>"><?= substr($nama,0,3) ?></option><?php endforeach; ?>
                                            </select>
                                            <?php endif; ?>
                                            
                                            <input type="number" name="nominal" placeholder="Nominal" class="w-24 bg-slate-900 border border-white/10 rounded-lg px-2 py-1.5 text-[10px] text-emerald-400 font-bold focus:border-emerald-500 outline-none">
                                            
                                            <button type="submit" name="save_tarif" class="w-8 h-8 rounded-lg bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600 hover:text-white transition-all flex items-center justify-center"><i class="fas fa-save text-[10px]"></i></button>
                                        </form>
                                        
                                        <!-- Lihat Existing -->
                                        <?php 
                                        $check = $pdo->prepare("SELECT * FROM tbl_tarif WHERE id_siswa = ? AND id_jenis = ?");
                                        $check->execute([$s['id_siswa'], $sel_jenis]);
                                        $existing = $check->fetchAll();
                                        if($existing): ?>
                                        <div class="mt-1.5 flex flex-wrap gap-1">
                                            <?php foreach($existing as $e): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-white/5 border border-white/10 rounded text-[9px] text-slate-400">
                                                <?= $e['bulan'] ? $bulan_list[$e['bulan']] : 'Setiap Bln' ?>: <b><?= rupiah($e['nominal']) ?></b>
                                                <a href="?hapus=<?= $e['id_tarif'] ?>&id_jenis=<?= $sel_jenis ?>&id_kelas=<?= $sel_kelas ?>" class="text-rose-500 hover:text-white"><i class="fas fa-times"></i></a>
                                            </span>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        <?php endif; ?>
    </div>
</div>

<style>
.tab-btn { color: #94a3b8; transition: all 0.3s; }
.tab-btn.active { background: #3b82f6; color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4); }
.tab-btn:not(.active):hover { color: white; background: rgba(255,255,255,0.05); }
.glass { backdrop-filter: blur(12px); background: rgba(15, 23, 42, 0.6); }

@keyframes zoom-in { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.tarif-section { animation: zoom-in 0.3s ease-out; }
</style>

<script>
function switchTab(mode) {
    // Buttons
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    const btn = document.getElementById('tab-' + mode);
    if(btn) btn.classList.add('active');

    // Sections
    document.querySelectorAll('.tarif-section').forEach(s => s.classList.add('hidden'));
    const sect = document.getElementById('section-' + mode);
    if(sect) sect.classList.remove('hidden');

    // Persist to form
    const field = document.getElementById('active-tab-field');
    if (field) field.value = mode;

    // Persist to URL
    const url = new URL(window.location);
    url.searchParams.set('tab', mode);
    window.history.replaceState({}, '', url);
}

function confirmDelete(url) {
    const activeTab = document.getElementById('active-tab-field').value;
    if(confirm('Hapus pengaturan tarif ini?')) {
        window.location.href = url + '&tab=' + activeTab;
    }
}
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
