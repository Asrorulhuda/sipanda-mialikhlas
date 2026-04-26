<?php
$page_title = 'Top-up Manual E-Kantin';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin','bendahara','kasir']);
cek_fitur('ekantin');

// Handle Manual Top-up
if (isset($_POST['submit_topup'])) {
    $id_user = (int)$_POST['id_user'];
    $role = $_POST['role'];
    $nominal = (float)$_POST['nominal'];
    $keterangan = $_POST['keterangan'] ?: 'Top-up manual via Admin';

    if ($nominal > 0) {
        if ($role == 'siswa') {
            $pdo->prepare("UPDATE tbl_siswa SET saldo_jajan = saldo_jajan + ? WHERE id_siswa = ?")->execute([$nominal, $id_user]);
            $user_info = $pdo->prepare("SELECT nama, no_hp_ortu FROM tbl_siswa WHERE id_siswa = ?");
            $user_info->execute([$id_user]); $u = $user_info->fetch();
            $no_hp = $u['no_hp_ortu'];
        } else {
            $pdo->prepare("UPDATE tbl_guru SET saldo_jajan = saldo_jajan + ? WHERE id_guru = ?")->execute([$nominal, $id_user]);
            $user_info = $pdo->prepare("SELECT nama, no_hp FROM tbl_guru WHERE id_guru = ?");
            $user_info->execute([$id_user]); $u = $user_info->fetch();
            $no_hp = $u['no_hp'];
        }

        // Record in tbl_topup as success
        $order_id = 'MANUAL-'.time().'-'.$id_user;
        $pdo->prepare("INSERT INTO tbl_topup (order_id, id_user, role, nominal, method, status) VALUES (?, ?, ?, ?, 'Manual', 'success')")
            ->execute([$order_id, $id_user, $role, $nominal]);

        // WA Notif if helper exists
        require_once __DIR__ . '/../api/wa_helper.php';
        if (!empty($no_hp)) {
            send_wa($no_hp, "🏫 *TOP-UP E-KANTIN SUCCESS*\n\nBerhasil mengisi saldo jajan an. *{$u['nama']}* sebesar *Rp ".number_format($nominal, 0, ',', '.')."* via Admin/Bendahara.");
        }

        flash('interactive', [
            'title' => 'TOP-UP BERHASIL!',
            'nominal' => rupiah($nominal),
            'user' => clean($u['nama']),
            'text' => 'Saldo telah ditambahkan ke dompet E-Kantin ' . clean($u['nama']) . ' dan notifikasi WhatsApp telah dikirim.'
        ]);
        header('Location: topup_manual.php'); exit;
    }
}

$siswa = $pdo->query("SELECT id_siswa as id, nama, 'siswa' as role FROM tbl_siswa WHERE status='Aktif' ORDER BY nama")->fetchAll();
$guru = $pdo->query("SELECT id_guru as id, nama, 'guru' as role FROM tbl_guru WHERE status='Aktif' ORDER BY nama")->fetchAll();
$users = array_merge($siswa, $guru);

require_once __DIR__ . '/../template/header.php'; require_once __DIR__ . '/../template/sidebar.php'; require_once __DIR__ . '/../template/topbar.php';
?>
<?= alert_flash('msg') ?>

<div class="max-w-2xl mx-auto">
    <div class="glass rounded-3xl p-10 bg-gradient-to-br from-white/5 to-white/[0.02] border border-white/10">
        <div class="text-center mb-10">
            <div class="w-20 h-20 bg-emerald-500/10 rounded-3xl flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-coins text-emerald-400 text-3xl"></i>
            </div>
            <h4 class="font-black text-2xl mb-2">Top-up Manual</h4>
            <p class="text-slate-500 text-sm">Isi saldo jajan secara langsung (penerimaan tunai).</p>
        </div>

        <form method="POST" class="space-y-6">
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Pilih Siswa / Guru</label>
                <div class="relative group">
                    <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-emerald-400 transition-colors"></i>
                    <select name="user" id="user_select" required class="w-full bg-slate-800/50 border border-white/10 rounded-2xl pl-12 pr-4 py-4 text-sm focus:border-emerald-500 outline-none appearance-none transition-all cursor-pointer">
                        <option value="" disabled selected>Cari nama...</option>
                        <?php foreach($users as $u): ?>
                        <option value="<?= $u['id'] ?>|<?= $u['role'] ?>"><?= clean($u['nama']) ?> (<?= ucfirst($u['role']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="id_user" id="id_user">
                    <input type="hidden" name="role" id="role">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Nominal Top-up</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-500">Rp</span>
                        <input type="number" name="nominal" required placeholder="0" class="w-full bg-slate-800/50 border border-white/10 rounded-2xl pl-12 pr-4 py-4 text-xl font-black text-emerald-400 focus:border-emerald-500 outline-none transition-all">
                    </div>
                </div>
                <div class="space-y-2 text-center flex items-center justify-center pt-6">
                    <div class="p-3 bg-white/5 rounded-xl border border-white/5 flex gap-2">
                        <button type="button" onclick="setNominal(20000)" class="px-3 py-1 bg-white/5 hover:bg-emerald-500/20 rounded-lg text-[10px] font-bold transition-all">20K</button>
                        <button type="button" onclick="setNominal(50000)" class="px-3 py-1 bg-white/5 hover:bg-emerald-500/20 rounded-lg text-[10px] font-bold transition-all">50K</button>
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-400 uppercase tracking-widest">Keterangan (Opsional)</label>
                <textarea name="keterangan" rows="2" placeholder="Catatan transaksi..." class="w-full bg-slate-800/50 border border-white/10 rounded-2xl p-4 text-sm focus:border-emerald-500 outline-none transition-all"></textarea>
            </div>

            <button type="submit" name="submit_topup" class="w-full py-5 bg-emerald-600 hover:bg-emerald-500 rounded-3xl font-black text-lg shadow-xl shadow-emerald-900/40 transition-all active:scale-95 mt-4">
                <i class="fas fa-check-circle mr-2"></i>KONFIRMASI TOP-UP
            </button>
        </form>
    </div>
</div>

<script>
document.getElementById('user_select').addEventListener('change', function() {
    const val = this.value.split('|');
    document.getElementById('id_user').value = val[0];
    document.getElementById('role').value = val[1];
});

function setNominal(val) {
    document.querySelector('input[name="nominal"]').value = val;
}
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
