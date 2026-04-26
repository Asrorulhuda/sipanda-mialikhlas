<?php
session_start();
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/config/fungsi.php';
require_once __DIR__ . '/config/auth.php';

// Already logged in?
if (isset($_SESSION['role'])) redirect_role($_SESSION['role']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    $r = login_admin($pdo, $u, $p) ?: login_siswa($pdo, $u, $p) ?: login_guru($pdo, $u, $p);
    if ($r) redirect_role($r);
    else $error = 'Username atau password salah!';
}
if (isset($_GET['e']) && $_GET['e'] == '403') $error = 'Akses ditolak!';
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SIPANDA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(30,41,59,.6); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,.1); }
        @keyframes float { 0%,100% { transform: translateY(0px); } 50% { transform: translateY(-20px); } }
        .float-anim { animation: float 6s ease-in-out infinite; }
        .float-anim2 { animation: float 8s ease-in-out infinite 1s; }
    </style>
</head>
<body class="bg-slate-950 min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Animated Background -->
    <div class="absolute inset-0">
        <div class="absolute top-20 left-10 w-72 h-72 bg-blue-600/20 rounded-full blur-3xl float-anim"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-600/15 rounded-full blur-3xl float-anim2"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-emerald-600/10 rounded-full blur-3xl"></div>
    </div>

    <div class="glass rounded-2xl w-full max-w-md p-8 relative z-10 shadow-2xl shadow-black/40">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 flex items-center justify-center text-white text-3xl font-bold shadow-xl shadow-blue-500/30 mb-4">S</div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">SIPANDA</h1>
            <p class="text-slate-400 text-sm mt-1">Sistem Informasi Pokok Pendidikan</p>
            <p class="text-slate-500 text-xs mt-0.5"><?= clean($setting['nama_sekolah'] ?? '') ?></p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i> <?= clean($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-sm text-slate-400 mb-2 font-medium">Username</label>
                <div class="relative">
                    <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                    <input type="text" name="username" required autofocus value="<?= clean($_POST['username'] ?? '') ?>"
                        class="w-full bg-slate-800/50 border border-white/10 rounded-xl py-3 pl-11 pr-4 text-slate-200 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all placeholder-slate-600"
                        placeholder="Masukkan username">
                </div>
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-2 font-medium">Password</label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                    <input type="password" name="password" id="password" required
                        class="w-full bg-slate-800/50 border border-white/10 rounded-xl py-3 pl-11 pr-11 text-slate-200 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all placeholder-slate-600"
                        placeholder="Masukkan password">
                    <button type="button" onclick="togglePw()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300"><i class="fas fa-eye" id="eyeIcon"></i></button>
                </div>
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white font-semibold py-3 rounded-xl transition-all transform hover:scale-[1.02] active:scale-95 shadow-lg shadow-blue-600/30">
                <i class="fas fa-sign-in-alt mr-2"></i>Masuk
            </button>

            <!-- Tombol Sidik Jari (Tersembunyi by default via JS jika WebAuthn tidak didukung) -->
            <button type="button" id="btnBiometric" onclick="loginBiometric()" class="w-full bg-slate-800/80 hover:bg-slate-700 text-slate-300 font-semibold py-3 rounded-xl transition-all transform hover:scale-[1.02] active:scale-95 border border-white/5 flex items-center justify-center gap-2">
                <i class="fas fa-fingerprint text-blue-400 text-lg"></i>
                <span>Masuk dengan Sidik Jari</span>
            </button>
        </form>

        <p class="text-center text-slate-600 text-xs mt-6">&copy; <?= date('Y') ?> SIPANDA <?= APP_VERSION ?></p>
    </div>

    <script>
    function togglePw() {
        const p = document.getElementById('password'), i = document.getElementById('eyeIcon');
        p.type = p.type === 'password' ? 'text' : 'password';
        i.className = p.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    }

    // ─── WEBAUTHN (SIDIK JARI) LOGIC ───
    const btnBiometric = document.getElementById('btnBiometric');
    const inputUsername = document.querySelector('input[name="username"]');
    let checkTimeout;

    // Helper: Decode base64url to ArrayBuffer
    function bufferDecode(value) {
        return Uint8Array.from(atob(value.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));
    }

    // Helper: Encode ArrayBuffer to base64url
    function bufferEncode(value) {
        return btoa(String.fromCharCode.apply(null, new Uint8Array(value)))
            .replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }

    // Cek ketersediaan WebAuthn
    const isWebAuthnSupported = window.PublicKeyCredential !== undefined;

    // Sembunyikan tombol jika browser/device tidak support WebAuthn
    if (!isWebAuthnSupported) {
        btnBiometric.style.display = 'none';
    }

    // Proses login dengan sidik jari
    async function loginBiometric() {
        const username = inputUsername.value.trim();
        if (!username) {
            Swal.fire({icon: 'warning', title: 'Oops...', text: 'Masukkan username Anda terlebih dahulu di kolom form di atas.'});
            inputUsername.focus();
            return;
        }

        try {
            // 1. Minta challenge dari server
            const formData = new FormData();
            formData.append('action', 'login_options');
            formData.append('username', username);

            const optRes = await fetch('api/webauthn.php', { method: 'POST', body: formData });
            const options = await optRes.json();

            if (options.status !== 'ok') {
                throw new Error(options.message);
            }

            // Siapkan opsi untuk navigator.credentials.get
            const getOptions = {
                publicKey: {
                    challenge: bufferDecode(options.challenge),
                    allowCredentials: options.allowCredentials.map(c => ({
                        type: c.type,
                        id: bufferDecode(c.id),
                        transports: c.transports
                    })),
                    timeout: options.timeout,
                    userVerification: options.userVerification
                }
            };

            // 2. Minta user scan sidik jari
            const assertion = await navigator.credentials.get(getOptions);

            // 3. Kirim hasil scan ke server
            const authData = {
                id: assertion.id,
                response: {
                    clientDataJSON: bufferEncode(assertion.response.clientDataJSON),
                    authenticatorData: bufferEncode(assertion.response.authenticatorData),
                    signature: bufferEncode(assertion.response.signature),
                    userHandle: assertion.response.userHandle ? bufferEncode(assertion.response.userHandle) : null
                }
            };

            const verifyRes = await fetch('api/webauthn.php?action=login_verify', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(authData)
            });

            const verifyResult = await verifyRes.json();

            if (verifyResult.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Login Berhasil!',
                    text: `Selamat datang, ${verifyResult.nama}`,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.href = verifyResult.redirect;
                });
            } else {
                throw new Error(verifyResult.message);
            }

        } catch (e) {
            console.error('Biometric login failed:', e);
            let msg = e.message;
            if (e.name === 'NotAllowedError') msg = 'Permintaan dibatalkan atau sidik jari tidak valid.';
            Swal.fire({icon: 'error', title: 'Login Gagal', text: msg});
        }
    }
    </script>
</body>
</html>
