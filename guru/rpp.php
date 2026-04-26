<?php
$page_title = 'Daftar RPP';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('ai_rpp');

$id_guru = $_SESSION['user_id'];

// Handle delete
if (isset($_GET['delete'])) {
    $id_del = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT file_path FROM tbl_rpp WHERE id=? AND id_guru=?");
    $stmt->execute([$id_del, $id_guru]);
    $file = $stmt->fetchColumn();
    
    if ($file && file_exists(__DIR__ . '/../assets/rpp_history/' . $file)) {
        unlink(__DIR__ . '/../assets/rpp_history/' . $file);
    }
    
    $pdo->prepare("DELETE FROM tbl_rpp WHERE id=? AND id_guru=?")->execute([$id_del, $id_guru]);
    flash('msg', 'RPP berhasil dihapus', 'success');
    header('Location: rpp.php');
    exit;
}

// Fetch RPPs
$stmt = $pdo->prepare("SELECT r.*, m.nama_mapel, k.nama_kelas FROM tbl_rpp r 
                        LEFT JOIN tbl_mapel m ON r.id_mapel=m.id_mapel 
                        LEFT JOIN tbl_kelas k ON r.id_kelas=k.id_kelas 
                        WHERE r.id_guru=? ORDER BY r.created_at DESC");
$stmt->execute([$id_guru]);
$data = $stmt->fetchAll();

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-bold italic uppercase tracking-widest font-black">Generator RPP 📝</h2>
        <p class="text-xs text-slate-400">Buat dan kelola Rencana Pelaksanaan Pembelajaran Anda.</p>
    </div>
    <a href="rpp_tambah.php" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-blue-900/20 transition-all flex items-center justify-center gap-2 uppercase tracking-widest italic font-black">
        <i class="fas fa-plus"></i> Buat RPP Baru
    </a>
</div>

<?= alert_flash('msg') ?>

<div class="glass rounded-xl overflow-hidden border border-white/5">
    <div class="table-container">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-white/5 text-slate-400 font-bold uppercase tracking-widest text-[10px]">
                    <th class="px-6 py-4">Tanggal</th>
                    <th class="px-6 py-4">Judul RPP / Materi</th>
                    <th class="px-6 py-4">Mapel & Kelas</th>
                    <th class="px-6 py-4">Tipe</th>
                    <th class="px-6 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php if ($data): foreach ($data as $r): ?>
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-4 text-xs"><?= tgl_indo($r['created_at']) ?></td>
                    <td class="px-6 py-4">
                        <p class="font-bold text-slate-200"><?= clean($r['judul_rpp']) ?></p>
                    </td>
                    <td class="px-6 py-4">
                        <p class="text-xs text-slate-400"><?= clean($r['nama_mapel']) ?> · <?= clean($r['nama_kelas']) ?></p>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-[9px] px-2 py-0.5 rounded-full font-bold uppercase <?= $r['kurikulum_type'] == 'KBC' ? 'bg-purple-500/20 text-purple-400' : 'bg-blue-500/20 text-blue-400' ?>">
                            <?= $r['kurikulum_type'] ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-center gap-2">
                            <a href="../assets/rpp_history/<?= $r['file_path'] ?>" download class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center hover:bg-blue-500 hover:text-white transition-all" title="Unduh File">
                                <i class="fas fa-file-word text-xs"></i>
                            </a>
                            <a href="rpp_view.php?id=<?= $r['id'] ?>" class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center hover:bg-emerald-500 hover:text-white transition-all" title="Lihat/Print">
                                <i class="fas fa-eye text-xs"></i>
                            </a>
                            <a href="rpp.php?delete=<?= $r['id'] ?>" onclick="return confirm('Hapus RPP ini?')" class="w-8 h-8 rounded-lg bg-rose-500/10 text-rose-400 flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all" title="Hapus">
                                <i class="fas fa-trash text-xs"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-slate-500 italic">
                        <i class="fas fa-file-invoice text-3xl mb-3 opacity-20 block"></i>
                        Belum ada riwayat RPP yang dibuat.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
