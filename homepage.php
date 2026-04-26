<?php
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/fungsi.php';

$setting = $pdo->query("SELECT * FROM tbl_setting WHERE id=1")->fetch();
$nama = $setting['nama_sekolah'] ?? 'SIPANDA';
$instansi = $setting['instansi_atas'] ?? '';
$yayasan = $setting['nama_yayasan'] ?? '';
$alamat = $setting['alamat'] ?? 'Indonesia';
$telepon = $setting['telepon'] ?? '';
$email = $setting['email'] ?? '';
$paket_langganan = $setting['paket_langganan'] ?? 'Basic';

// CMS Content Data
$content = $pdo->query("SELECT * FROM tbl_cms_content ORDER BY urutan")->fetchAll();
$menu = $pdo->query("SELECT * FROM tbl_cms_menu WHERE status='active' ORDER BY urutan")->fetchAll();

// Statistics
$siswa_count = $pdo->query("SELECT COUNT(*) FROM tbl_siswa WHERE status='Aktif'")->fetchColumn();
$guru_count = $pdo->query("SELECT COUNT(*) FROM tbl_guru WHERE status='Aktif'")->fetchColumn();
$kelas_count = $pdo->query("SELECT COUNT(*) FROM tbl_kelas")->fetchColumn();
$eskul_count = $pdo->query("SELECT COUNT(*) FROM tbl_eskul")->fetchColumn();

// Fetch Data for Sections
$info = $pdo->query("SELECT * FROM tbl_display_info WHERE status='aktif' ORDER BY id DESC LIMIT 3")->fetchAll();
$prestasi = $pdo->query("SELECT p.*, s.nama, s.foto FROM tbl_prestasi p JOIN tbl_siswa s ON p.id_siswa = s.id_siswa ORDER BY p.tanggal DESC LIMIT 4")->fetchAll();
$kemitraan = $pdo->query("SELECT * FROM tbl_humas_kemitraan WHERE status='Aktif' ORDER BY id DESC LIMIT 6")->fetchAll();
$kegiatan = $pdo->query("SELECT * FROM tbl_agama_kegiatan ORDER BY tanggal DESC LIMIT 4")->fetchAll();

// Fetch Gallery & Banner
$db_banner = $pdo->query("SELECT * FROM tbl_gallery WHERE type='banner' ORDER BY id DESC LIMIT 1")->fetch();
$db_galeri = $pdo->query("SELECT * FROM tbl_gallery WHERE type='galeri' ORDER BY id DESC LIMIT 8")->fetchAll();

