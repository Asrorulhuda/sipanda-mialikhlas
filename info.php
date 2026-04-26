<?php
$page_title = 'Detail Berita & Informasi';
require_once __DIR__ . '/config/init.php';

$slug = $_GET['s'] ?? '';
$id = $_GET['id'] ?? 0;

if ($slug) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_display_info WHERE slug = ? AND status = 'aktif' LIMIT 1");
    $stmt->execute([$slug]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM tbl_display_info WHERE id = ? AND status = 'aktif' LIMIT 1");
    $stmt->execute([$id]);
}

$info = $stmt->fetch();

if (!$info) {
    header("Location: homepage.php");
    exit;
}

// Redirect if external link exists
if (!empty($info['external_link'])) {
    header("Location: " . $info['external_link']);
    exit;
}

// Get school setting for branding
$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id = 1")->fetch();
$nama = $setting['nama_sekolah'] ?? 'MI Asrorul Huda';

// SEO Brain
$clean_content = strip_tags($info['konten']);
$meta_desc = mb_strimwidth($clean_content, 0, 160, "...");
$og_image = !empty($info['gambar']) ? BASE_URL . "cms_images/" . $info['gambar'] : BASE_URL . "assets/img/logo.png";
$canonical_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Master -->
    <title><?= clean($info['judul']) ?> | <?= clean($nama) ?></title>
    <meta name="description" content="<?= clean($meta_desc) ?>">
    <meta name="keywords" content="berita sekolah, informasi pendidikan, <?= clean($nama) ?>, <?= clean($info['judul']) ?>">
    <link rel="canonical" href="<?= $canonical_url ?>">

    <!-- Open Graph / Social Media Sharing -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= $canonical_url ?>">
    <meta property="og:title" content="<?= clean($info['judul']) ?> - <?= clean($nama) ?>">
    <meta property="og:description" content="<?= clean($meta_desc) ?>">
    <meta property="og:image" content="<?= $og_image ?>">

    <!-- Twitter Card -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= $canonical_url ?>">
    <meta property="twitter:title" content="<?= clean($info['judul']) ?>">
    <meta property="twitter:description" content="<?= clean($meta_desc) ?>">
    <meta property="twitter:image" content="<?= $og_image ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fafbfd; color: #1e293b; }
        h1, h2, h3, .font-serif { font-family: 'Playfair Display', serif; }
        .glass-nav { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(0,0,0,0.05); }
        .content-area img { max-width: 100%; height: auto; border-radius: 1rem; margin: 1.5rem 0; }
        .content-area iframe { width: 100%; border-radius: 1rem; margin: 1.5rem 0; aspect-ratio: 16/9; }
        .text-gradient { background: linear-gradient(135deg, #0a1628 0%, #1e3a8a 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body class="antialiased">

<!-- Simple Nav -->
<nav class="fixed top-0 left-0 right-0 z-50 glass-nav h-16 flex items-center">
    <div class="max-w-4xl mx-auto px-4 w-full flex justify-between items-center">
        <a href="homepage.php" class="flex items-center gap-2 text-navy-950 font-bold hover:text-blue-600 transition-colors">
            <i class="fas fa-arrow-left"></i>
            <span>Kembali</span>
        </a>
        <div class="text-xs font-bold uppercase tracking-widest text-slate-400"><?= clean($nama) ?></div>
    </div>
</nav>

<main class="pt-24 pb-20 px-4">
    <article class="max-w-4xl mx-auto bg-white rounded-3xl shadow-xl shadow-slate-200/50 overflow-hidden border border-slate-100">
        <?php if ($info['gambar']): ?>
        <div class="w-full h-[400px] relative">
            <img src="<?= BASE_URL ?>cms_images/<?= $info['gambar'] ?>" alt="<?= clean($info['judul']) ?>" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent"></div>
        </div>
        <?php endif; ?>

        <div class="p-8 md:p-12 -mt-20 relative z-10">
            <div class="flex items-center gap-4 mb-6">
                <span class="bg-blue-600 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Info Resmi</span>
                <span class="text-xs text-slate-400 font-medium"><i class="far fa-calendar-alt mr-1"></i> <?= date('d M Y', strtotime($info['created_at'])) ?></span>
            </div>

            <h1 class="text-4xl md:text-5xl font-black text-navy-950 mb-8 leading-tight">
                <?= clean($info['judul']) ?>
            </h1>

            <div class="content-area text-slate-600 leading-relaxed text-lg space-y-6">
                <?= $info['konten'] // Render high-quality HTML from TinyMCE ?>
            </div>

            <div class="mt-12 pt-8 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-medium">Dipublikasikan Oleh</p>
                        <p class="text-sm font-bold text-navy-950">Administrator</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <a href="https://wa.me/?text=<?= urlencode(clean($info['judul']) . ' - ' . (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]") ?>" target="_blank" class="w-10 h-10 rounded-full bg-green-50 text-green-600 flex items-center justify-center hover:bg-green-600 hover:text-white transition-all">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <button onclick="window.print()" class="w-10 h-10 rounded-full bg-slate-50 text-slate-600 flex items-center justify-center hover:bg-navy-950 hover:text-white transition-all">
                        <i class="fas fa-print"></i>
                    </button>
                </div>
            </div>
        </div>
    </article>
</main>

<footer class="py-10 text-center text-slate-400 text-sm">
    &copy; <?= date('Y') ?> <?= clean($nama) ?>. All rights reserved.
</footer>

</body>
</html>
