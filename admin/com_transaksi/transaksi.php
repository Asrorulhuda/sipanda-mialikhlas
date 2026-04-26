<?php
$page_title = 'Transaksi Tabungan';
require_once __DIR__ . '/../../config/init.php';
cek_role(['admin','bendahara']);
cek_fitur('tabungan');
?>
<style>
/* Global Loading Overlay */
#global-loader { position: fixed; inset: 0; background: rgba(15,23,42,0.8); backdrop-filter: blur(8px); z-index: 9999; display: none; flex-direction: column; align-items: center; justify-content: center; }
.spinner { width: 48px; height: 48px; border: 4px solid rgba(255,255,255,0.1); border-left-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 1rem; }
@keyframes spin { 100% { transform: rotate(360deg); } }
</style>
<div id="global-loader">
    <div class="spinner mb-4"></div>
    <div class="text-white font-semibold text-lg tracking-wide">Memproses Transaksi...</div>
    <div class="text-slate-300 text-sm mt-2">Mohon tunggu sebentar</div>
</div>

<?php
$id = (int)($_GET['id'] ?? 0);
if (isset($_POST['setor'])) {
    $jumlah = (int)$_POST['jumlah'];
    $pdo->prepare("INSERT INTO tbl_transaksi_tabungan (id_nasabah,jenis,jumlah,keterangan) VALUES (?,'Debit',?,?)")->execute([$_POST['id_nasabah'],$jumlah,$_POST['ket']]);
    $pdo->prepare("UPDATE tbl_nasabah SET saldo=saldo+? WHERE id_nasabah=?")->execute([$jumlah,$_POST['id_nasabah']]);
    $pdo->prepare("UPDATE tbl_siswa SET saldo=saldo+? WHERE id_siswa=(SELECT id_siswa FROM tbl_nasabah WHERE id_nasabah=?)")->execute([$jumlah,$_POST['id_nasabah']]);
    
    if (file_exists(__DIR__ . '/../../api/wa_helper.php')) {
        require_once __DIR__ . '/../../api/wa_helper.php';
        $n = $pdo->prepare("SELECT id_siswa, saldo FROM tbl_nasabah WHERE id_nasabah=?");
        $n->execute([$_POST['id_nasabah']]);
        $nas = $n->fetch();
        if ($nas) wa_notif_tabungan($nas['id_siswa'], 'Setor', $jumlah, $_POST['ket'], $nas['saldo']);
    }

    flash('msg','Setoran berhasil!'); header('Location: transaksi.php?id='.$_POST['id_nasabah']); exit;
}
if (isset($_POST['tarik'])) {
    $jumlah_request = (int)$_POST['jumlah'];
    $persen_admin = (float)($_POST['biaya_admin_persen'] ?? 0);
    $keterangan = $_POST['ket'] ?? '';
    
    // Verifikasi batas saldo
    $cek = $pdo->prepare("SELECT saldo FROM tbl_nasabah WHERE id_nasabah=?");
    $cek->execute([$_POST['id_nasabah']]);
    $saldo_current = (int)$cek->fetchColumn();
    
    if ($jumlah_request > $saldo_current) {
        flash('msg', 'Sisa saldo tidak cukup!', 'bg-red-500');
        header('Location: transaksi.php?id='.$_POST['id_nasabah']); exit;
    }

    $admin = 0;
    // Jika ditarik semua DAN potongan diisi
    if ($jumlah_request >= $saldo_current && $persen_admin > 0) {
        $admin = (int)(($saldo_current * $persen_admin) / 100);
        $uang_tunai_diserahkan = $saldo_current - $admin;
        $total_potong_db = $saldo_current;
    } else {
        $uang_tunai_diserahkan = $jumlah_request;
        $total_potong_db = $jumlah_request;
    }

    // Insert 1: Penarikan Tunai
    $pdo->prepare("INSERT INTO tbl_transaksi_tabungan (id_nasabah,jenis,jumlah,keterangan) VALUES (?,'Kredit',?,?)")->execute([$_POST['id_nasabah'], $uang_tunai_diserahkan, $keterangan]);
    
    // Insert 2: Biaya Admin (Jika ada)
    if ($admin > 0) {
        $pdo->prepare("INSERT INTO tbl_transaksi_tabungan (id_nasabah,jenis,jumlah,keterangan) VALUES (?,'Kredit',?,?)")->execute([$_POST['id_nasabah'], $admin, 'Biaya Admin Penarikan / Tutup Buku']);
    }
    
    $pdo->prepare("UPDATE tbl_nasabah SET saldo=saldo-? WHERE id_nasabah=?")->execute([$total_potong_db,$_POST['id_nasabah']]);
    $pdo->prepare("UPDATE tbl_siswa SET saldo=saldo-? WHERE id_siswa=(SELECT id_siswa FROM tbl_nasabah WHERE id_nasabah=?)")->execute([$total_potong_db,$_POST['id_nasabah']]);
    
    if (file_exists(__DIR__ . '/../../api/wa_helper.php')) {
        require_once __DIR__ . '/../../api/wa_helper.php';
        $n = $pdo->prepare("SELECT id_siswa, saldo FROM tbl_nasabah WHERE id_nasabah=?");
        $n->execute([$_POST['id_nasabah']]);
        $nas = $n->fetch();
        if ($nas) wa_notif_tabungan($nas['id_siswa'], 'Tarik', $uang_tunai_diserahkan, $keterangan, $nas['saldo'], $admin);
    }

    flash('msg','Penarikan berhasil dicatat!'); header('Location: transaksi.php?id='.$_POST['id_nasabah']); exit;
}

