<?php
$page_title = 'Pembayaran Siswa';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin','bendahara']);
cek_fitur('keuangan');

$ta_aktif = get_ta_aktif($pdo);
$ta_list = $pdo->query("SELECT * FROM tbl_tahun_ajaran ORDER BY id_ta DESC")->fetchAll();

// Selected TA (default: aktif)
$sel_ta_id = isset($_GET['ta']) ? (int)$_GET['ta'] : ($ta_aktif['id_ta'] ?? 0);
$ta = null;
if ($sel_ta_id) {
    $stmt_ta = $pdo->prepare("SELECT * FROM tbl_tahun_ajaran WHERE id_ta=?");
    $stmt_ta->execute([$sel_ta_id]);
    $ta = $stmt_ta->fetch();
}
if (!$ta) $ta = $ta_aktif;

$kelas_list = $pdo->query("SELECT * FROM tbl_kelas ORDER BY nama_kelas")->fetchAll();

// Process payment - Bulanan
if (isset($_POST['bayar'])) {
    $id_siswa = (int)$_POST['id_siswa'];
    $id_jenis = (int)$_POST['id_jenis'];
    $bulan = (int)$_POST['bulan'];
    $tahun = (int)$_POST['tahun_bayar'];
    $jumlah = (int)$_POST['jumlah'];
    $cara = clean($_POST['cara']);
    $ket = clean($_POST['ket'] ?? '');

    // Logika Tabungan
    if ($cara === 'Tabungan') {
        if (!fitur_aktif('tabungan')) {
            flash('msg', 'Pembayaran via Tabungan dinonaktifkan!', 'bg-rose-600');
            header('Location: pembayaran.php?kelas='.(int)($_POST['sel_kelas']??0).'&siswa='.$id_siswa); exit;
        }
        $nas_stmt = $pdo->prepare("SELECT id_nasabah, saldo FROM tbl_nasabah WHERE id_siswa=?");
        $nas_stmt->execute([$id_siswa]);
        $nas = $nas_stmt->fetch();
        
        if (!$nas || $nas['saldo'] < $jumlah) {
            flash('msg', 'Saldo tabungan tidak cukup atau rekening tidak ditemukan!', 'bg-rose-600');
            header('Location: pembayaran.php?kelas='.(int)($_POST['sel_kelas']??0).'&siswa='.$id_siswa); exit;
        }

        // Potong Saldo
        $pdo->prepare("UPDATE tbl_nasabah SET saldo = saldo - ? WHERE id_nasabah=?")->execute([$jumlah, $nas['id_nasabah']]);
        $pdo->prepare("UPDATE tbl_siswa SET saldo = saldo - ? WHERE id_siswa=?")->execute([$jumlah, $id_siswa]);
        
        // Catat Mutasi
        $pdo->prepare("INSERT INTO tbl_transaksi_tabungan (id_nasabah, jenis, jumlah, keterangan) VALUES (?, 'Kredit', ?, ?)")
            ->execute([$nas['id_nasabah'], $jumlah, "Pembayaran: $ket (Bulanan)"]);
            
        // Notif WA Tabungan
        if (file_exists(__DIR__ . '/../api/wa_helper.php')) {
            require_once __DIR__ . '/../api/wa_helper.php';
            wa_notif_tabungan($id_siswa, 'Tarik', $jumlah, "Pembayaran Tagihan", $nas['saldo'] - $jumlah);
        }
    }

    $pdo->prepare("INSERT INTO tbl_pembayaran (id_siswa,id_jenis,bulan,tahun,jumlah_bayar,cara_bayar,teller,keterangan,id_ta) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([$id_siswa, $id_jenis, $bulan, $tahun, $jumlah, $cara, $_SESSION['nama'], $ket, $ta['id_ta']??null]);
    
    $last_id = $pdo->lastInsertId();
    
    // WA Notification
    if (file_exists(__DIR__ . '/../api/wa_helper.php')) {
        require_once __DIR__ . '/../api/wa_helper.php';
        $jenis_nama = $pdo->prepare("SELECT nama_jenis FROM tbl_jenis_bayar WHERE id_jenis=?");
        $jenis_nama->execute([$id_jenis]);
        $nama_j = $jenis_nama->fetchColumn() . " - " . bulan_indo($bulan);
        wa_notif_pembayaran($id_siswa, $jumlah, $nama_j, $cara, date('Y-m-d'));
    }
    
     flash('msg', 'Pembayaran berhasil dicatat! <a href="cetak_kwitansi.php?siswa='.$id_siswa.'&tanggal='.date('Y-m-d').'" target="_blank" class="underline font-bold">🖨️ Cetak Semua Hari Ini</a>');
    header('Location: pembayaran.php?kelas='.(int)($_POST['sel_kelas']??0).'&siswa='.$id_siswa); exit;
}

// Process payment - Bebas
if (isset($_POST['bayar_bebas'])) {
    $id_siswa = (int)$_POST['id_siswa'];
    $id_jenis = (int)$_POST['id_jenis'];
    $jumlah = (int)$_POST['jumlah'];
    $cara = clean($_POST['cara']);
    $ket = clean($_POST['ket'] ?? '');

    // Logika Tabungan Bebas
    if ($cara === 'Tabungan') {
        if (!fitur_aktif('tabungan')) {
            flash('msg', 'Pembayaran via Tabungan dinonaktifkan!', 'bg-rose-600');
            header('Location: pembayaran.php?kelas='.(int)($_POST['sel_kelas']??0).'&siswa='.$id_siswa); exit;
        }
        $nas_stmt = $pdo->prepare("SELECT id_nasabah, saldo FROM tbl_nasabah WHERE id_siswa=?");
        $nas_stmt->execute([$id_siswa]);
        $nas = $nas_stmt->fetch();
        
        if (!$nas || $nas['saldo'] < $jumlah) {
            flash('msg', 'Saldo tabungan tidak cukup atau rekening tidak ditemukan!', 'bg-rose-600');
            header('Location: pembayaran.php?kelas='.(int)($_POST['sel_kelas']??0).'&siswa='.$id_siswa); exit;
        }

        // Potong Saldo
        $pdo->prepare("UPDATE tbl_nasabah SET saldo = saldo - ? WHERE id_nasabah=?")->execute([$jumlah, $nas['id_nasabah']]);
        $pdo->prepare("UPDATE tbl_siswa SET saldo = saldo - ? WHERE id_siswa=?")->execute([$jumlah, $id_siswa]);
        
        // Catat Mutasi
        $pdo->prepare("INSERT INTO tbl_transaksi_tabungan (id_nasabah, jenis, jumlah, keterangan) VALUES (?, 'Kredit', ?, ?)")
            ->execute([$nas['id_nasabah'], $jumlah, "Pembayaran: $ket (Bebas)"]);

        // Notif WA Tabungan
        if (file_exists(__DIR__ . '/../api/wa_helper.php')) {
            require_once __DIR__ . '/../api/wa_helper.php';
            wa_notif_tabungan($id_siswa, 'Tarik', $jumlah, "Pembayaran Tagihan Bebas", $nas['saldo'] - $jumlah);
        }
    }

    $pdo->prepare("INSERT INTO tbl_pembayaran_bebas (id_siswa,id_jenis,jumlah_bayar,cara_bayar,teller,keterangan) VALUES (?,?,?,?,?,?)")
        ->execute([$id_siswa, $id_jenis, $jumlah, $cara, $_SESSION['nama'], $ket]);
    
    $last_id_bebas = $pdo->lastInsertId();
    
    // WA Notification Bebas
    if (file_exists(__DIR__ . '/../api/wa_helper.php')) {
        require_once __DIR__ . '/../api/wa_helper.php';
        $jenis_nama = $pdo->prepare("SELECT nama_jenis FROM tbl_jenis_bayar WHERE id_jenis=?");
        $jenis_nama->execute([$id_jenis]);
        $nama_j = $jenis_nama->fetchColumn();
        wa_notif_pembayaran($id_siswa, $jumlah, $nama_j, $cara, date('Y-m-d'));
    }

    flash('msg', 'Pembayaran bebas berhasil dicatat! <a href="cetak_kwitansi.php?siswa='.$id_siswa.'&tanggal='.date('Y-m-d').'" target="_blank" class="underline font-bold">🖨️ Cetak Semua Hari Ini</a>');
    header('Location: pembayaran.php?kelas='.(int)($_POST['sel_kelas']??0).'&siswa='.$id_siswa); exit;
}

// Delete payment
if (isset($_GET['hapus_bayar'])) {
    $id_hapus = (int)$_GET['hapus_bayar'];
    $tipe = $_GET['tipe'] ?? 'Bulanan';
    
    if ($tipe === 'Bebas') {
        $pdo->prepare("DELETE FROM tbl_pembayaran_bebas WHERE id_bebas=?")->execute([$id_hapus]);
    } else {
        $pdo->prepare("DELETE FROM tbl_pembayaran WHERE id_pembayaran=?")->execute([$id_hapus]);
    }
    
    flash('msg','Pembayaran dihapus!','warning');
    header('Location: pembayaran.php?kelas='.(int)($_GET['kelas']??0).'&siswa='.(int)($_GET['siswa']??0)); exit;
}

$sel_kelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 0;
$sel_siswa = isset($_GET['siswa']) ? (int)$_GET['siswa'] : 0;

$siswa_list = [];
if ($sel_kelas) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_siswa WHERE id_kelas=? AND status='Aktif' ORDER BY nama");
    $stmt->execute([$sel_kelas]);
    $siswa_list = $stmt->fetchAll();
}

