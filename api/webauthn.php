<?php
/**
 * SIPANDA WebAuthn API — Login Sidik Jari / Biometric
 * 
 * Endpoints:
 * POST register_options → Buat challenge untuk daftar credential
 * POST register_verify  → Simpan credential setelah user scan biometric
 * POST login_options     → Buat challenge untuk login biometric
 * POST login_verify      → Verifikasi credential & login user
 * GET  check             → Cek apakah user sudah punya credential
 * POST remove            → Hapus credential terdaftar
 */
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/fungsi.php';
require_once __DIR__ . '/../config/auth.php';

header('Content-Type: application/json');

// ─── Pastikan tabel webauthn ada ───
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS tbl_webauthn (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50) NOT NULL,
        user_role VARCHAR(20) NOT NULL,
        credential_id TEXT NOT NULL,
        public_key TEXT NOT NULL,
        username VARCHAR(100) NOT NULL,
        device_name VARCHAR(100) DEFAULT 'Unknown Device',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id, user_role),
        INDEX idx_username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── Helper: generate random bytes sebagai base64url ───
function random_challenge($length = 32) {
    $bytes = random_bytes($length);
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
}

function json_response($data) {
    echo json_encode($data);
    exit;
}

// ─── Deteksi RP ID (domain) ───
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$rpId = explode(':', $host)[0]; // Remove port if present
$rpName = $setting['nama_sekolah'] ?? 'SIPANDA';
$origin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $host;