$nasabah_list = $pdo->query("SELECT n.*,s.nama FROM tbl_nasabah n JOIN tbl_siswa s ON n.id_siswa=s.id_siswa ORDER BY s.nama")->fetchAll();
if ($id) { $stmt = $pdo->prepare("SELECT n.*,s.nama FROM tbl_nasabah n JOIN tbl_siswa s ON n.id_siswa=s.id_siswa WHERE n.id_nasabah=?"); $stmt->execute([$id]); $nasabah = $stmt->fetch(); } else { $nasabah = null; }
if ($id) { $stmt = $pdo->prepare("SELECT * FROM tbl_transaksi_tabungan WHERE id_nasabah=? ORDER BY id DESC"); $stmt->execute([$id]); $transaksi = $stmt->fetchAll(); } else { $transaksi = []; }

require_once __DIR__ . '/../../template/header.php'; require_once __DIR__ . '/../../template/sidebar.php'; require_once __DIR__ . '/../../template/topbar.php';
?>
<?= alert_flash('msg') ?>
<div class="glass rounded-xl p-5 mb-6"><form method="GET" class="flex gap-3 items-end">
    <div class="flex-1"><label class="block text-xs text-slate-400 mb-1">Pilih Nasabah</label><select name="id" onchange="this.form.submit()" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"><option value="">-- Pilih --</option><?php foreach ($nasabah_list as $n): ?><option value="<?= $n['id_nasabah'] ?>" <?= $id==$n['id_nasabah']?'selected':'' ?>><?= clean($n['nama']) ?> (<?= $n['no_rekening'] ?>)</option><?php endforeach; ?></select></div>
</form></div>