$siswa_data = null;
if ($sel_siswa) {
    $stmt = $pdo->prepare("SELECT s.*,k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE s.id_siswa=?");
    $stmt->execute([$sel_siswa]);
    $siswa_data = $stmt->fetch();
}

require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<!-- Filter -->
<div class="glass rounded-xl p-5 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div><label class="block text-xs text-slate-400 mb-1">Tahun Ajaran</label>
            <select name="ta" onchange="this.form.submit()" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm min-w-[160px] font-medium">
                <?php foreach ($ta_list as $tl): ?>
                <option value="<?= $tl['id_ta'] ?>" <?= ($ta['id_ta']??0)==$tl['id_ta']?'selected':'' ?>><?= clean($tl['tahun']) ?> <?= $tl['status']=='aktif'?'✦':'' ?></option>
                <?php endforeach; ?>
            </select></div>
        <div><label class="block text-xs text-slate-400 mb-1">Kelas</label>
            <select name="kelas" onchange="this.form.submit()" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                <option value="">-- Pilih Kelas --</option>
                <?php foreach ($kelas_list as $k): ?><option value="<?= $k['id_kelas'] ?>" <?= $sel_kelas==$k['id_kelas']?'selected':'' ?>><?= clean($k['nama_kelas']) ?></option><?php endforeach; ?>
            </select></div>
        <?php if ($sel_kelas): ?>
        <div><label class="block text-xs text-slate-400 mb-1">Siswa</label>
            <select name="siswa" onchange="this.form.submit()" class="bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm">
                <option value="">-- Pilih Siswa --</option>
                <?php foreach ($siswa_list as $s): ?><option value="<?= $s['id_siswa'] ?>" <?= $sel_siswa==$s['id_siswa']?'selected':'' ?>><?= clean($s['nama']) ?> (<?= $s['nisn'] ?>)</option><?php endforeach; ?>
            </select></div>
        <?php endif; ?>
    </form>
