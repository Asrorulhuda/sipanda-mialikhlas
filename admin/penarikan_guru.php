<?php
$page_title = 'Penarikan Dana Guru';
require_once __DIR__ . '/../config/init.php';
cek_role(['admin', 'bendahara']);
cek_fitur('ekantin');

// Handle Approval
if (isset($_GET['approve'])) {
    $id = $_GET['approve'];
    $p = $pdo->prepare("SELECT p.*, g.id_guru, g.nama, g.no_hp FROM tbl_penarikan_guru p JOIN tbl_guru g ON p.id_guru=g.id_guru WHERE p.id = ?");
    $p->execute([$id]); $pd = $p->fetch();
    
    if ($pd && $pd['status'] == 'Pending') {
        if ($pd['metode'] == 'Saldo Jajan') {
            // Update Saldo Jajan in tbl_guru
            $pdo->prepare("UPDATE tbl_guru SET saldo_jajan = saldo_jajan + ? WHERE id_guru = ?")
                ->execute([$pd['nominal'], $pd['id_guru']]);
        }
        
        $pdo->prepare("UPDATE tbl_penarikan_guru SET status = 'Sukses' WHERE id = ?")->execute([$id]);
        
        // Notify Guru
        require_once __DIR__ . '/../api/wa_helper.php';
        if ($pd['no_hp']) {
            $msg = "✅ *PENARIKAN SALDO PENJUAL BERHASIL*\n\nHallo *{$pd['nama']}*,\nPermintaan penarikan senilai *".rupiah($pd['nominal'])."* via *{$pd['metode']}* telah disetujui.\n\nSilakan cek saldo Anda sekarang. Terimakasih! 🙏";
            send_wa($pd['no_hp'], $msg);
        }
        
        flash('msg', 'Penarikan berhasil disetujui!');
    }
    header('Location: penarikan_guru.php'); exit;
}

// Handle Reject
if (isset($_GET['reject'])) {
    $id = $_GET['reject'];
    $p = $pdo->prepare("SELECT * FROM tbl_penarikan_guru WHERE id = ?");
    $p->execute([$id]); $pd = $p->fetch();
    
    if ($pd && $pd['status'] == 'Pending') {
        // Revert balance
        $pdo->prepare("UPDATE tbl_guru SET saldo_penjual = saldo_penjual + ? WHERE id_guru = ?")
            ->execute([$pd['nominal'], $pd['id_guru']]);
            
        $pdo->prepare("UPDATE tbl_penarikan_guru SET status = 'Ditolak' WHERE id = ?")->execute([$id]);
        flash('msg', 'Penarikan telah ditolak dan saldo dikembalikan ke guru.', 'warning');
    }
    header('Location: penarikan_guru.php'); exit;
}

$pending = $pdo->query("SELECT p.*, g.nama FROM tbl_penarikan_guru p JOIN tbl_guru g ON p.id_guru=g.id_guru WHERE p.status = 'Pending' ORDER BY p.id ASC")->fetchAll();
$history = $pdo->query("SELECT p.*, g.nama FROM tbl_penarikan_guru p JOIN tbl_guru g ON p.id_guru=g.id_guru WHERE p.status != 'Pending' ORDER BY p.id DESC LIMIT 50")->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="mb-6">
    <h4 class="font-bold text-lg">Penyelesaian Penarikan Guru</h4>
    <p class="text-xs text-slate-500">Setujui permintaan pencairan hasil jualan titipan guru</p>
</div>

<?= alert_flash('msg') ?>

<div class="grid grid-cols-1 gap-8">
    <!-- Pending Requests -->
    <div class="glass rounded-2xl overflow-hidden border border-amber-500/20">
        <div class="px-6 py-4 bg-amber-500/10 border-b border-amber-500/20 flex items-center justify-between">
            <h5 class="text-sm font-bold text-amber-500"><i class="fas fa-clock mr-2"></i>MENUNGGU PERSETUJUAN</h5>
            <span class="bg-amber-500 text-black text-[10px] font-black px-2 py-0.5 rounded-full"><?= count($pending) ?> REQUEST</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="text-[10px] font-black text-slate-500 uppercase tracking-widest bg-white/5">
                    <tr>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Nama Guru</th>
                        <th class="px-6 py-3">Nominal</th>
                        <th class="px-6 py-3">Metode</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php if (empty($pending)): ?>
                        <tr><td colspan="5" class="px-6 py-12 text-center text-slate-600 italic">Tidak ada permintaan pending</td></tr>
                    <?php endif; ?>
                    <?php foreach ($pending as $p): ?>
                    <tr class="hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4 text-xs"><?= date('d/m/Y H:i', strtotime($p['tanggal'])) ?></td>
                        <td class="px-6 py-4 font-bold text-white"><?= clean($p['nama']) ?></td>
                        <td class="px-6 py-4 font-black text-emerald-400"><?= rupiah($p['nominal']) ?></td>
                        <td class="px-6 py-4">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded <?= $p['metode'] == 'Saldo Jajan' ? 'bg-blue-500/20 text-blue-400' : 'bg-orange-500/20 text-orange-400' ?>">
                                <?= $p['metode'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center gap-2">
                                <a href="?approve=<?= $p['id'] ?>" onclick="return confirm('Setujui penarikan ini?')" class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all"><i class="fas fa-check mr-1"></i> SETUJUI</a>
                                <a href="?reject=<?= $p['id'] ?>" onclick="return confirm('Tolak penarikan ini?')" class="bg-red-600/20 hover:bg-red-600 text-red-500 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all border border-red-500/30">TOLAK</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- History -->
    <div class="glass rounded-2xl overflow-hidden border border-white/5">
        <div class="px-6 py-4 bg-white/5 border-b border-white/5">
            <h5 class="text-sm font-bold text-slate-300"><i class="fas fa-history mr-2"></i>RIWAYAT PENYELESAIAN</h5>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="text-[10px] font-black text-slate-500 uppercase tracking-widest bg-white/5">
                    <tr>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Nama Guru</th>
                        <th class="px-6 py-3">Nominal</th>
                        <th class="px-6 py-3">Metode</th>
                        <th class="px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($history as $p): ?>
                    <tr class="opacity-70 hover:opacity-100 transition-opacity">
                        <td class="px-6 py-4 text-xs"><?= date('d/m/Y H:i', strtotime($p['tanggal'])) ?></td>
                        <td class="px-6 py-4 font-medium"><?= clean($p['nama']) ?></td>
                        <td class="px-6 py-4 font-bold"><?= rupiah($p['nominal']) ?></td>
                        <td class="px-6 py-4 text-xs"><?= $p['metode'] ?></td>
                        <td class="px-6 py-4">
                            <span class="text-[10px] font-black px-2 py-0.5 rounded italic <?= $p['status'] == 'Sukses' ? 'text-emerald-500 bg-emerald-500/10' : 'text-red-500 bg-red-500/10' ?>">
                                <?= strtoupper($p['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
