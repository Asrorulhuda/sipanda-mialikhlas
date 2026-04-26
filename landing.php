<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/fungsi.php';

// ═══════════════════════════════════════════════════════
// LOAD DATA
// ═══════════════════════════════════════════════════════
$setting      = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
$nama         = $setting['nama_sekolah'] ?? 'SIPANDA';
$paket_aktif  = $setting['paket_langganan'] ?? 'Basic';
$db_features  = $pdo->query("SELECT * FROM tbl_features ORDER BY sort_order")->fetchAll();

// ═══════════════════════════════════════════════════════
// DAFTAR PAKET
// ═══════════════════════════════════════════════════════
// Fetch packages from database
try {
    $paket_list_db = $pdo->query("SELECT * FROM tbl_packages ORDER BY sort_order")->fetchAll();
    
    // Create a feature map for fast lookup
    $feature_map = [];
    foreach ($db_features as $f) {
        $feature_map[$f['module_id']] = $f['module_name'];
    }

    $paket_list = [];
    foreach ($paket_list_db as $pkg) {
        // Fetch selected modules
        $pkg_modules = json_decode($pkg['modules_json'] ?? '[]', true) ?: [];
        $combined_features = [];
        
        // Map module IDs to module names
        foreach ($pkg_modules as $mod_id) {
            if (isset($feature_map[$mod_id])) {
                $combined_features[] = "Modul " . $feature_map[$mod_id];
            }
        }
        
        // Add the custom text features
        $custom_features = json_decode($pkg['features_json'] ?? '[]', true) ?: [];
        $combined_features = array_merge($combined_features, $custom_features);

        $paket_list[$pkg['name']] = [
            'harga' => $pkg['price_text'],
            'periode' => $pkg['period'],
            'desc' => $pkg['description'],
            'fitur' => $combined_features,
            'color' => $pkg['color_hex'],
            'icon' => $pkg['icon_class'],
            'rekomendasi' => (bool)$pkg['is_recommended']
        ];
    }
} catch (Exception $e) {
    $paket_list = []; // Fallback if table doesn't exist yet
}

// ═══════════════════════════════════════════════════════
// PALET WARNA IKON FITUR
// ═══════════════════════════════════════════════════════
$icon_colors = [
    ['var(--blue)',    'rgba(59,130,246,0.1)'],
    ['var(--emerald)', 'rgba(16,185,129,0.1)'],
    ['var(--gold)',    'rgba(215,136,57,0.1)'],
    ['var(--purple)',  'rgba(139,92,246,0.1)'],
    ['#ef4444',       'rgba(239,68,68,0.1)'],
    ['#06b6d4',       'rgba(6,182,212,0.1)'],
];