<?php if ($nasabah): ?>
<div class="glass rounded-xl p-5 mb-6 flex items-center justify-between">
    <div><h3 class="font-bold"><?= clean($nasabah['nama']) ?></h3><p class="text-xs text-slate-400">No. Rek: <?= $nasabah['no_rekening'] ?></p></div>
    <div class="text-right"><p class="text-xs text-slate-400">Saldo</p><p class="text-2xl font-bold text-emerald-400"><?= rupiah($nasabah['saldo']) ?></p></div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="glass rounded-xl p-5">
        <h4 class="text-sm font-semibold mb-3 text-emerald-400"><i class="fas fa-arrow-down mr-1"></i>Setor</h4>
        <form method="POST" class="form-bayar"><input type="hidden" name="id_nasabah" value="<?= $nasabah['id_nasabah'] ?>">
            <div class="space-y-3"><div><input type="number" name="jumlah" required placeholder="Jumlah (Mis: 10000)" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div><div><input type="text" name="ket" placeholder="Keterangan (Opsional)" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div></div>
            <button type="submit" name="setor" class="bg-emerald-600 hover:bg-emerald-500 px-4 py-2 rounded-lg text-sm font-medium mt-3 w-full transition-colors"><i class="fas fa-plus mr-1"></i>Proses Setor</button>
        </form>
    </div>
    <div class="glass rounded-xl p-5">
        <h4 class="text-sm font-semibold mb-3 text-red-400"><i class="fas fa-arrow-up mr-1"></i>Tarik</h4>
        <form method="POST" class="form-bayar"><input type="hidden" name="id_nasabah" value="<?= $nasabah['id_nasabah'] ?>">
            <div class="space-y-3">
                <div class="flex gap-2">
                    <input type="number" name="jumlah" id="input_jumlah_tarik" required placeholder="Jml Tarik (Mis: 50000)" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm transition-all duration-300">
                    <div id="admin_wrapper" style="display:none;" class="w-1/3 shrink-0"><input type="number" step="0.1" name="biaya_admin_persen" id="input_admin_persen" placeholder="Admin (%)" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
                </div>
                <div><input type="text" name="ket" placeholder="Keterangan (Opsional)" class="w-full bg-slate-800/50 border border-white/10 rounded-lg px-3 py-2 text-sm"></div>
            </div>
            <button type="submit" name="tarik" value="1" class="bg-red-600 hover:bg-red-500 px-4 py-2 rounded-lg text-sm font-medium mt-3 w-full transition-colors"><i class="fas fa-minus mr-1"></i>Proses Tarik</button>
        </form>
    </div>
</div>

<div class="glass rounded-xl p-5"><h4 class="text-sm font-semibold mb-3"><i class="fas fa-history mr-2 text-blue-400"></i>Riwayat Transaksi</h4>
    <div class="table-container"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-400 border-b border-white/10"><th class="pb-3">#</th><th class="pb-3">Tanggal</th><th class="pb-3">Jenis</th><th class="pb-3">Jumlah</th><th class="pb-3">Keterangan</th></tr></thead>
        <tbody><?php foreach ($transaksi as $i => $t): ?>
        <tr class="border-b border-white/5"><td class="py-2"><?= $i+1 ?></td><td><?= tgl_indo($t['tanggal']) ?></td>
        <td><span class="px-2 py-0.5 rounded-full text-xs <?= $t['jenis']=='Debit'?'bg-emerald-500/20 text-emerald-400':'bg-red-500/20 text-red-400' ?>"><?= $t['jenis']=='Debit'?'Setor':'Tarik' ?></span></td>
        <td class="font-medium <?= $t['jenis']=='Debit'?'text-emerald-400':'text-red-400' ?>"><?= rupiah($t['jumlah']) ?></td><td class="text-slate-400"><?= clean($t['keterangan']) ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../template/footer.php'; ?>