</div>

<?php if ($siswa_data):
    // Get payment types filtered by selected TA
    $id_ta_filter = $ta['id_ta'] ?? 0;
    $jenis_bulanan = $pdo->prepare("SELECT * FROM tbl_jenis_bayar WHERE tipe='Bulanan' AND id_ta=? ORDER BY nama_jenis");
    $jenis_bulanan->execute([$id_ta_filter]);
    $jenis_bulanan = $jenis_bulanan->fetchAll();
    $jenis_bebas = $pdo->prepare("SELECT * FROM tbl_jenis_bayar WHERE tipe='Bebas' AND id_ta=? ORDER BY nama_jenis");
    $jenis_bebas->execute([$id_ta_filter]);
    $jenis_bebas = $jenis_bebas->fetchAll();

    // Academic Year months (Juli - Juni) based on TA aktif
    if ($ta && $ta['tgl_mulai'] && $ta['tgl_selesai']) {
        $ta_start_y = (int)date('Y', strtotime($ta['tgl_mulai']));
        $ta_end_y = (int)date('Y', strtotime($ta['tgl_selesai']));
    } else {
        $cm = (int)date('n');
        $ta_start_y = $cm >= 7 ? (int)date('Y') : (int)date('Y') - 1;
        $ta_end_y = $ta_start_y + 1;
    }
    $academic_months = [
        ['m' => 7, 'y' => $ta_start_y, 'name' => 'Juli'],
        ['m' => 8, 'y' => $ta_start_y, 'name' => 'Agustus'],
        ['m' => 9, 'y' => $ta_start_y, 'name' => 'September'],
        ['m' => 10, 'y' => $ta_start_y, 'name' => 'Oktober'],
        ['m' => 11, 'y' => $ta_start_y, 'name' => 'November'],
        ['m' => 12, 'y' => $ta_start_y, 'name' => 'Desember'],
        ['m' => 1, 'y' => $ta_end_y, 'name' => 'Januari'],
        ['m' => 2, 'y' => $ta_end_y, 'name' => 'Februari'],
        ['m' => 3, 'y' => $ta_end_y, 'name' => 'Maret'],
        ['m' => 4, 'y' => $ta_end_y, 'name' => 'April'],
        ['m' => 5, 'y' => $ta_end_y, 'name' => 'Mei'],
        ['m' => 6, 'y' => $ta_end_y, 'name' => 'Juni'],
    ];