$active_color = $paket_list[$paket_aktif]['color'] ?? 'var(--blue)';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harga & Paket SIPANDA — <?= clean($nama) ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ─── Variables ─── */
        :root {
            --bg-dark:    #06060b;
            --text-main:  #f8fafc;
            --text-muted: #94a3b8;
            --card-bg:    rgba(15, 15, 25, 0.6);
            --card-border:rgba(255, 255, 255, 0.08);
            --gold:       #d78839;
            --gold-light: #f0dbbe;
            --blue:       #3b82f6;
            --emerald:    #10b981;
            --purple:     #8b5cf6;
        }

        /* ─── Reset ─── */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* ─── Animated Background ─── */
        .bg-mesh {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            z-index: -1;
            background:
                radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.15), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(139, 92, 246, 0.15), transparent 25%),
                radial-gradient(circle at 50% 80%, rgba(16, 185, 129, 0.1), transparent 25%);
            filter: blur(60px);
            animation: meshAnim 15s ease-in-out infinite alternate;
        }

        @keyframes meshAnim {
            0%   { transform: scale(1); }
            100% { transform: scale(1.1) rotate(5deg); }
        }

        /* ─── Navbar ─── */
        nav {
            position: fixed;
            top: 0; width: 100%;
            z-index: 100;
            background: rgba(6, 6, 11, 0.7);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--card-border);
            padding: 16px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-logo {
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            font-size: 24px;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-logo span {
            background: linear-gradient(135deg, var(--gold), #fcd34d);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-back {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: 0.3s;
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid transparent;
        }

        .nav-back:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--card-border);
        }

        /* ─── Hero ─── */
        .hero {
            padding: 160px 5% 80px;
            text-align: center;
            max-width: 900px;
            margin: 0 auto;
        }

        .badge-pro {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(215, 136, 57, 0.1);
            border: 1px solid rgba(215, 136, 57, 0.3);
            color: var(--gold-light);
            font-size: 12px;
            font-weight: 700;
            padding: 6px 16px;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 24px;
        }

        .hero h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 56px;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 24px;
            letter-spacing: -1px;
        }

        .hero p {
            font-size: 18px;
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto 40px;
        }

        /* ─── Active Package Banner ─── */
        .active-pkg-banner {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.01));
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 700px;
            margin: 0 auto 80px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(12px);
            position: relative;
            overflow: hidden;
        }

        .active-pkg-banner::before {
            content: '';
            position: absolute;
            left: 0; top: 0;
            width: 6px; height: 100%;
            background: <?= $active_color ?>;
        }

        .pkg-info h3 {
            font-size: 14px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 4px;
        }

        .pkg-info h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: #fff;
        }

        .pkg-info h2 span { color: <?= $active_color ?>; }

        .pkg-status {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #34d399;
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ─── Pricing Section ─── */
        .pricing-section {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 5% 100px;
        }

        /* ─── Tabs ─── */
        .tabs-container {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-bottom: 40px;
        }

        .tab-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--card-border);
            color: var(--text-muted);
            padding: 12px 32px;
            border-radius: 30px;
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }

        .tab-btn:hover { background: rgba(255, 255, 255, 0.1); color: #fff; }

        .tab-btn.active {
            background: linear-gradient(135deg, var(--gold), #d97706);
            border-color: var(--gold);
            color: #fff;
            box-shadow: 0 10px 20px rgba(215, 136, 57, 0.3);
        }

        .tab-content { display: none; animation: fadeIn 0.5s ease-in-out; }
        .tab-content.active { display: block; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ─── Pricing Grid ─── */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            align-items: center;
        }

        .pricing-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 32px;
            padding: 40px;
            position: relative;
            backdrop-filter: blur(20px);
            transition: 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        .pricing-card:hover {
            transform: translateY(-10px);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
        }

        .pricing-card.recommended {
            background: linear-gradient(180deg, rgba(16, 185, 129, 0.05) 0%, var(--card-bg) 100%);
            border-color: rgba(16, 185, 129, 0.3);
            transform: scale(1.05);
            z-index: 2;
        }

        .pricing-card.recommended:hover { transform: scale(1.05) translateY(-10px); }

        .rec-badge {
            position: absolute;
            top: -14px; left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, var(--emerald), #059669);
            color: white;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 6px 16px;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        /* ─── Card Internals ─── */
        .card-header { margin-bottom: 30px; }

        .card-icon {
            width: 56px; height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .card-title {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .card-desc {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.5;
            min-height: 42px;
        }

        .card-price {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid var(--card-border);
        }

        .price-val {
            font-family: 'Outfit', sans-serif;
            font-size: 36px;
            font-weight: 900;
            letter-spacing: -1px;
        }

        .price-period {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .card-features { list-style: none; margin-bottom: 40px; }

        .card-features li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            font-size: 15px;
            color: #cbd5e1;
        }

        .card-features li i {
            color: var(--emerald);
            font-size: 18px;
            margin-top: 2px;
        }

        .btn-buy {
            display: block;
            width: 100%;
            padding: 16px;
            text-align: center;
            border-radius: 16px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.3s;
            border: 1px solid var(--card-border);
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }

        .btn-buy:hover { background: rgba(255, 255, 255, 0.1); }

        .pricing-card.recommended .btn-buy {
            background: var(--emerald);
            border-color: var(--emerald);
            color: #064e3b;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.2);
        }

        .pricing-card.recommended .btn-buy:hover {
            background: #34d399;
            box-shadow: 0 12px 32px rgba(16, 185, 129, 0.4);
            transform: translateY(-2px);
        }

        /* ─── Feature Items ─── */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 5% 100px;
        }

        .feat-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--card-border);
            padding: 24px;
            border-radius: 20px;
            display: flex;
            gap: 16px;
            transition: 0.3s;
            position: relative;
            overflow: hidden;
        }

        .feat-item.inactive {
            opacity: 0.6;
            filter: grayscale(80%);
        }

        .feat-item:hover {
            background: rgba(255, 255, 255, 0.04);
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.1);
            opacity: 1;
            filter: grayscale(0%);
        }

        .feat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            background: rgba(59, 130, 246, 0.1);
            color: var(--blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .feat-item.inactive .feat-icon {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-muted);
        }

        .feat-title {
            font-weight: 700;
            margin-bottom: 4px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .feat-desc {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .feat-status {
            position: absolute;
            top: 12px; right: 12px;
            font-size: 14px;
        }

        .feat-status.active   { color: var(--emerald); }
        .feat-status.inactive { color: rgba(255, 255, 255, 0.2); }

        /* ─── Section Heading ─── */
        .section-heading {
            text-align: center;
            margin-bottom: 40px;
            padding-top: 40px;
            border-top: 1px solid var(--card-border);
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 5% 40px;
        }

        .section-heading h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .section-heading p { color: var(--text-muted); }

        /* ─── Footer ─── */
        footer {
            text-align: center;
            padding: 40px;
            border-top: 1px solid var(--card-border);
            margin-top: 40px;
            color: var(--text-muted);
            font-size: 14px;
        }

        /* ─── Responsive ─── */
        @media (max-width: 768px) {
            .hero h1 { font-size: 40px; }
            .active-pkg-banner { flex-direction: column; text-align: center; gap: 20px; }
            .pricing-card.recommended { transform: none; }
            .pricing-card.recommended:hover { transform: translateY(-5px); }
        }
    </style>
</head>
<body>

<div class="bg-mesh"></div>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- NAVBAR -->
<!-- ═══════════════════════════════════════════════════════ -->
<nav>
    <a href="homepage.php" class="nav-logo">
        <i class="fas fa-layer-group" style="color:var(--gold);"></i>
        <span>SIPANDA</span>
    </a>
    <a href="homepage.php" class="nav-back">
        <i class="fas fa-arrow-left" style="margin-right:6px;"></i> Kembali
    </a>
</nav>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- HERO -->
<!-- ═══════════════════════════════════════════════════════ -->
<div class="hero">
    <div class="badge-pro">
        <i class="fas fa-star"></i> Pilihan Tepat Untuk Sekolah
    </div>

    <h1>
        Digitalisasi Sekolah<br>
        Semakin <span style="color:var(--blue);">Mudah & Cepat</span>
    </h1>

    <p>Pilih paket langganan yang sesuai dengan kebutuhan sekolah Anda. Tingkatkan efisiensi layanan administrasi tanpa repot memikirkan server.</p>

    <!-- Active Package Banner -->
    <div class="active-pkg-banner">
        <div class="pkg-info" style="text-align:left;">
            <h3>Status Langganan Anda</h3>
            <h2>Paket <span><?= htmlspecialchars($paket_aktif) ?></span></h2>
            <div style="font-size:13px; color:var(--text-muted); margin-top:8px;">
                Dikelola oleh <strong style="color:#fff;">ASR Production</strong>
            </div>
        </div>
        <div class="pkg-status">
            <i class="fas fa-check-circle"></i> Sistem Aktif
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- PRICING SECTION -->
<!-- ═══════════════════════════════════════════════════════ -->
<div class="pricing-section">

    <!-- Section Heading -->
    <div style="text-align:center; margin-bottom:40px;">
        <h2 style="font-family:'Outfit',sans-serif; font-size:32px; font-weight:800; margin-bottom:12px;">
            Daftar Harga & Layanan
        </h2>
        <p style="color:var(--text-muted);">Pilih layanan terbaik untuk menunjang pendidikan modern.</p>
    </div>

    <!-- ─── Tab Navigation ─── -->
    <div class="tabs-container">
        <button class="tab-btn active" onclick="switchTab('paket')">
            <i class="fas fa-boxes" style="margin-right:8px;"></i> Beli Paket (Bundling)
        </button>
        <button class="tab-btn" onclick="switchTab('satuan')">
            <i class="fas fa-list-check" style="margin-right:8px;"></i> Beli Satuan (Per Fitur)
        </button>
    </div>

    <!-- ─── Tab: Paket Bundling ─── -->
    <div id="tab-paket" class="tab-content active">
        <div class="pricing-grid">
            <?php foreach ($paket_list as $nama_paket => $p):
                $is_rec = !empty($p['rekomendasi']);
            ?>
            <div class="pricing-card <?= $is_rec ? 'recommended' : '' ?>">

                <?php if ($is_rec): ?>
                    <div class="rec-badge">Paling Diminati</div>
                <?php endif; ?>

                <div>
                    <!-- Card Header -->
                    <div class="card-header">
                        <div class="card-icon" style="background:<?= $p['color'] ?>20; color:<?= $p['color'] ?>;">
                            <i class="fas <?= $p['icon'] ?>"></i>
                        </div>
                        <h3 class="card-title"><?= $nama_paket ?></h3>
                        <p class="card-desc"><?= $p['desc'] ?></p>
                    </div>

                    <!-- Price -->
                    <div class="card-price">
                        <span class="price-val"><?= $p['harga'] ?></span>
                        <span class="price-period"><?= $p['periode'] ?></span>
                    </div>

                    <!-- Features List -->
                    <ul class="card-features">
                        <?php foreach ($p['fitur'] as $fitur): ?>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <?= $fitur ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- CTA Button -->
                <a href="https://wa.me/6281389297614?text=Halo%20Admin,%20saya%20tertarik%20dengan%20Sipanda%20Paket%20<?= urlencode($nama_paket) ?>"
                   target="_blank"
                   class="btn-buy">
                    Hubungi Admin <i class="fab fa-whatsapp" style="margin-left:8px;"></i>
                </a>

            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- End Tab Paket -->

    <!-- ─── Tab: Fitur Satuan ─── -->
    <div id="tab-satuan" class="tab-content">
        <div class="features-grid" style="padding-bottom:0;">
            <?php foreach ($db_features as $idx => $f):
                $color     = $icon_colors[$idx % count($icon_colors)];
                $harga     = (int) ($f['harga_satuan'] ?? 0);
                $harga_text = $harga > 0 ? 'Rp ' . number_format($harga, 0, ',', '.') : 'Hubungi Admin';
                $wa_text   = urlencode('Halo Admin, saya ingin membeli tambahan fitur SIPANDA: ' . $f['module_name']);
            ?>
            <div class="feat-item" style="flex-direction:column; justify-content:space-between;">
                <!-- Icon & Name -->
                <div>
                    <div style="display:flex; align-items:center; gap:16px; margin-bottom:12px;">
                        <div class="feat-icon" style="color:<?= $color[0] ?>; background:<?= $color[1] ?>;">
                            <i class="fas <?= clean($f['icon']) ?>"></i>
                        </div>
                        <h4 class="feat-title" style="margin-bottom:0; font-size:18px;">
                            <?= clean($f['module_name']) ?>
                        </h4>
                    </div>
                    <p class="feat-desc"><?= clean($f['description']) ?></p>
                </div>

                <!-- Price & CTA -->
                <div style="margin-top:24px; padding-top:20px; border-top:1px solid var(--card-border); display:flex; align-items:center; justify-content:space-between;">
                    <div style="font-family:'Outfit',sans-serif; font-size:20px; font-weight:800; color:#fff;">
                        <?= $harga_text ?>
                    </div>
                    <a href="https://wa.me/6281389297614?text=<?= $wa_text ?>"
                       target="_blank"
                       style="background:rgba(255,255,255,0.1); color:#fff; text-decoration:none; padding:8px 16px; border-radius:12px; font-size:13px; font-weight:700; transition:0.3s;"
                       onmouseover="this.style.background='var(--gold)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                        <i class="fab fa-whatsapp"></i> Pesan
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- End Tab Satuan -->

</div>
<!-- End Pricing Section -->

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION: FITUR AKTIF PAKET SAAT INI -->
<!-- ═══════════════════════════════════════════════════════ -->
<div class="section-heading">
    <h2>Fitur & Modul Utama (Paket Anda Saat Ini)</h2>
    <p style="color:var(--text-muted);">Kenali beberapa fitur andalan dari SIPANDA v2.0</p>
</div>

<div class="features-grid">
    <?php foreach ($db_features as $idx => $f):
        $is_active = (bool) $f['is_active'];
        $color     = $icon_colors[$idx % count($icon_colors)];
        $icon_style = $is_active ? "color:{$color[0]}; background:{$color[1]};" : '';
    ?>
    <div class="feat-item <?= $is_active ? '' : 'inactive' ?>">

        <!-- Status Icon -->
        <div class="feat-status <?= $is_active ? 'active' : 'inactive' ?>">
            <?php if ($is_active): ?>
                <i class="fas fa-check-circle" title="Tersedia di paket langganan Anda"></i>
            <?php else: ?>
                <i class="fas fa-lock" title="Belum tersedia di paket ini"></i>
            <?php endif; ?>
        </div>

        <!-- Feature Icon -->
        <div class="feat-icon" style="<?= $icon_style ?>">
            <i class="fas <?= clean($f['icon']) ?>"></i>
        </div>

        <!-- Feature Info -->
        <div>
            <h4 class="feat-title"><?= clean($f['module_name']) ?></h4>
            <p class="feat-desc"><?= clean($f['description']) ?></p>
        </div>

    </div>
    <?php endforeach; ?>
</div>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- FOOTER -->
<!-- ═══════════════════════════════════════════════════════ -->
<footer>
    <p>&copy; <?= date('Y') ?> SIPANDA by ASR Production. All rights reserved.</p>
</footer>

<script>
// ═══════════════════════════════════════════════════════
// TAB SWITCHING
// ═══════════════════════════════════════════════════════
function switchTab(tabId) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.currentTarget.classList.add('active');

    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById('tab-' + tabId).classList.add('active');
}
</script>

</body>
</html>