?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($nama) ?> - Sistem Informasi Pokok Pendidikan</title>
    <meta name="description" content="Website Resmi dan Sistem Informasi Akademik <?= clean($nama) ?>">
    <meta name="theme-color" content="#0a1628">
    <link rel="manifest" href="manifest.json">
    <?php if (!empty($setting['logo_web'])): ?>
        <link rel="shortcut icon" href="gambar/<?= $setting['logo_web'] ?>" type="image/x-icon">
        <link rel="apple-touch-icon" href="gambar/<?= $setting['logo_web'] ?>">
    <?php endif; ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { 50: '#f0f4fa', 100: '#e1e9f4', 200: '#c8d7ea', 300: '#a3bdda', 400: '#779fc6', 500: '#5684b3', 600: '#436994', 700: '#365478', 800: '#2f4764', 900: '#2a3d54', 950: '#0a1628' },
                        gold: { 50: '#fcfaf2', 100: '#f7efe1', 200: '#f0dbbe', 300: '#e7c191', 400: '#dea160', 500: '#d78839', 600: '#ca6e2c', 700: '#a85325', 800: '#864324', 900: '#6c3821', 950: '#3a1b0e' }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        serif: ['"Playfair Display"', 'serif'],
                    }
                }
            }
        }
    </script>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #fafbfd;
            color: #1e293b;
        }

        h1,
        h2,
        h3,
        .font-serif {
            font-family: 'Playfair Display', serif;
        }

        /* Subtle Mesh Gradient Background */
        .hero-bg {
            background-color: #f8fafc;
            background-image:
                radial-gradient(at 10% 20%, rgba(212, 168, 83, 0.07) 0px, transparent 50%),
                radial-gradient(at 90% 80%, rgba(10, 22, 40, 0.05) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(59, 130, 246, 0.05) 0px, transparent 50%);
        }

        .glass-nav {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }

        .text-gradient {
            background: linear-gradient(135deg, #0a1628 0%, #1e3a8a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .text-gradient-gold {
            background: linear-gradient(135deg, #d78839 0%, #a85325 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-15px);
            }
        }

        .float-anim {
            animation: float 6s ease-in-out infinite;
        }

        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.5, 0, 0, 1);
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Dropdown Menu Styles */
        .dropdown:hover .dropdown-menu {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .dropdown-menu {
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            transform: translateY(10px);
        }

        .dropdown-menu::before {
            content: '';
            position: absolute;
            top: -20px;
            left: 0;
            width: 100%;
            height: 20px;
        }
    </style>
</head>

<body class="antialiased overflow-x-hidden selection:bg-gold-500 selection:text-white">

    <!-- Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass-nav transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo area -->
                <div class="flex items-center gap-3">
                    <!-- Agency Logo (Logo Kiri) if exists -->
                    <?php if (!empty($setting['logo_kiri'])): ?>
                        <div class="w-10 h-10 flex items-center justify-center">
                            <img src="<?= BASE_URL ?>gambar/<?= $setting['logo_kiri'] ?>" class="w-10 h-10 object-contain">
                        </div>
                        <!-- Separator line between logos -->
                        <div class="w-px h-8 bg-slate-200 mx-1"></div>
                    <?php endif; ?>

                    <div
                        class="w-12 h-12 rounded-xl bg-navy-950 flex items-center justify-center font-bold text-xl text-gold-400 shadow-md">
                        <?php if (!empty($setting['logo_web'])): ?>
                            <img src="<?= BASE_URL ?>gambar/<?= $setting['logo_web'] ?>" class="w-10 h-10 object-contain">
                        <?php else: ?>
                            <?= substr(clean($nama), 0, 1) ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-lg text-navy-950 leading-tight"><?= clean($nama) ?></span>
                        <span
                            class="text-[9px] text-slate-500 font-bold uppercase tracking-widest border-t border-slate-200 mt-0.5 pt-0.5"><?= !empty($instansi) ? clean($instansi) : (!empty($yayasan) ? clean($yayasan) : 'Sistem Informasi Akademik') ?></span>
                    </div>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center gap-8">
                    <a href="#beranda"
                        class="text-sm font-semibold text-slate-600 hover:text-navy-950 transition-colors">Beranda</a>
                    <a href="#profil"
                        class="text-sm font-semibold text-slate-600 hover:text-navy-950 transition-colors">Profil</a>
                    <a href="#akademik"
                        class="text-sm font-semibold text-slate-600 hover:text-navy-950 transition-colors">Akademik</a>
                    <a href="#galeri"
                        class="text-sm font-semibold text-slate-600 hover:text-navy-950 transition-colors">Galeri</a>
                    <a href="#informasi"
                        class="text-sm font-semibold text-slate-600 hover:text-navy-950 transition-colors">Informasi</a>
                    
                    <!-- Dropdown Layanan Digital -->
                    <div class="relative dropdown group">
                        <button class="flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-navy-950 transition-colors outline-none">
                            Layanan Digital <i class="fas fa-chevron-down text-[10px] opacity-50 group-hover:rotate-180 transition-transform"></i>
                        </button>
                        <div class="dropdown-menu absolute left-0 mt-3 w-56 bg-white/90 backdrop-blur-xl border border-slate-100 rounded-2xl shadow-xl opacity-0 pointer-events-none z-50">
                            <div class="p-2 space-y-1">
                                <a href="kantin.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-600 hover:bg-navy-50 hover:text-navy-950 transition-all">
                                    <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center"><i class="fas fa-utensils text-xs"></i></div>
                                    E-Kantin Sekolah
                                </a>
                                <a href="perpustakaan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-blue-600 hover:bg-blue-50 transition-all">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fas fa-book-reader text-xs"></i></div>
                                    Perpustakaan Digital
                                </a>
                                <a href="admin/com_humas/tamu_kiosk.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-rose-600 hover:bg-rose-50 transition-all">
                                    <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center"><i class="fas fa-book-open text-xs"></i></div>
                                    Buku Tamu Digital
                                </a>
                                <a href="#verifikasi" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-600 hover:bg-blue-50 hover:text-blue-700 transition-all">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fas fa-certificate text-xs"></i></div>
                                    Verifikasi SKL
                                </a>
                                <a href="cek_tagihan_public.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-600 hover:bg-emerald-50 hover:text-emerald-700 transition-all border-t border-slate-100 mt-1">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center"><i class="fas fa-file-invoice-dollar text-xs"></i></div>
                                    Cek Tagihan Publik
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="login.php"
                            class="bg-navy-950 hover:bg-navy-800 text-white px-6 py-2.5 rounded-full text-sm font-bold shadow-lg shadow-navy-950/20 transition-all hover:-translate-y-0.5"><i
                                class="fas fa-user-circle mr-2 text-gold-400"></i>Portal Login</a>
                        
                        <a href="landing.php" class="bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white px-4 py-2.5 rounded-full text-xs font-bold shadow-lg shadow-orange-500/30 transition-all hover:-translate-y-0.5 flex items-center gap-2 border border-orange-400/50">
                            <i class="fas fa-crown text-amber-100"></i> <?= htmlspecialchars($paket_langganan) ?>
                        </a>
                    </div>
                </div>

                <!-- Mobile Toggle -->
                <button id="mobileToggle" class="md:hidden text-2xl text-navy-950 focus:outline-none"><i
                        class="fas fa-bars"></i></button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu"
            class="hidden md:hidden bg-white border-t border-slate-100 shadow-xl px-4 pt-2 pb-6 space-y-1 absolute w-full">
            <a href="#beranda"
                class="block py-3 px-4 rounded-lg text-sm font-semibold hover:bg-slate-50 text-slate-700">Beranda</a>
            <a href="#profil"
                class="block py-3 px-4 rounded-lg text-sm font-semibold hover:bg-slate-50 text-slate-700">Profil
                Sekolah</a>
            <a href="#akademik"
                class="block py-3 px-4 rounded-lg text-sm font-semibold hover:bg-slate-50 text-slate-700">Program &
                Akademik</a>
            <a href="#informasi"
                class="block py-3 px-4 rounded-lg text-sm font-semibold hover:bg-slate-50 text-slate-700">Berita &
                Informasi</a>
            
            <!-- Mobile Layanan Digital Section -->
            <div class="px-4 py-3 mt-4 border-t border-slate-50">
                <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Layanan Digital</span>
                <div class="mt-2 space-y-1">
                    <a href="kantin.php"
                        class="flex items-center gap-3 py-3 rounded-lg text-sm font-semibold text-slate-700">
                        <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center"><i class="fas fa-utensils text-xs"></i></div>
                        E-Kantin
                    </a>
                    <a href="perpustakaan.php"
                        class="flex items-center gap-3 py-3 rounded-lg text-sm font-semibold text-blue-600 font-bold">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fas fa-book-reader text-xs"></i></div>
                        Perpustakaan Digital
                    </a>
                    <a href="admin/com_humas/tamu_kiosk.php"
                        class="flex items-center gap-3 py-3 rounded-lg text-sm font-semibold text-rose-600 font-bold">
                        <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center"><i class="fas fa-book-open text-xs"></i></div>
                        Buku Tamu Digital
                    </a>
                    <a href="#verifikasi"
                        class="flex items-center gap-3 py-3 rounded-lg text-sm font-semibold text-slate-700">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fas fa-certificate text-xs"></i></div>
                        Verifikasi SKL
                    </a>
                    <a href="cek_tagihan_public.php"
                        class="flex items-center gap-3 py-3 rounded-lg text-sm font-semibold text-slate-700">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center"><i class="fas fa-file-invoice-dollar text-xs"></i></div>
                        Cek Tagihan Publik
                    </a>
                </div>
            </div>

            <div class="pt-4 mt-2 border-t border-slate-100 flex flex-col gap-2">
                <a href="login.php"
                    class="block bg-navy-950 text-white text-center py-3 rounded-xl text-sm font-bold shadow-md"><i
                        class="fas fa-user-circle mr-2 text-gold-400"></i>Login SIPANDA</a>
                
                <a href="landing.php"
                    class="block bg-gradient-to-r from-amber-500 to-orange-500 text-white text-center py-3 rounded-xl text-sm font-bold shadow-md"><i
                        class="fas fa-crown mr-2 text-amber-100"></i>Status Paket: <?= htmlspecialchars($paket_langganan) ?></a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="beranda" class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 px-4 hero-bg overflow-hidden">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row items-center gap-12 lg:gap-20 relative z-10">
            <div class="flex-1 text-center lg:text-left reveal active">
                <div
                    class="inline-flex items-center gap-2 bg-white px-4 py-2 rounded-full text-[10px] font-bold text-navy-800 shadow-sm border border-slate-100 mb-6 uppercase tracking-wider">
                    <span
                        class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span><?= !empty($setting['hero_badge']) ? clean($setting['hero_badge']) : 'Penerimaan Siswa Baru Dibuka' ?>
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black mb-6 leading-tight text-navy-950">
                    <?= !empty($setting['hero_judul']) ? $setting['hero_judul'] : 'Membangun Generasi <br> <span class="text-gradient">Berprestasi & Berakhlak</span>' ?>
                </h1>
                <p class="text-lg text-slate-600 max-w-xl mx-auto lg:mx-0 mb-10 leading-relaxed">
                    <?= !empty($setting['hero_deskripsi']) ? clean($setting['hero_deskripsi']) : 'Selamat datang di platform digital resmi MI Asrorul Huda. Sistem Informasi Pokok Pendidikan (SIPANDA) mempermudah akses informasi bagi seluruh civitas akademika.' ?>
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="#profil"
                        class="bg-navy-950 text-white px-8 py-4 rounded-xl text-sm font-bold shadow-xl shadow-navy-950/20 transition-all hover:bg-navy-800 hover:-translate-y-1">
                        Mulai Jelajahi
                    </a>
                    <a href="login.php"
                        class="bg-white text-navy-950 border border-slate-200 px-8 py-4 rounded-xl text-sm font-bold shadow-sm transition-all hover:border-gold-400 hover:text-gold-600">
                        <i class="fas fa-lock mr-2 text-slate-400"></i>Akses Sistem
                    </a>
                    <button id="btnInstallPwa" style="display: none;"
                        class="bg-emerald-600 text-white px-8 py-4 rounded-xl text-sm font-bold shadow-xl shadow-emerald-600/20 transition-all hover:bg-emerald-500 hover:-translate-y-1">
                        <i class="fas fa-download mr-2 text-emerald-200"></i>Install Aplikasi
                    </button>
                </div>
            </div>

            <div class="flex-1 relative w-full max-w-lg lg:max-w-none mx-auto reveal active float-anim">
                <div
                    class="absolute inset-0 bg-gradient-to-tr from-gold-200/40 to-navy-200/40 rounded-3xl blur-3xl transform rotate-3">
                </div>
                <?php
                $hero_img = $db_banner ? BASE_URL . 'assets/uploads/gallery/' . $db_banner['image'] : 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';
                ?>
                <img src="<?= $hero_img ?>" alt="Siswa Berprestasi"
                    class="relative z-10 w-full h-[450px] rounded-3xl shadow-2xl object-cover border-8 border-white aspect-[4/3] lg:aspect-auto">

                <!-- Floating cards -->
                <div class="absolute -right-6 lg:-right-12 top-10 glass-card p-4 rounded-2xl z-20 flex items-center gap-4 animate-bounce"
                    style="animation-duration: 4s;">
                    <div
                        class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg">
                        <i class="fas fa-check-circle"></i></div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">
                            <?= !empty($setting['hero_floating_badge1']) ? clean($setting['hero_floating_badge1']) : 'Akreditasi' ?>
                        </p>
                        <p class="text-sm font-bold text-navy-950">
                            <?= !empty($setting['hero_floating_text1']) ? clean($setting['hero_floating_text1']) : 'A (Sangat Baik)' ?>
                        </p>
                    </div>
                </div>

                <div class="absolute -left-6 lg:-left-12 bottom-10 glass-card p-4 rounded-2xl z-20 flex items-center gap-4 animate-bounce"
                    style="animation-duration: 5s; animation-delay: 1s;">
                    <div
                        class="w-10 h-10 rounded-full bg-gold-100 text-gold-600 flex items-center justify-center text-lg">
                        <i class="fas fa-trophy"></i></div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">
                            <?= !empty($setting['hero_floating_badge2']) ? clean($setting['hero_floating_badge2']) : 'Program Unggulan' ?>
                        </p>
                        <p class="text-sm font-bold text-navy-950">
                            <?= !empty($setting['hero_floating_text2']) ? clean($setting['hero_floating_text2']) : 'Kelas Tahfidz & IT' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistik Strip -->
    <div class="bg-navy-950 text-white py-12 relative z-20 border-t-4 border-gold-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 divide-x divide-white/10 text-center">
                <div class="px-4 reveal">
                    <i class="fas fa-users text-3xl font-light text-gold-400 mb-3 block"></i>
                    <div class="text-4xl font-serif font-bold mb-1"><span class="counter"
                            data-target="<?= $siswa_count ?>">0</span>+</div>
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-widest text-white/70">Peserta
                        Didik</div>
                </div>
                <div class="px-4 reveal" style="transition-delay: 100ms;">
                    <i class="fas fa-chalkboard-teacher text-3xl font-light text-gold-400 mb-3 block"></i>
                    <div class="text-4xl font-serif font-bold mb-1"><span class="counter"
                            data-target="<?= $guru_count ?>">0</span>+</div>
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-widest text-white/70">Tenaga
                        Pendidik</div>
                </div>
                <div class="px-4 reveal" style="transition-delay: 200ms;">
                    <i class="fas fa-door-open text-3xl font-light text-gold-400 mb-3 block"></i>
                    <div class="text-4xl font-serif font-bold mb-1"><span class="counter"
                            data-target="<?= $kelas_count ?>">0</span></div>
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-widest text-white/70">Ruang
                        Kelas</div>
                </div>
                <div class="px-4 reveal" style="transition-delay: 300ms;">
                    <i class="fas fa-futbol text-3xl font-light text-gold-400 mb-3 block"></i>
                    <div class="text-4xl font-serif font-bold mb-1"><span class="counter"
                            data-target="<?= $eskul_count ?>">0</span></div>
                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-widest text-white/70">
                        Ekstrakurikuler</div>
                </div>
            </div>
        </div>
    </div>



    <!-- Profil & Keunggulan -->
    <section id="profil" class="py-24 px-4 bg-white relative">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16 reveal">
                <h3 class="text-gold-600 font-bold uppercase tracking-widest text-sm mb-2">
                    <?= clean($setting['keunggulan_sub']) ?></h3>
                <h2 class="text-3xl md:text-4xl font-black text-navy-950">
                    <?= nl2br(clean($setting['keunggulan_judul'])) ?></h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php
                $db_keunggulan = $pdo->query("SELECT * FROM tbl_keunggulan WHERE status='aktif' ORDER BY urutan ASC")->fetchAll();
                $db_kerjasama = $pdo->query("SELECT * FROM tbl_kerjasama WHERE status='aktif' ORDER BY urutan ASC")->fetchAll();
                foreach ($db_keunggulan as $idx => $k):
                    $delay = $idx * 100;
                    ?>
                    <div class="glass-card p-8 rounded-3xl bg-slate-50 border border-slate-100 hover:shadow-xl hover:border-gold-300 transition-all duration-300 group reveal"
                        style="transition-delay: <?= $delay ?>ms;">
                        <div
                            class="w-14 h-14 rounded-2xl bg-white shadow-sm flex items-center justify-center text-<?= $k['warna'] ?>-600 text-xl mb-6 group-hover:scale-110 group-hover:<?= $idx % 2 == 0 ? 'rotate-3' : '-rotate-3' ?> transition-transform">
                            <i class="<?= $k['ikon'] ?>"></i>
                        </div>
                        <h3 class="text-xl font-bold text-navy-950 mb-3"><?= clean($k['judul']) ?></h3>
                        <p class="text-sm text-slate-600 leading-relaxed"><?= clean($k['deskripsi']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Prestasi & Aktivitas -->
    <section id="akademik" class="py-24 px-4 bg-slate-50 relative overflow-hidden">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">

                <!-- Kiri: Board of Honor -->
                <div class="lg:col-span-5 reveal">
                    <h3 class="text-gold-600 font-bold uppercase tracking-widest text-sm mb-2">Hall of Fame</h3>
                    <h2 class="text-3xl md:text-4xl font-black text-navy-950 mb-6">Prestasi Terkini <br>Siswa Siswi Kami
                    </h2>
                    <p class="text-slate-600 mb-8 leading-relaxed">Kami bangga dengan deretan pencapaian siswa di kancah
                        akademik maupun non-akademik, membangun semangat kompetitif yang sehat.</p>

                    <div class="space-y-4">
                        <?php if ($prestasi):
                            foreach ($prestasi as $p): ?>
                                <div
                                    class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4 hover:shadow-md transition-shadow">
                                    <div
                                        class="w-12 h-12 rounded-full overflow-hidden bg-slate-100 flex-shrink-0 border-2 border-gold-300 p-0.5">
                                        <img src="<?= BASE_URL ?>foto_siswa/<?= $p['foto'] ?>"
                                            onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($p['nama']) ?>&background=random&color=fff&bold=true';"
                                            class="w-full h-full object-cover rounded-full">
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-navy-950 text-sm"><?= clean($p['nama']) ?></h4>
                                        <p class="text-xs font-semibold text-gold-600"><?= clean($p['nama_prestasi']) ?></p>
                                        <p class="text-[10px] text-slate-400 uppercase mt-0.5"><i
                                                class="fas fa-award mr-1"></i>Tingkat <?= clean($p['tingkat']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; else: ?>
                            <div class="text-slate-500 italic text-sm">Masih mengumpulkan data prestasi...</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Kanan: Kegiatan Agama -->
                <div class="lg:col-span-7 reveal" style="transition-delay: 200ms;">
                    <div
                        class="bg-navy-950 rounded-[2.5rem] p-8 md:p-12 text-white relative overflow-hidden shadow-2xl">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-gold-500/10 rounded-full blur-3xl"></div>

                        <h3 class="text-gold-400 font-bold uppercase tracking-widest text-sm mb-2 flex items-center"><i
                                class="fas fa-mosque mr-2"></i>Bina Agama & Karakter</h3>
                        <h2 class="text-2xl md:text-3xl font-serif font-bold mb-8">Kabar Kegiatan Ibadah & Sosial</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10">
                            <?php if ($kegiatan):
                                foreach ($kegiatan as $kg): ?>
                                    <div class="group cursor-pointer">
                                        <div class="h-40 bg-navy-800 rounded-2xl mb-4 overflow-hidden relative">
                                            <?php if ($kg['foto']): ?>
                                                <img src="<?= BASE_URL ?>assets/uploads/gambar/<?= $kg['foto'] ?>"
                                                    class="w-full h-full object-cover opacity-80 group-hover:opacity-100 group-hover:scale-105 transition-all duration-500">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center text-navy-600 text-4xl">
                                                    <i class="fas fa-praying-hands"></i></div>
                                            <?php endif; ?>
                                            <div
                                                class="absolute top-3 left-3 bg-white/90 backdrop-blur-sm px-2 py-1 rounded-lg text-[10px] font-bold text-navy-900">
                                                <?= clean($kg['jenis']) ?></div>
                                        </div>
                                        <h4
                                            class="font-bold text-white text-base group-hover:text-gold-400 transition-colors line-clamp-2">
                                            <?= clean($kg['nama_kegiatan']) ?></h4>
                                        <p class="text-xs text-slate-400 mt-1"><i
                                                class="far fa-calendar-alt mr-1"></i><?= tgl_indo($kg['tanggal']) ?> ·
                                            <?= clean($kg['pelaksana']) ?></p>
                                    </div>
                                <?php endforeach; else: ?>
                                <div class="col-span-2 text-slate-400 italic text-sm">Belum ada entri kegiatan keagamaan
                                    terbaru.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Informasi & Pengumuman -->
    <section id="informasi" class="py-24 px-4 bg-white">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-end mb-12 reveal">
                <div>
                    <h3 class="text-gold-600 font-bold uppercase tracking-widest text-sm mb-2">Papan Pengumuman</h3>
                    <h2 class="text-3xl md:text-4xl font-black text-navy-950">Berita & Informasi <br>Terbaru Sekolah
                    </h2>
                </div>
                <a href="#"
                    class="hidden md:inline-flex items-center text-sm font-bold text-blue-600 hover:text-blue-800 transition-colors">Lihat
                    Semua Info <i
                        class="fas fa-arrow-right ml-2 transition-transform group-hover:translate-x-1"></i></a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if ($info):
                    foreach ($info as $idx => $n): ?>
                        <div class="bg-white border text-left border-slate-100 rounded-3xl overflow-hidden hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 flex flex-col reveal"
                            style="transition-delay: <?= $idx * 100 ?>ms;">
                            <div class="h-48 bg-slate-100 relative overflow-hidden">
                                <?php if (!empty($n['gambar'])): ?>
                                    <img src="<?= BASE_URL ?>cms_images/<?= $n['gambar'] ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div
                                        class="w-full h-full flex justify-center items-center bg-gradient-to-br from-blue-50 to-slate-100 text-blue-200 text-5xl">
                                        <i class="far fa-newspaper"></i>
                                    </div>
                                <?php endif; ?>
                                <div
                                    class="absolute bottom-0 left-0 right-0 h-1/2 bg-gradient-to-t from-black/60 to-transparent">
                                </div>
                                <span
                                    class="absolute bottom-4 left-4 text-xs font-bold bg-blue-600 text-white px-3 py-1 rounded-full uppercase tracking-wider">Info
                                    Resmi</span>
                            </div>
                            <div class="p-6 flex flex-col flex-grow">
                                <h4 class="font-bold text-navy-950 text-xl mb-3 line-clamp-2 leading-tight">
                                    <?= htmlspecialchars($n['judul']) ?></h4>
                                <p class="text-sm text-slate-500 mb-6 line-clamp-3 leading-relaxed">
                                    <?= strip_tags($n['konten']) ?></p>
                                <div class="mt-auto flex items-center justify-between border-t border-slate-100 pt-4">
                                    <span class="text-xs text-slate-400 font-medium"><i
                                            class="far fa-clock mr-1"></i>Administrator</span>
                                    <?php
                                    $link = !empty($n['external_link']) ? $n['external_link'] : 'info.php?s=' . $n['slug'];
                                    $target = !empty($n['external_link']) ? 'target="_blank"' : '';
                                    ?>
                                    <a href="<?= $link ?>" <?= $target ?>
                                        class="text-xs font-bold text-navy-900 hover:text-gold-600 flex items-center transition-colors">Baca
                                        Lengkap <i class="fas fa-chevron-right ml-1 text-[10px]"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                    <p class="col-span-3 text-center text-slate-500 py-10">Belum ada informasi terbaru untuk saat ini.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <?php if ($db_galeri): ?>
        <section id="galeri" class="py-24 px-4 bg-slate-50 relative">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16 reveal">
                    <h3 class="text-gold-600 font-bold uppercase tracking-widest text-sm mb-2">Momen & Kegiatan</h3>
                    <h2 class="text-3xl md:text-4xl font-black text-navy-950">Galeri Foto Siswa <br><span
                            class="text-gradient">Kebanggaan Sekolah</span></h2>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php foreach ($db_galeri as $idx => $g): ?>
                        <div class="group relative overflow-hidden rounded-2xl aspect-square shadow-sm hover:shadow-xl transition-all duration-500 reveal"
                            style="transition-delay: <?= $idx * 50 ?>ms;">
                            <img src="<?= BASE_URL ?>assets/uploads/gallery/<?= $g['image'] ?>"
                                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-navy-950/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex flex-col justify-end p-4">
                                <p
                                    class="text-white text-xs font-bold transform translate-y-4 group-hover:translate-y-0 transition-transform duration-500">
                                    <?= clean($g['title']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-12 text-center reveal">
                    <p class="text-slate-400 text-sm italic">"Setiap momen adalah bagian dari perjalanan meraih prestasi"
                    </p>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Layanan Digital Section (Presensi & SKL) -->
    <section id="verifikasi" class="py-24 px-4 bg-navy-950 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-gold-500/10 rounded-full blur-3xl"></div>
        <div
            class="absolute bottom-0 left-0 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl text-navy-950 font-bold text-center">
        </div>

        <div class="max-w-5xl mx-auto relative z-10">
            <div class="text-center mb-12">
                <div
                    class="inline-flex items-center gap-2 bg-white/10 px-4 py-2 rounded-full text-xs font-bold text-gold-400 mb-6 uppercase tracking-wider border border-white/5">
                    <i class="fas fa-microchip text-blue-400"></i> SIPANDA Smart Campus
                </div>
                <h2 class="text-3xl md:text-5xl font-serif font-bold mb-4">Layanan Digital <span
                        class="text-gradient-gold">Mandiri</span></h2>
                <p class="text-slate-400 max-w-2xl mx-auto text-sm">Akses presensi kehadiran dan verifikasi dokumen
                    resmi sekolah dalam satu pintu digital yang aman dan cepat.</p>
            </div>

            <!-- Tabs Interface -->
            <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-2 max-w-2xl mx-auto mb-12 flex">
                <button onclick="switchTab('presence')" id="tab-presence"
                    class="flex-1 py-4 rounded-[2rem] text-sm font-bold transition-all flex items-center justify-center gap-2 bg-gold-500 text-white shadow-lg shadow-gold-500/20">
                    <i class="fas fa-user-check"></i> Presensi Siswa
                </button>
                <button onclick="switchTab('skl')" id="tab-skl"
                    class="flex-1 py-4 rounded-[2rem] text-sm font-bold transition-all flex items-center justify-center gap-2 text-slate-400 hover:text-white">
                    <i class="fas fa-shield-alt"></i> Verifikasi SKL
                </button>
                <a href="kantin.php" id="tab-kantin"
                    class="flex-1 py-4 rounded-[2rem] text-sm font-bold transition-all flex items-center justify-center gap-2 text-slate-400 hover:text-white">
                    <i class="fas fa-store"></i> E-Kantin
                </a>
                <a href="admin/com_humas/tamu_kiosk.php" id="tab-tamu"
                    class="flex-1 py-4 rounded-[2rem] text-sm font-bold transition-all flex items-center justify-center gap-2 text-slate-400 hover:text-rose-400">
                    <i class="fas fa-id-card"></i> Buku Tamu
                </a>
                <a href="cek_tagihan_public.php" id="tab-tagihan"
                    class="flex-1 py-4 rounded-[2rem] text-sm font-bold transition-all flex items-center justify-center gap-2 text-slate-400 hover:text-emerald-400">
                    <i class="fas fa-file-invoice-dollar"></i> Cek Tagihan
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-stretch max-w-4xl mx-auto">
                <!-- Scan QR Card -->
                <div
                    class="bg-white/5 border border-white/10 rounded-3xl p-10 hover:border-gold-500/30 transition-all group flex flex-col items-center justify-center min-h-[350px]">
                    <div id="scanner-icon"
                        class="w-20 h-20 bg-gold-500/20 text-gold-500 rounded-3xl flex items-center justify-center text-4xl mb-8 group-hover:scale-110 transition-transform">
                        <i class="fas fa-qrcode"></i></div>
                    <h3 id="scanner-title" class="text-2xl font-bold mb-4">Scan QR Presensi</h3>
                    <p id="scanner-desc" class="text-xs text-slate-400 mb-10 px-4 text-center leading-relaxed">Gunakan
                        Kartu Pelajar Digital Anda. Arahkan QR Code ke kamera untuk melakukan absensi Masuk atau Pulang
                        secara otomatis.</p>
                    <button onclick="startScanner()"
                        class="bg-gold-500 hover:bg-gold-600 text-white px-10 py-4 rounded-2xl text-sm font-extrabold shadow-xl shadow-gold-500/20 transition-all hover:-translate-y-1 active:scale-95">
                        <i class="fas fa-camera mr-2"></i>Buka Kamera Scanner
                    </button>
                </div>

                <!-- Manual / Info Card -->
                <div id="action-card"
                    class="bg-white/5 border border-white/10 rounded-3xl p-10 hover:border-blue-500/30 transition-all group flex flex-col items-center justify-center min-h-[350px]">
                    <div id="action-icon"
                        class="w-20 h-20 bg-blue-500/20 text-blue-500 rounded-3xl flex items-center justify-center text-4xl mb-8 group-hover:scale-110 transition-transform">
                        <i class="fas fa-fingerprint"></i></div>
                    <h3 id="action-title" class="text-2xl font-bold mb-4">Input ID Manual</h3>
                    <p id="action-desc" class="text-xs text-slate-400 mb-8 text-center leading-relaxed">Jika kamera
                        bermasalah, masukkan ID Kartu (rfid_uid) Anda secara manual di bawah ini untuk presensi.</p>

                    <form id="action-form" onsubmit="handleManualAction(event)" class="w-full">
                        <input type="text" id="action-input" name="token" placeholder="Masukkan ID Kartu..."
                            class="w-full bg-slate-900 border border-white/10 rounded-2xl px-5 py-4 text-sm mb-4 text-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:outline-none placeholder:text-slate-600 transition-all font-mono">
                        <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-500 text-white py-4 rounded-2xl text-sm font-extrabold shadow-xl shadow-blue-600/20 transition-all active:scale-95">
                            Proses Absensi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Scanner -->
    <div id="modalScanner" class="fixed inset-0 z-[100] flex items-center justify-center px-4 hidden py-24">
        <div class="absolute inset-0 bg-black/95 backdrop-blur-xl" onclick="stopScanner()"></div>
        <div
            class="bg-white rounded-[2.5rem] w-full max-w-md relative z-10 p-8 overflow-hidden shadow-2xl border border-white/10">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-xl font-bold text-navy-950">Smart Scanner</h3>
                    <p id="modal-subtitle" class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">
                        Mode: Presensi Siswa</p>
                </div>
                <button onclick="stopScanner()"
                    class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-red-100 hover:text-red-500 transition-all"><i
                        class="fas fa-times"></i></button>
            </div>

            <div class="relative">
                <div id="reader"
                    class="rounded-3xl overflow-hidden border-4 border-slate-100 bg-slate-50 aspect-square"></div>
                <!-- Overlay decorations -->
                <div
                    class="absolute top-0 left-0 w-10 h-10 border-t-4 border-l-4 border-gold-500 rounded-tl-2xl m-4 pointer-events-none">
                </div>
                <div
                    class="absolute top-0 right-0 w-10 h-10 border-t-4 border-r-4 border-gold-500 rounded-tr-2xl m-4 pointer-events-none">
                </div>
                <div
                    class="absolute bottom-0 left-0 w-10 h-10 border-b-4 border-l-4 border-gold-500 rounded-bl-2xl m-4 pointer-events-none">
                </div>
                <div
                    class="absolute bottom-0 right-0 w-10 h-10 border-b-4 border-r-4 border-gold-500 rounded-br-2xl m-4 pointer-events-none">
                </div>
            </div>

            <p class="text-[10px] text-center text-slate-400 mt-8 leading-relaxed font-medium">Posisikan QR Code di
                dalam bingkai. <br>Jangan tutup kamera atau flash saat memindai.</p>
        </div>
    </div>

    <!-- Absen Alert Success/Error -->
    <div id="absenAlert" class="fixed bottom-10 left-1/2 -translate-x-1/2 z-[110] hidden transition-all duration-500">
        <div class="bg-white rounded-2xl shadow-2xl p-6 border-b-4 flex items-center gap-5 min-w-[320px]">
            <div id="alertIcon" class="w-12 h-12 rounded-full flex items-center justify-center text-xl shadow-lg"></div>
            <div>
                <h4 id="alertTitle" class="font-black text-navy-950 leading-none mb-1">Berhasil!</h4>
                <p id="alertMsg" class="text-xs text-slate-500">Selamat pagi, Muhammad Irfan!</p>
            </div>
        </div>
    </div>

    <!-- Mitra & Kerjasama Section -->
    <?php if ($db_kerjasama): ?>
        <section class="py-20 px-4 bg-white border-t border-slate-100">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-12 reveal">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-[0.3em] mb-3">Mitra Strategis & Kerjasama
                    </h3>
                    <div class="h-1 w-12 bg-gold-500 mx-auto"></div>
                </div>

                <div class="flex flex-wrap justify-center items-center gap-8 md:gap-16 lg:gap-24 opacity-60">
                    <?php foreach ($db_kerjasama as $k): ?>
                        <a href="<?= $k['website'] ?: '#' ?>" target="_blank" title="<?= clean($k['nama_instansi']) ?>"
                            class="group transition-all duration-500 hover:opacity-100 hover:scale-110">
                            <img src="<?= BASE_URL ?>gambar/<?= $k['logo'] ?>" alt="<?= clean($k['nama_instansi']) ?>"
                                class="h-12 md:h-16 w-auto grayscale group-hover:grayscale-0 transition-all duration-500">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-navy-950 text-white pt-20 pb-10 border-t-[8px] border-gold-500 relative overflow-hidden">
        <!-- Background Decor -->
        <div class="absolute top-0 right-0 w-1/2 h-full opacity-5 pointer-events-none">
            <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="w-full h-full">
                <path d="M0,0 L100,0 L100,100 Z" fill="currentColor"></path>
            </svg>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
                <div class="lg:col-span-2">
                    <div class="flex items-center gap-3 mb-6">
                        <div
                            class="w-12 h-12 rounded-xl bg-white flex items-center justify-center font-bold text-navy-950 text-lg shadow-xl shadow-gold-500/20">
                            <?php if (!empty($setting['logo_web'])): ?>
                                <img src="<?= BASE_URL ?>gambar/<?= $setting['logo_web'] ?>"
                                    class="w-10 h-10 object-contain">
                            <?php else: ?>
                                S
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-bold text-xl tracking-wide"><?= clean($nama) ?></span>
                            <?php if (!empty($yayasan)): ?>
                                <span
                                    class="text-[9px] font-bold text-gold-400 uppercase tracking-[3px]"><?= clean($yayasan) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed max-w-md mb-6">
                        Mencetak generasi cerdas, berakhlak mulia, dan siap menghadapi tantangan masa depan melalui
                        sistem pendidikan terpadu
                        <?= !empty($instansi) ? 'di bawah naungan ' . clean($instansi) : 'berbasis nilai luhur' ?>.
                    </p>
                    <div class="flex gap-4">
                        <a href="#"
                            class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-gold-500 transition-colors"><i
                                class="fab fa-facebook-f"></i></a>
                        <a href="#"
                            class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-gold-500 transition-colors"><i
                                class="fab fa-instagram"></i></a>
                        <a href="#"
                            class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-gold-500 transition-colors"><i
                                class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <div>
                    <h4 class="text-gold-400 font-bold mb-6 uppercase tracking-wider text-sm">Tautan Cepat</h4>
                    <ul class="space-y-3 text-sm text-slate-400 font-medium">
                        <li><a href="#beranda" class="hover:text-white transition-colors flex items-center"><i
                                    class="fas fa-chevron-right text-[10px] mr-2 text-gold-500"></i>Beranda</a></li>
                        <li><a href="#profil" class="hover:text-white transition-colors flex items-center"><i
                                    class="fas fa-chevron-right text-[10px] mr-2 text-gold-500"></i>Profil Sekolah</a>
                        </li>
                        <li><a href="#akademik" class="hover:text-white transition-colors flex items-center"><i
                                    class="fas fa-chevron-right text-[10px] mr-2 text-gold-500"></i>Prestasi &
                                Akademik</a></li>
                        <li><a href="#informasi" class="hover:text-white transition-colors flex items-center"><i
                                    class="fas fa-chevron-right text-[10px] mr-2 text-gold-500"></i>Info PPDB</a></li>
                        <li><a href="admin/com_humas/tamu_kiosk.php" class="hover:text-white transition-colors flex items-center"><i
                                    class="fas fa-chevron-right text-[10px] mr-2 text-gold-500"></i>Buku Tamu Digital</a></li>
                        <li><a href="login.php" class="hover:text-white transition-colors flex items-center"><i
                                    class="fas fa-chevron-right text-[10px] mr-2 text-gold-500"></i>Portal Login Guru &
                                Siswa</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-gold-400 font-bold mb-6 uppercase tracking-wider text-sm">Hubungi Kami</h4>
                    <ul class="space-y-4 text-sm text-slate-400">
                        <li class="flex items-start gap-3">
                            <i class="fas fa-map-marker-alt text-gold-500 mt-1"></i>
                            <span class="leading-relaxed"><?= clean($alamat) ?></span>
                        </li>
                        <?php if ($telepon): ?>
                            <li class="flex items-center gap-3">
                                <i class="fas fa-phone-alt text-gold-500"></i>
                                <span><?= clean($telepon) ?></span>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($setting['wa_tu'])): ?>
                            <li class="flex items-center gap-3">
                                <i class="fab fa-whatsapp text-green-500"></i>
                                <span>TU: <?= clean($setting['wa_tu']) ?></span>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($setting['wa_kepsek'])): ?>
                            <li class="flex items-center gap-3">
                                <i class="fab fa-whatsapp text-green-500"></i>
                                <span>Kepsek: <?= clean($setting['wa_kepsek']) ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <div
                class="border-t border-white/10 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-slate-500 font-medium">
                <div>&copy; <?= date('Y') ?> SIPANDA (Sistem Informasi Pokok Pendidikan) <?= APP_VERSION ?>. All rights reserved.
                </div>
                <div class="flex gap-4">
                    <a href="syarat_ketentuan.php" class="hover:text-white transition-colors">Syarat & Ketentuan</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Navbar Scroll Effect
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                navbar.classList.add('shadow-md');
                navbar.classList.replace('py-4', 'py-0');
            } else {
                navbar.classList.remove('shadow-md');
                navbar.classList.replace('py-0', 'py-4');
            }
        });

        // Mobile Menu Toggle
        document.getElementById('mobileToggle')?.addEventListener('click', () => {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });

        // Counters Animation
        const counters = document.querySelectorAll('.counter');
        const observerOptions = { root: null, rootMargin: '0px', threshold: 0.5 };

        const counterObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = entry.target;
                    const endVal = parseInt(target.getAttribute('data-target'));
                    const duration = 2000;
                    const frameRate = 1000 / 60;
                    const totalFrames = Math.round(duration / frameRate);
                    let currentFrame = 0;

                    const easeOutQuad = t => t * (2 - t);

                    const counter = setInterval(() => {
                        currentFrame++;
                        const progress = easeOutQuad(currentFrame / totalFrames);
                        const currentVal = Math.round(endVal * progress);

                        target.textContent = currentVal.toLocaleString('id-ID');

                        if (currentFrame === totalFrames) {
                            clearInterval(counter);
                            target.textContent = endVal.toLocaleString('id-ID');
                        }
                    }, frameRate);

                    observer.unobserve(target);
                }
            });
        }, observerOptions);

        counters.forEach(counter => counterObserver.observe(counter));

        // Reveal Animation
        const revealElements = document.querySelectorAll('.reveal');
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -50px 0px' });

        revealElements.forEach(el => revealObserver.observe(el));

        // Dual Tab Scanner Logic
        let currentTab = 'presence';
        let html5QrCode = null;

        // QR Scanner Initializer
        const scannerScript = document.createElement('script');
        scannerScript.src = "https://unpkg.com/html5-qrcode";
        document.body.appendChild(scannerScript);

        function switchTab(tab) {
            currentTab = tab;
            const btnPresence = document.getElementById('tab-presence');
            const btnSkl = document.getElementById('tab-skl');
            const icon = document.getElementById('scanner-icon');
            const title = document.getElementById('scanner-title');
            const desc = document.getElementById('scanner-desc');
            const actionIcon = document.getElementById('action-icon');
            const actionTitle = document.getElementById('action-title');
            const actionDesc = document.getElementById('action-desc');
            const actionInput = document.getElementById('action-input');
            const actionForm = document.getElementById('action-form');
            const btnProcess = actionForm.querySelector('button');

            if (tab === 'presence') {
                btnPresence.className = "flex-1 py-4 rounded-[2rem] text-sm font-bold transition-all flex items-center justify-center gap-2 bg-gold-500 text-white shadow-lg shadow-gold-500/20";
                btnSkl.className = "flex-1 py-4 rounded-[2rem] text-sm font-bold transition-all flex items-center justify-center gap-2 text-slate-400 hover:text-white";
                icon.innerHTML = '<i class="fas fa-qrcode"></i>';
                title.innerText = 'Scan QR Presensi';
                desc.innerText = 'Gunakan Kartu Pelajar Digital Anda. Arahkan QR Code ke kamera untuk melakukan absensi Masuk atau Pulang secara otomatis.';
                actionIcon.innerHTML = '<i class="fas fa-fingerprint"></i>';
                actionTitle.innerText = 'Input ID Manual';
                actionDesc.innerText = 'Jika kamera bermasalah, masukkan ID Kartu (rfid_uid) Anda secara manual di bawah ini untuk presensi.';
                actionInput.placeholder = 'Masukkan ID Kartu...';
                btnProcess.innerText = 'Proses Absensi';
                document.getElementById('modal-subtitle').innerText = 'Mode: Presensi Siswa';
            } else {
                btnSkl.className = "flex-1 py-4 rounded-[2rem] text-sm font-bold transition-all flex items-center justify-center gap-2 bg-gold-500 text-white shadow-lg shadow-gold-500/20";
                btnPresence.className = "flex-1 py-4 rounded-[2rem] text-sm font-bold transition-all flex items-center justify-center gap-2 text-slate-400 hover:text-white";
                icon.innerHTML = '<i class="fas fa-certificate"></i>';
                title.innerText = 'Verifikasi SKL';
                desc.innerText = 'Scan QR Code yang tertera pada surat SKL fisik Anda untuk memvalidasi keaslian dokumen secara realtime.';
                actionIcon.innerHTML = '<i class="fas fa-shield-alt"></i>';
                actionTitle.innerText = 'Cek Token SKL';
                actionDesc.innerText = 'Masukkan 16 karakter token verifikasi yang tertera di bagian bawah surat SKL fisik Anda.';
                actionInput.placeholder = 'Masukkan Token SKL...';
                btnProcess.innerText = 'Verifikasi SKL';
                document.getElementById('modal-subtitle').innerText = 'Mode: Verifikasi Dokumen';
            }
        }

        function startScanner() {
            document.getElementById('modalScanner').classList.remove('hidden');
            html5QrCode = new Html5Qrcode("reader");
            const config = { fps: 15, qrbox: { width: 250, height: 250 } };

            html5QrCode.start({ facingMode: "environment" }, config, (decodedText) => {
                if (currentTab === 'skl') {
                    if (decodedText.includes('verify.php?token=')) {
                        window.location.href = decodedText;
                    } else {
                        showAbsenAlert('Gagal', 'QR tidak dikenali sebagai token SKL.', 'error');
                        stopScanner();
                    }
                } else {
                    // Presence logic
                    submitPresence(decodedText);
                }
            }).catch(err => {
                console.error(err);
                alert('Akses kamera ditolak atau tidak ditemukan.');
                stopScanner();
            });
        }

        function stopScanner() {
            if (html5QrCode) {
                html5QrCode.stop().then(() => {
                    document.getElementById('modalScanner').classList.add('hidden');
                    html5QrCode = null;
                }).catch(() => {
                    document.getElementById('modalScanner').classList.add('hidden');
                });
            } else {
                document.getElementById('modalScanner').classList.add('hidden');
            }
        }

        function handleManualAction(e) {
            e.preventDefault();
            const val = document.getElementById('action-input').value;
            if (!val) return;

            if (currentTab === 'skl') {
                window.location.href = `verify.php?token=${val}`;
            } else {
                submitPresence(val);
            }
        }

        function getDeviceId() {
            let deviceId = localStorage.getItem('sipanda_device_id');
            if (!deviceId) {
                // Generate simple unique ID
                deviceId = 'DEV-' + Math.random().toString(36).substr(2, 9).toUpperCase() + '-' + Date.now().toString(36).toUpperCase();
                localStorage.setItem('sipanda_device_id', deviceId);
            }
            return deviceId;
        }

        function submitPresence(token) {
            stopScanner();
            const deviceId = getDeviceId();
            const formData = new FormData();
            formData.append('token', token);
            formData.append('device_token', deviceId);

            fetch('api/absen_qr.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'ok') {
                        showAbsenAlert('Berhasil!', data.message, 'success');
                    } else if (data.status === 'unauthorized_device') {
                        // Special handling for unauthorized device
                        Swal.fire({
                            title: 'Otorisasi Diperlukan',
                            html: `<div class="text-center">
                                    <i class="fas fa-shield-alt text-rose-500 text-5xl mb-4"></i>
                                    <p class="text-sm text-slate-600 mb-4">${data.message}</p>
                                    <div class="bg-slate-100 p-3 rounded-lg font-mono text-[10px] break-all border border-slate-200">
                                        ID: <span class="font-bold text-navy-950">${data.token}</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-4">Berikan ID di atas kepada Admin untuk didaftarkan guna mendapatkan akses scanner.</p>
                                   </div>`,
                            confirmButtonText: 'Tutup',
                            confirmButtonColor: '#0a1628'
                        });
                    } else {
                        showAbsenAlert('Gagal Presensi', data.message, 'error');
                    }
                })
                .catch(err => {
                    showAbsenAlert('Error', 'Gagal terhubung ke server.', 'error');
                });
        }

        function showAbsenAlert(title, msg, type) {
            const alert = document.getElementById('absenAlert');
            const alertTitle = document.getElementById('alertTitle');
            const alertMsg = document.getElementById('alertMsg');
            const alertIcon = document.getElementById('alertIcon');
            const alertInner = alert.querySelector('div');

            alertTitle.innerText = title;
            alertMsg.innerText = msg;

            if (type === 'success') {
                alertInner.className = "bg-white rounded-2xl shadow-2xl p-6 border-b-4 border-emerald-500 flex items-center gap-5 min-w-[320px] animate-bounce-in";
                alertIcon.className = "w-12 h-12 rounded-full bg-emerald-500/20 text-emerald-500 flex items-center justify-center text-xl shadow-lg";
                alertIcon.innerHTML = '<i class="fas fa-check"></i>';
            } else {
                alertInner.className = "bg-white rounded-2xl shadow-2xl p-6 border-b-4 border-red-500 flex items-center gap-5 min-w-[320px] animate-bounce-in";
                alertIcon.className = "w-12 h-12 rounded-full bg-red-500/20 text-red-500 flex items-center justify-center text-xl shadow-lg";
                alertIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
            }

            alert.classList.remove('hidden');
            setTimeout(() => {
                alert.classList.add('hidden');
            }, 5000);
        }

        // --- PWA Installation Logic ---
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('service-worker.js').then(function (registration) {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                }, function (err) {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }

        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent the mini-infobar from appearing on mobile
            e.preventDefault();
            // Stash the event so it can be triggered later.
            deferredPrompt = e;
            // Update UI notify the user they can install the PWA
            const installBtn = document.getElementById('btnInstallPwa');
            if (installBtn) {
                installBtn.style.display = 'flex';
                installBtn.style.alignItems = 'center';
                installBtn.style.justifyContent = 'center';

                installBtn.addEventListener('click', async () => {
                    // Show the install prompt
                    deferredPrompt.prompt();
                    // Wait for the user to respond to the prompt
                    const { outcome } = await deferredPrompt.userChoice;
                    if (outcome === 'accepted') {
                        installBtn.style.display = 'none';
                    }
                    deferredPrompt = null;
                });
            }
        });

        window.addEventListener('appinstalled', () => {
            const installBtn = document.getElementById('btnInstallPwa');
            if (installBtn) installBtn.style.display = 'none';
            console.log('SIPANDA App was installed');
        });
    </script>

    <!-- Guru AI Widget -->
    <?php require_once __DIR__ . '/template/guru_ai_widget.php'; ?>

    <!-- Floating WhatsApp Support -->
    <?php if (!empty($setting['wa_tu'])): ?>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $setting['wa_tu']) ?>" target="_blank"
            class="fixed bottom-6 right-6 z-[90] bg-emerald-500 text-white w-14 h-14 rounded-full flex items-center justify-center text-2xl shadow-2xl hover:bg-emerald-600 hover:scale-110 transition-all duration-300 group">
            <i class="fab fa-whatsapp"></i>
            <span
                class="absolute right-full mr-4 bg-navy-950 text-white text-[10px] font-bold px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none shadow-xl border border-white/10 uppercase tracking-widest">Chat
                Tata Usaha</span>
        </a>
    <?php endif; ?>

</body>

</html>