?>

<!-- Siswa Info -->
<div class="glass rounded-xl p-5 mb-6">
    <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-xl font-bold shadow-lg shadow-blue-500/30"><?= strtoupper(substr($siswa_data['nama'],0,1)) ?></div>
        <div class="flex-1">
            <h3 class="font-bold text-lg"><?= clean($siswa_data['nama']) ?></h3>
            <p class="text-sm text-slate-400"><?= clean($siswa_data['nama_kelas']) ?> · NISN: <?= clean($siswa_data['nisn']) ?> · NIS: <?= clean($siswa_data['nis'] ?? '-') ?></p>
        </div>
        <div class="text-right hidden sm:block">
            <p class="text-xs text-slate-400">Saldo Tabungan</p>
            <p class="text-lg font-bold text-blue-400"><?= rupiah($siswa_data['saldo'] ?? 0) ?></p>
        </div>
    </div>
</div>

<!-- Tahun Ajaran Info -->
<div class="glass rounded-xl p-4 mb-6 flex items-center justify-between border border-blue-500/20">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
            <i class="fas fa-calendar-check text-white"></i>
        </div>
        <div>
            <p class="text-xs text-slate-400 uppercase tracking-widest font-bold">Tahun Ajaran Aktif</p>
            <p class="font-bold text-white"><?= clean($ta['tahun'] ?? '-') ?></p>
        </div>
    </div>
    <div class="text-right">
        <p class="text-xs text-slate-400">Periode Tagihan</p>
        <p class="text-sm font-medium text-blue-400"><i class="fas fa-calendar-alt mr-1"></i>Jul <?= $ta_start_y ?> — Jun <?= $ta_end_y ?></p>
    </div>