<!-- Modal Konfirmasi Penarikan -->
<div id="modal-konfirmasi" class="fixed inset-0 bg-slate-900/80 backdrop-blur-[2px] z-[9998] flex items-center justify-center p-4" style="display:none;">
    <div class="bg-white text-slate-800 rounded-xl max-w-sm w-full p-6 shadow-2xl relative">
        <h3 class="text-xl font-bold mb-3 border-b pb-2">Konfirmasi Penarikan</h3>
        <p class="text-xs text-slate-600 mb-4" id="modal-text-desc">Anda akan menarik seluruh saldo tabungan siswa. Uang yang diterima oleh siswa akan dikurangi biaya administrasi.</p>
        
        <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 mb-5">
            <div class="flex justify-between text-sm mb-1"><span>Total Saldo:</span><span id="mk-saldo" class="font-bold"></span></div>
            <div class="flex justify-between text-sm mb-1 text-red-600"><span>Biaya Admin (<span id="mk-persen"></span>%):</span><span id="mk-admin" class="font-bold"></span></div>
            <div class="border-t border-dashed border-slate-300 my-2"></div>
            <div class="flex justify-between text-base"><span>Tunai Diberikan:</span><span id="mk-tunai" class="font-black text-emerald-600"></span></div>
        </div>
        
        <div class="flex justify-end gap-2">
            <button type="button" onclick="document.getElementById('modal-konfirmasi').style.display='none'" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium hover:bg-slate-100 transition-colors">Batal</button>
            <button type="button" id="btn-lanjut-tarik" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold shadow-md transition-colors">Ya, Tetap Tarik</button>
        </div>
    </div>
</div>

<script>
    const limitSaldo = <?= $nasabah['saldo'] ?? 0 ?>;
    const inputJumlah = document.getElementById('input_jumlah_tarik');
    const adminWrapper = document.getElementById('admin_wrapper');
    const inputAdmin = document.getElementById('input_admin_persen');
    
    // Auto-show Admin Input on Withdraw All
    if (inputJumlah) {
        inputJumlah.addEventListener('input', function() {
            let val = parseInt(this.value) || 0;
            if (val >= limitSaldo && limitSaldo > 0) {
                this.classList.remove('w-full');
                this.classList.add('w-2/3');
                adminWrapper.style.display = 'block';
            } else {
                adminWrapper.style.display = 'none';
                this.classList.remove('w-2/3');
                this.classList.add('w-full');
                inputAdmin.value = '';
            }
        });
    }

    document.querySelectorAll('.form-bayar').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Logika Tarik
            if (this.querySelector('button[name="tarik"]')) {
                let j = parseInt(inputJumlah.value) || 0;
                let p = parseFloat(inputAdmin.value) || 0;
                
                if (j > limitSaldo) {
                    alert('Maaf, nominal melebihi sisa saldo!');
                    return;
                }
                
                if (j >= limitSaldo && p > 0) {
                    let a = Math.floor((limitSaldo * p) / 100);
                    let tunai = limitSaldo - a;
                    
                    document.getElementById('mk-saldo').innerText = 'Rp ' + limitSaldo.toLocaleString('id-ID');
                    document.getElementById('mk-persen').innerText = p;
                    document.getElementById('mk-admin').innerText = '-Rp ' + a.toLocaleString('id-ID');
                    document.getElementById('mk-tunai').innerText = 'Rp ' + tunai.toLocaleString('id-ID');
                    document.getElementById('modal-konfirmasi').style.display = 'flex';
                    
                    document.getElementById('btn-lanjut-tarik').onclick = () => {
                        document.getElementById('modal-konfirmasi').style.display = 'none';
                        document.getElementById('global-loader').style.display = 'flex';
                        let inputH = document.createElement('input');
                        inputH.type = 'hidden'; inputH.name = 'tarik'; inputH.value = '1';
                        form.appendChild(inputH);
                        HTMLFormElement.prototype.submit.call(form);
                    };
                    return;
                }
                // Jika ga ditarik semua, langsung proses via mekanisme standar
                let inputH = document.createElement('input'); inputH.type = 'hidden'; inputH.name = 'tarik'; inputH.value = '1'; form.appendChild(inputH);
            } else if (this.querySelector('button[name="setor"]')) {
                let inputH = document.createElement('input'); inputH.type = 'hidden'; inputH.name = 'setor'; inputH.value = '1'; form.appendChild(inputH);
            }
            
            document.getElementById('global-loader').style.display = 'flex';
            this.querySelector('button[type="submit"]').disabled = true;
            HTMLFormElement.prototype.submit.call(form);
        });
    });
</script>