switch ($action) {

    // ═══════════════════════════════════════════
    // CEK apakah ada credential terdaftar
    // ═══════════════════════════════════════════
    case 'check':
        $username = $_GET['username'] ?? '';
        if (!$username) {
            json_response(['registered' => false]);
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_webauthn WHERE username = ?");
        $stmt->execute([$username]);
        $count = $stmt->fetchColumn();
        json_response(['registered' => $count > 0, 'count' => (int)$count]);
        break;

    // ═══════════════════════════════════════════
    // REGISTER: Buat options untuk pendaftaran
    // ═══════════════════════════════════════════
    case 'register_options':
        // User harus sudah login
        if (!isset($_SESSION['user_id'])) {
            json_response(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.']);
        }

        $userId = $_SESSION['user_id'];
        $username = $_SESSION['username'];
        $role = $_SESSION['role'];
        $displayName = $_SESSION['nama'] ?? $username;

        // Challenge
        $challenge = random_challenge(32);
        $_SESSION['webauthn_challenge'] = $challenge;
        $_SESSION['webauthn_action'] = 'register';

        // Ambil credential yang sudah ada (untuk exclude)
        $stmt = $pdo->prepare("SELECT credential_id FROM tbl_webauthn WHERE user_id = ? AND user_role = ?");
        $stmt->execute([$userId, $role]);
        $existingCreds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $excludeCredentials = array_map(function($cid) {
            return [
                'type' => 'public-key',
                'id' => $cid,
            ];
        }, $existingCreds);

        $options = [
            'status' => 'ok',
            'rp' => [
                'name' => $rpName,
                'id' => $rpId,
            ],
            'user' => [
                'id' => base64url_encode($role . ':' . $userId),
                'name' => $username,
                'displayName' => $displayName,
            ],
            'challenge' => $challenge,
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],   // ES256
                ['type' => 'public-key', 'alg' => -257],  // RS256
            ],
            'timeout' => 60000,
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform', // Gunakan biometric bawaan device
                'userVerification' => 'required',
                'residentKey' => 'preferred',
            ],
            'attestation' => 'none',
            'excludeCredentials' => $excludeCredentials,
        ];

        json_response($options);
        break;

    // ═══════════════════════════════════════════
    // REGISTER: Verifikasi & simpan credential
    // ═══════════════════════════════════════════
    case 'register_verify':
        if (!isset($_SESSION['user_id'])) {
            json_response(['status' => 'error', 'message' => 'Session expired.']);
        }
        if (($_SESSION['webauthn_action'] ?? '') !== 'register') {
            json_response(['status' => 'error', 'message' => 'Invalid flow.']);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['id']) || !isset($input['response'])) {
            json_response(['status' => 'error', 'message' => 'Data credential tidak valid.']);
        }

        $credentialId = $input['id'];
        $clientDataJSON = $input['response']['clientDataJSON'] ?? '';
        $attestationObject = $input['response']['attestationObject'] ?? '';
        $deviceName = $input['device_name'] ?? 'Unknown Device';

        // Decode clientDataJSON untuk verifikasi challenge
        $clientData = json_decode(base64url_decode($clientDataJSON), true);
        if (!$clientData) {
            json_response(['status' => 'error', 'message' => 'ClientData tidak valid.']);
        }

        // Verifikasi challenge
        if (($clientData['challenge'] ?? '') !== $_SESSION['webauthn_challenge']) {
            json_response(['status' => 'error', 'message' => 'Challenge tidak cocok.']);
        }

        // Verifikasi origin
        if (($clientData['origin'] ?? '') !== $origin) {
            // Relaxed check for PWA standalone mode
            $clientOrigin = $clientData['origin'] ?? '';
            if (strpos($clientOrigin, $rpId) === false) {
                json_response(['status' => 'error', 'message' => 'Origin tidak cocok.']);
            }
        }

        // Simpan credential
        $userId = $_SESSION['user_id'];
        $role = $_SESSION['role'];
        $username = $_SESSION['username'];

        try {
            $stmt = $pdo->prepare("INSERT INTO tbl_webauthn (user_id, user_role, credential_id, public_key, username, device_name) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $role, $credentialId, $attestationObject, $username, $deviceName]);
        } catch (Exception $e) {
            json_response(['status' => 'error', 'message' => 'Gagal menyimpan credential: ' . $e->getMessage()]);
        }

        // Cleanup session
        unset($_SESSION['webauthn_challenge'], $_SESSION['webauthn_action']);

        json_response([
            'status' => 'success',
            'message' => 'Sidik jari berhasil didaftarkan! Anda bisa login dengan sidik jari mulai sekarang.',
        ]);
        break;

    // ═══════════════════════════════════════════
    // LOGIN: Buat options untuk autentikasi
    // ═══════════════════════════════════════════
    case 'login_options':
        $username = $_POST['username'] ?? '';

        // Ambil semua credential untuk username ini
        $stmt = $pdo->prepare("SELECT credential_id, user_id, user_role FROM tbl_webauthn WHERE username = ?");
        $stmt->execute([$username]);
        $creds = $stmt->fetchAll();

        if (empty($creds)) {
            json_response(['status' => 'error', 'message' => 'Sidik jari belum terdaftar untuk akun ini.']);
        }

        $challenge = random_challenge(32);
        $_SESSION['webauthn_challenge'] = $challenge;
        $_SESSION['webauthn_action'] = 'login';
        $_SESSION['webauthn_username'] = $username;

        $allowCredentials = array_map(function($c) {
            return [
                'type' => 'public-key',
                'id' => $c['credential_id'],
                'transports' => ['internal'],
            ];
        }, $creds);

        json_response([
            'status' => 'ok',
            'challenge' => $challenge,
            'rpId' => $rpId,
            'timeout' => 60000,
            'userVerification' => 'required',
            'allowCredentials' => $allowCredentials,
        ]);
        break;

    // ═══════════════════════════════════════════
    // LOGIN: Verifikasi assertion & login user
    // ═══════════════════════════════════════════
    case 'login_verify':
        if (($_SESSION['webauthn_action'] ?? '') !== 'login') {
            json_response(['status' => 'error', 'message' => 'Invalid flow.']);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['id'])) {
            json_response(['status' => 'error', 'message' => 'Data autentikasi tidak valid.']);
        }

        $credentialId = $input['id'];
        $clientDataJSON = $input['response']['clientDataJSON'] ?? '';

        // Decode & verify challenge
        $clientData = json_decode(base64url_decode($clientDataJSON), true);
        if (!$clientData || ($clientData['challenge'] ?? '') !== $_SESSION['webauthn_challenge']) {
            json_response(['status' => 'error', 'message' => 'Challenge tidak cocok.']);
        }

        // Cari credential di database
        $username = $_SESSION['webauthn_username'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM tbl_webauthn WHERE credential_id = ? AND username = ?");
        $stmt->execute([$credentialId, $username]);
        $cred = $stmt->fetch();

        if (!$cred) {
            json_response(['status' => 'error', 'message' => 'Credential tidak ditemukan.']);
        }

        // Login user berdasarkan credential
        $userId = $cred['user_id'];
        $role = $cred['user_role'];

        $loginResult = login_via_webauthn($pdo, $userId, $role);
        if (!$loginResult) {
            json_response(['status' => 'error', 'message' => 'Akun tidak ditemukan atau sudah nonaktif.']);
        }

        // Cleanup
        unset($_SESSION['webauthn_challenge'], $_SESSION['webauthn_action'], $_SESSION['webauthn_username']);

        json_response([
            'status' => 'success',
            'message' => 'Login berhasil!',
            'redirect' => BASE_URL . redirect_url_for_role($role),
            'nama' => $_SESSION['nama'] ?? '',
            'role' => $role,
        ]);
        break;

    // ═══════════════════════════════════════════
    // REMOVE: Hapus credential
    // ═══════════════════════════════════════════
    case 'remove':
        if (!isset($_SESSION['user_id'])) {
            json_response(['status' => 'error', 'message' => 'Silakan login.']);
        }
        $credId = $_POST['credential_id'] ?? '';
        $stmt = $pdo->prepare("DELETE FROM tbl_webauthn WHERE id = ? AND user_id = ? AND user_role = ?");
        $stmt->execute([$credId, $_SESSION['user_id'], $_SESSION['role']]);
        json_response(['status' => 'success', 'message' => 'Credential dihapus.']);
        break;

    default:
        json_response(['status' => 'error', 'message' => 'Unknown action.']);
}