</div>

<!-- Tagihan Bulanan -->
<?php foreach ($jenis_bulanan as $jb): 
    $spp_standar = get_tarif($pdo, $siswa_data['id_siswa'], $jb['id_jenis']);
    // Hanya tampilkan jika siswa ini memiliki tarif yang di-set (> 0)
    if ($spp_standar <= 0) continue;
?>
<div class="glass rounded-xl p-5 mb-4 border border-blue-500/10">
    <h4 class="font-semibold text-sm mb-3 flex items-center justify-between">
        <span><i class="fas fa-calendar-alt text-blue-400 mr-2"></i><?= clean($jb['nama_jenis']) ?></span>
        <span class="text-[10px] bg-blue-500/10 px-2 py-0.5 rounded text-blue-400">Tarif Dasar: <?= rupiah($spp_standar) ?></span>
    </h4>
    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
        <?php foreach ($academic_months as $am):
            $bln = $am['m'];
            $nama_bln = $am['name'];
            $thn_bln = $am['y'];
            $paid = $pdo->prepare("SELECT * FROM tbl_pembayaran WHERE id_siswa=? AND id_jenis=? AND bulan=? AND tahun=?");
            $paid->execute([$siswa_data['id_siswa'], $jb['id_jenis'], $bln, $thn_bln]);
            $is_paid = $paid->fetch();
        ?>
        <div class="text-center p-2 rounded-lg <?= $is_paid ? 'bg-emerald-500/20 border border-emerald-500/30' : 'bg-slate-800/50 border border-white/5 hover:border-amber-500/30 transition-colors' ?>">
            <p class="text-xs font-medium"><?= substr($nama_bln,0,3) ?></p>
            <p class="text-[9px] text-slate-500"><?= $thn_bln ?></p>
            <?php if ($is_paid): ?>
                <i class="fas fa-check-circle text-emerald-400 text-lg my-1"></i>
                <p class="text-[10px] text-emerald-400">Lunas</p>
                <a href="cetak_kwitansi.php?id=<?= $is_paid['id_pembayaran'] ?>" target="_blank" class="text-[9px] text-blue-400 hover:underline block mt-0.5"><i class="fas fa-print"></i></a>
            <?php else: ?>
                <button onclick="openModal('modal_<?= $jb['id_jenis'].'_'.$bln.'_'.$thn_bln ?>')" class="text-amber-400 text-lg my-1 hover:text-amber-300 transition-colors"><i class="fas fa-times-circle"></i></button>
                <p class="text-[10px] text-amber-400">Bayar</p>
                <!-- Modal -->
                <div id="modal_<?= $jb['id_jenis'].'_'.$bln.'_'.$thn_bln ?>" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" onclick="if(event.target===this)closeModal(this.id)">
                    <div class="glass rounded-xl p-6 w-full max-w-md">
                        <h3 class="font-bold mb-4"><i class="fas fa-cash-register text-emerald-400 mr-2"></i>Bayar <?= clean($jb['nama_jenis']) ?> — <?= $nama_bln ?> <?= $thn_bln ?></h3>
                        <form method="POST" class="form-bayar">
                            <input type="hidden" name="id_siswa" value="<?= $siswa_data['id_siswa'] ?>">
                            <input type="hidden" name="id_jenis" value="<?= $jb['id_jenis'] ?>">
                            <input type="hidden" name="bulan" value="<?= $bln ?>">
                            <input type="hidden" name="tahun_bayar" value="<?= $thn_bln ?>">
                            <input type="hidden" name="sel_kelas" value="<?= $sel_kelas ?>">
                            <div class="space-y-3">
                                <?php $tarif_bln = get_tarif($pdo, $siswa_data['id_siswa'], $jb['id_jenis'], $bln); ?>
                                <div><label class="block text-xs text-slate-400 mb-1">Jumlah</label><input type="number" name="jumlah" value="<?= $tarif_bln ?>" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm text-emerald-400 font-bold"></div>
                                <div><label class="block text-xs text-slate-400 mb-1">Cara Bayar</label><select name="cara" onchange="checkTabungan(this)" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><option>Tunai</option><option>Transfer</option><?php if(fitur_aktif('tabungan')): ?><option>Tabungan</option><?php endif; ?></select>
                                    <div class="info-tabungan hidden mt-2 p-2 bg-blue-500/10 border border-blue-500/20 rounded-lg">
                                        <div class="flex justify-between text-[10px]">
                                            <span class="text-slate-400 uppercase font-bold">Saldo Tabungan:</span>
                                            <span class="text-white font-black"><?= rupiah($siswa_data['saldo'] ?? 0) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div><label class="block text-xs text-slate-400 mb-1">Keterangan</label><input type="text" name="ket" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm" placeholder="Opsional"></div>
                            </div>
                            <div class="flex gap-2 mt-4">
                                <button type="submit" name="bayar" class="bg-emerald-600 hover:bg-emerald-500 px-4 py-2 rounded-lg text-sm font-medium transition-colors"><i class="fas fa-check mr-1"></i>Bayar</button>
                                <button type="button" onclick="closeModal('modal_<?= $jb['id_jenis'].'_'.$bln.'_'.$thn_bln ?>')" class="bg-slate-600 hover:bg-slate-500 px-4 py-2 rounded-lg text-sm transition-colors">Batal</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Tagihan Bebas -->
