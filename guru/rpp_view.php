<?php
$page_title = 'Lihat RPP';
require_once __DIR__ . '/../config/init.php';
cek_role(['guru']);
cek_fitur('ai_rpp');

$id = (int) ($_GET['id'] ?? 0);
$id_guru = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT r.*, m.nama_mapel, k.nama_kelas FROM tbl_rpp r 
                        LEFT JOIN tbl_mapel m ON r.id_mapel=m.id_mapel 
                        LEFT JOIN tbl_kelas k ON r.id_kelas=k.id_kelas 
                        WHERE r.id=? AND r.id_guru=?");
$stmt->execute([$id, $id_guru]);
$r = $stmt->fetch();

if (!$r) {
    die("RPP tidak ditemukan.");
}

$filepath = __DIR__ . '/../assets/rpp_history/' . $r['file_path'];
if (!file_exists($filepath)) {
    die("File RPP tidak ditemukan di folder penyimpan.");
}

$content = file_get_contents($filepath);

// Extract styles AND body content
$styles = '';
if (preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $content, $style_matches)) {
    $styles = implode("\n", $style_matches[0]);
}

if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $content, $matches)) {
    $body = $matches[1];
} else {
    $body = $content;
}

require_once __DIR__ . '/../template/header.php';
require_once __DIR__ . '/../template/sidebar.php';
require_once __DIR__ . '/../template/topbar.php';
?>

<div class="mb-6 flex items-center justify-between gap-4">
    <div class="flex items-center gap-4">
        <a href="rpp.php" class="w-10 h-10 rounded-xl glass flex items-center justify-center text-slate-400 hover:text-white transition-all"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h2 class="text-xl font-bold italic uppercase tracking-widest font-black text-blue-400">Pratinjau RPP 📋</h2>
            <p class="text-[10px] text-slate-500 uppercase tracking-[0.2em]"><?= clean($r['judul_rpp']) ?></p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="../assets/rpp_history/<?= $r['file_path'] ?>" download class="bg-blue-600 hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-lg shadow-blue-500/20">
            <i class="fas fa-file-download"></i> Simpan File
        </a>
        <button onclick="printRPP()" class="bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-lg shadow-emerald-500/20">
            <i class="fas fa-print"></i> Cetak / PDF
        </button>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200">
    <div class="p-4 bg-slate-50 border-bottom border-slate-200 flex items-center justify-between">
        <div class="flex gap-1.5">
            <div class="w-2.5 h-2.5 rounded-full bg-red-400"></div>
            <div class="w-2.5 h-2.5 rounded-full bg-amber-400"></div>
            <div class="w-2.5 h-2.5 rounded-full bg-emerald-400"></div>
        </div>
        <p class="text-[10px] uppercase font-bold text-slate-400 tracking-widest">A4 Document View</p>
    </div>
    <div class="overflow-x-auto bg-slate-100 p-8 flex justify-center">
        <div class="bg-white shadow-xl p-[20mm] w-[210mm] min-h-[297mm] text-slate-900 shadow-2xl border border-slate-200" id="printableRPP">
            <?= $styles ?>
            <?= $body ?>
        </div>
    </div>
</div>

<style>
    /* Reset style inside preview so it doesn't clash with app UI */
    #printableRPP {
        font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif !important;
        line-height: 1.6;
    }
    #printableRPP * {
        color: #1a1a1a !important;
    }
    
    @media print {
        body * { visibility: hidden; }
        #printableRPP, #printableRPP * { visibility: visible; }
        #printableRPP { 
            position: absolute; 
            left: 0; 
            top: 0; 
            width: 100%; 
            padding: 0; 
            margin: 0;
            border: none;
            box-shadow: none;
        }
        @page { size: A4; margin: 15mm; }
    }
</style>

<script>
function printRPP() {
    window.print();
}
</script>

<?php require_once __DIR__ . '/../template/footer.php'; ?>