<?php if (count($jenis_bebas) > 0): ?>
<div class="glass rounded-xl p-5 mb-4">
    <h4 class="font-semibold text-sm mb-3"><i class="fas fa-money-check text-purple-400 mr-2"></i>Pembayaran Bebas</h4>
    <?php foreach ($jenis_bebas as $jb):
        // Cek tarif
        $tarif_bebas = get_tarif($pdo, $siswa_data['id_siswa'], $jb['id_jenis']);
        if ($tarif_bebas <= 0) continue;

        $tb_stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah_bayar),0) FROM tbl_pembayaran_bebas WHERE id_siswa=? AND id_jenis=?");
        $tb_stmt->execute([$siswa_data['id_siswa'], $jb['id_jenis']]);
        $total_bayar = $tb_stmt->fetchColumn();
        $sisa_bayar = $tarif_bebas - $total_bayar;
        $is_lunas = $sisa_bayar <= 0;
    ?>
    <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg mb-2">
        <div>
            <span class="font-medium text-sm"><?= clean($jb['nama_jenis']) ?></span>
            <span class="text-[10px] text-slate-500 ml-2">(Tarif: <?= rupiah($tarif_bebas) ?>)</span>
            <span class="text-xs text-blue-400 font-bold ml-2">Sudah bayar: <?= rupiah($total_bayar) ?></span>
            <?php if(!$is_lunas): ?>
            <span class="text-xs text-rose-400 font-bold ml-2">Sisa: <?= rupiah($sisa_bayar) ?></span>
            <?php endif; ?>
        </div>
        <?php if($is_lunas): ?>
            <span class="bg-emerald-500/20 text-emerald-400 px-3 py-1 rounded text-xs font-bold transition-colors"><i class="fas fa-check mr-1"></i>Lunas</span>
        <?php else: ?>
            <button onclick="openModal('modal_bebas_<?= $jb['id_jenis'] ?>')" class="bg-purple-600 hover:bg-purple-500 px-3 py-1 rounded text-xs transition-colors">Cicil / Bayar</button>
        <?php endif; ?>
    </div>
    <div id="modal_bebas_<?= $jb['id_jenis'] ?>" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" onclick="if(event.target===this)closeModal(this.id)">
        <div class="glass rounded-xl p-6 w-full max-w-md">
            <h3 class="font-bold mb-4"><i class="fas fa-money-check text-purple-400 mr-2"></i>Bayar <?= clean($jb['nama_jenis']) ?></h3>
            <form method="POST" class="form-bayar"><input type="hidden" name="id_siswa" value="<?= $siswa_data['id_siswa'] ?>"><input type="hidden" name="id_jenis" value="<?= $jb['id_jenis'] ?>"><input type="hidden" name="sel_kelas" value="<?= $sel_kelas ?>">
                <div class="space-y-3">
                    <div><label class="block text-xs text-slate-400 mb-1">Jumlah</label><input type="number" name="jumlah" value="<?= $sisa_bayar > 0 ? $sisa_bayar : '' ?>" required class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
                    <div><label class="block text-xs text-slate-400 mb-1">Cara Bayar</label><select name="cara" onchange="checkTabungan(this)" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><option>Tunai</option><option>Transfer</option><?php if(fitur_aktif('tabungan')): ?><option>Tabungan</option><?php endif; ?></select>
                        <div class="info-tabungan hidden mt-2 p-2 bg-blue-500/10 border border-blue-500/20 rounded-lg">
                            <div class="flex justify-between text-[10px]">
                                <span class="text-slate-400 uppercase font-bold">Saldo Tabungan:</span>
                                <span class="text-white font-black"><?= rupiah($siswa_data['saldo'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                    <div><label class="block text-xs text-slate-400 mb-1">Keterangan</label><input type="text" name="ket" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
                </div>
                <div class="flex gap-2 mt-4"><button type="submit" name="bayar_bebas" class="bg-emerald-600 hover:bg-emerald-500 px-4 py-2 rounded-lg text-sm font-medium"><i class="fas fa-check mr-1"></i>Bayar</button><button type="button" onclick="closeModal('modal_bebas_<?= $jb['id_jenis'] ?>')" class="bg-slate-600 px-4 py-2 rounded-lg text-sm">Batal</button></div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Payment History -->
<div class="glass rounded-xl p-5">
    <div class="flex flex-wrap items-center justify-between mb-3 gap-2">
        <h4 class="font-semibold text-sm"><i class="fas fa-history text-amber-400 mr-2"></i>Riwayat Pembayaran</h4>
        <a href="cetak_kwitansi.php?siswa=<?= $siswa_data['id_siswa'] ?>&tanggal=<?= date('Y-m-d') ?>" target="_blank" class="bg-blue-600 hover:bg-blue-500 test-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors shadow-lg"><i class="fas fa-print mr-1"></i>Cetak Semua Hari Ini</a>
    </div>
    <div class="table-container"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">Tanggal</th><th class="pb-3">Jenis</th><th class="pb-3">Bulan</th><th class="pb-3">Jumlah</th><th class="pb-3">Cara</th><th class="pb-3">Teller</th><th class="pb-3">Aksi</th></tr></thead>
        <tbody>
        <?php 
        $hist = $pdo->prepare("
            SELECT id_pembayaran as id, tanggal_bayar, j.nama_jenis, bulan, jumlah_bayar, cara_bayar, teller, 'Bulanan' as tipe 
            FROM tbl_pembayaran p LEFT JOIN tbl_jenis_bayar j ON p.id_jenis=j.id_jenis WHERE p.id_siswa=? 
            UNION ALL 
            SELECT id_bebas as id, tanggal_bayar, j.nama_jenis, NULL as bulan, jumlah_bayar, cara_bayar, teller, 'Bebas' as tipe 
            FROM tbl_pembayaran_bebas pb LEFT JOIN tbl_jenis_bayar j ON pb.id_jenis=j.id_jenis WHERE pb.id_siswa=? 
            ORDER BY tanggal_bayar DESC LIMIT 30
        ");
        $hist->execute([$siswa_data['id_siswa'], $siswa_data['id_siswa']]);
        while ($h = $hist->fetch()): ?>
        <tr class="border-b border-white/5">
            <td class="py-2 text-xs"><?= tgl_indo(date('Y-m-d', strtotime($h['tanggal_bayar']))) ?></td>
            <td><?= clean($h['nama_jenis']) ?></td>
            <td><?= $h['bulan'] ? bulan_indo($h['bulan']) : '-' ?></td>
            <td class="text-emerald-400 font-medium"><?= rupiah($h['jumlah_bayar']) ?></td>
            <td><span class="px-2 py-0.5 rounded-full text-xs bg-blue-500/20 text-blue-400"><?= $h['cara_bayar'] ?></span></td>
            <td class="text-slate-400 text-xs"><?= clean($h['teller']) ?></td>
            <td class="flex gap-1">
                <?php if($h['tipe'] == 'Bulanan'): ?>
                <a href="cetak_kwitansi.php?id=<?= $h['id'] ?>" target="_blank" class="p-1.5 rounded bg-purple-600/20 text-purple-400 text-xs hover:bg-purple-600/40 transition-colors" title="Cetak Kwitansi"><i class="fas fa-print"></i></a>
                <?php else: ?>
                <a href="cetak_kwitansi.php?id_bebas=<?= $h['id'] ?>" target="_blank" class="p-1.5 rounded bg-blue-600/20 text-blue-400 text-xs hover:bg-blue-600/40 transition-colors" title="Cetak Kwitansi Cicilan"><i class="fas fa-print"></i></a>
                <?php endif; ?>
                <button onclick="confirmDelete('?hapus_bayar=<?= $h['id'] ?>&tipe=<?= $h['tipe'] ?>&kelas=<?= $sel_kelas ?>&siswa=<?= $sel_siswa ?>','pembayaran ini')" class="p-1.5 rounded bg-red-600/20 text-red-400 text-xs hover:bg-red-600/40 transition-colors" title="Hapus"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
        <?php endwhile; ?></tbody>
    </table></div>
</div>
<?php endif; ?>

<!-- Global Loading Overlay -->
<div id="global-loading-spinner" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[9999] flex-col items-center justify-center">
    <i class="fas fa-circle-notch fa-spin text-emerald-400 text-6xl mb-4 drop-shadow-[0_0_15px_rgba(52,211,153,0.5)]"></i>
    <p class="text-white font-bold animate-pulse text-lg tracking-widest uppercase">Memproses Pembayaran...</p>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Bebaskan modal dari batasan CSS parent (div.glass)
        document.querySelectorAll('[id^=modal_]').forEach(m => document.body.appendChild(m));
        
        // Pasang spinner loading pada form pembayaran
        document.querySelectorAll('.form-bayar').forEach(f => {
            f.addEventListener('submit', function(e) {
                const cara = this.querySelector('select[name="cara"]').value;
                const jumlah = parseInt(this.querySelector('input[name="jumlah"]').value) || 0;
                const saldo = <?= (int)($siswa_data['saldo'] ?? 0) ?>;

                if (cara === 'Tabungan' && jumlah > saldo) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Saldo Tidak Cukup!',
                        text: 'Saldo tabungan siswa tidak mencukupi untuk pembayaran ini.',
                        icon: 'error',
                        confirmButtonColor: '#ef4444',
                        background: '#0f172a',
                        color: '#fff'
                    });
                    return;
                }

                // Sembunyikan modal
                document.querySelectorAll('[id^=modal_]').forEach(m => m.classList.add('hidden'));
                // Tampilkan loading screen
                const spinner = document.getElementById('global-loading-spinner');
                if(spinner) {
                    spinner.classList.remove('hidden');
                    spinner.classList.add('flex');
                }
            });
        });
    });

    function checkTabungan(el) {
        const modal = el.closest('.glass');
        const info = modal.querySelector('.info-tabungan');
        if (el.value === 'Tabungan') {
            info.classList.remove('hidden');
        } else {
            info.classList.add('hidden');
        }
    }
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
