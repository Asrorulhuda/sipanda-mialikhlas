<?php
// Authentication & Authorization SIPANDA

function generate_remember_token($id, $role, $db_password) {
    // Kombinasi unik yang diamankan via HMAC menggunakan password hash sebagai kunci rahasia
    $token = hash_hmac('sha256', $id . ':' . $role, $db_password);
    $payload = base64_encode($id . ':' . $role . ':' . $token);
    // 30 Hari expired
    setcookie('sipanda_auth', $payload, time() + (86400 * 30), '/');
}

function auto_login_via_cookie($pdo) {
    if (isset($_SESSION['user_id']) || !isset($_COOKIE['sipanda_auth'])) return;

    $payload = @base64_decode($_COOKIE['sipanda_auth']);
    if (!$payload) return;
    
    @list($id, $role, $token) = explode(':', $payload);
    if (!$id || !$role || !$token) return;

    if ($role === 'admin' || $role === 'bendahara' || $role === 'kepsek' || $role === 'kasir' || $role === 'petugas') {
        $stmt = $pdo->prepare("SELECT * FROM tbl_admin WHERE id_admin=? AND status='aktif'");
        $stmt->execute([$id]);
        if ($u = $stmt->fetch()) {
            if ($token === hash_hmac('sha256', $id . ':' . $u['level'], $u['password'])) {
                $_SESSION['user_id'] = $u['id_admin'];
                $_SESSION['username'] = $u['username'];
                $_SESSION['nama'] = $u['nama'];
                $_SESSION['foto'] = $u['foto'];
                $_SESSION['role'] = $u['level'];
            }
        }
    } else if ($role === 'siswa') {
        $stmt = $pdo->prepare("SELECT s.*, k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE s.id_siswa=? AND s.status='Aktif'");
        $stmt->execute([$id]);
        if ($u = $stmt->fetch()) {
            if ($token === hash_hmac('sha256', $id . ':siswa', $u['password'])) {
                $_SESSION['user_id'] = $u['id_siswa'];
                $_SESSION['username'] = $u['username'];
                $_SESSION['nama'] = $u['nama'];
                $_SESSION['foto'] = $u['foto'];
                $_SESSION['role'] = 'siswa';
                $_SESSION['kelas'] = $u['nama_kelas'];
                $_SESSION['id_kelas'] = $u['id_kelas'];
            }
        }
    } else if ($role === 'guru') {
        $stmt = $pdo->prepare("SELECT * FROM tbl_guru WHERE id_guru=? AND status='Aktif'");
        $stmt->execute([$id]);
        if ($u = $stmt->fetch()) {
            if ($token === hash_hmac('sha256', $id . ':guru', $u['password'])) {
                $_SESSION['user_id'] = $u['id_guru'];
                $_SESSION['username'] = $u['username'];
                $_SESSION['nama'] = $u['nama'];
                $_SESSION['foto'] = $u['foto'];
                $_SESSION['role'] = 'guru';
                $_SESSION['tugas_tambahan'] = $u['tugas_tambahan'] ?? '';
            }
        }
    }
}

function cek_login() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function cek_role($allowed = []) {
    cek_login();
    if (empty($allowed)) return;
    if (in_array($_SESSION['role'], $allowed)) return;

    // RBAC Bypass untuk Waka
    if ($_SESSION['role'] === 'guru') {
        $tugas = $_SESSION['tugas_tambahan'] ?? '';
        if ($tugas === 'Waka Kurikulum' && in_array('waka_kurikulum', $allowed)) return;
        if ($tugas === 'Waka Kesiswaan' && in_array('waka_kesiswaan', $allowed)) return;
        if ($tugas === 'Waka Sarpras' && in_array('waka_sarpras', $allowed)) return;
        if ($tugas === 'Waka Humas' && in_array('waka_humas', $allowed)) return;
        if ($tugas === 'Waka Keagamaan' && in_array('waka_keagamaan', $allowed)) return;
        if ($tugas === 'Waka UKS' && in_array('waka_uks', $allowed)) return;
    }

    header('Location: ' . BASE_URL . 'login.php?e=403');
    exit;
}

function login_admin($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_admin WHERE username=? AND status='aktif'");
    $stmt->execute([$username]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password'])) {
        $_SESSION['user_id'] = $u['id_admin'];
        $_SESSION['username'] = $u['username'];
        $_SESSION['nama'] = $u['nama'];
        $_SESSION['foto'] = $u['foto'];
        $_SESSION['role'] = $u['level'];
        generate_remember_token($u['id_admin'], $u['level'], $u['password']);
        return $u['level'];
    }
    return false;
}

function login_siswa($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT s.*, k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE s.username=? AND s.status='Aktif'");
    $stmt->execute([$username]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password'])) {
        $_SESSION['user_id'] = $u['id_siswa'];
        $_SESSION['username'] = $u['username'];
        $_SESSION['nama'] = $u['nama'];
        $_SESSION['foto'] = $u['foto'];
        $_SESSION['role'] = 'siswa';
        $_SESSION['kelas'] = $u['nama_kelas'];
        $_SESSION['id_kelas'] = $u['id_kelas'];
        generate_remember_token($u['id_siswa'], 'siswa', $u['password']);
        return 'siswa';
    }
    return false;
}

function login_guru($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_guru WHERE username=? AND status='Aktif'");
    $stmt->execute([$username]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password'])) {
        $_SESSION['user_id'] = $u['id_guru'];
        $_SESSION['username'] = $u['username'];
        $_SESSION['nama'] = $u['nama'];
        $_SESSION['foto'] = $u['foto'];
        $_SESSION['role'] = 'guru';
        $_SESSION['tugas_tambahan'] = $u['tugas_tambahan'] ?? '';
        generate_remember_token($u['id_guru'], 'guru', $u['password']);
        return 'guru';
    }
    return false;
}

function redirect_role($role) {
    header('Location: ' . BASE_URL . redirect_url_for_role($role));
    exit;
}

function redirect_url_for_role($role) {
    $map = [
        'admin' => 'index-admin.php',
        'bendahara' => 'index-bendahara.php',
        'kepsek' => 'index-kepsek.php',
        'guru' => 'index-guru.php',
        'siswa' => 'index-siswa.php',
        'kasir' => 'index-kasir.php',
        'petugas' => 'index-petugas.php',
    ];
    return $map[$role] ?? 'login.php';
}

/**
 * Login via WebAuthn (tanpa password) — dipanggil setelah biometric terverifikasi
 */
function login_via_webauthn($pdo, $user_id, $role) {
    if (in_array($role, ['admin','bendahara','kepsek','kasir','petugas'])) {
        $stmt = $pdo->prepare("SELECT * FROM tbl_admin WHERE id_admin=? AND status='aktif'");
        $stmt->execute([$user_id]);
        $u = $stmt->fetch();
        if ($u) {
            $_SESSION['user_id']  = $u['id_admin'];
            $_SESSION['username'] = $u['username'];
            $_SESSION['nama']     = $u['nama'];
            $_SESSION['foto']     = $u['foto'];
            $_SESSION['role']     = $u['level'];
            generate_remember_token($u['id_admin'], $u['level'], $u['password']);
            return true;
        }
    } elseif ($role === 'siswa') {
        $stmt = $pdo->prepare("SELECT s.*, k.nama_kelas FROM tbl_siswa s LEFT JOIN tbl_kelas k ON s.id_kelas=k.id_kelas WHERE s.id_siswa=? AND s.status='Aktif'");
        $stmt->execute([$user_id]);
        $u = $stmt->fetch();
        if ($u) {
            $_SESSION['user_id']  = $u['id_siswa'];
            $_SESSION['username'] = $u['username'];
            $_SESSION['nama']     = $u['nama'];
            $_SESSION['foto']     = $u['foto'];
            $_SESSION['role']     = 'siswa';
            $_SESSION['kelas']    = $u['nama_kelas'];
            $_SESSION['id_kelas'] = $u['id_kelas'];
            generate_remember_token($u['id_siswa'], 'siswa', $u['password']);
            return true;
        }
    } elseif ($role === 'guru') {
        $stmt = $pdo->prepare("SELECT * FROM tbl_guru WHERE id_guru=? AND status='Aktif'");
        $stmt->execute([$user_id]);
        $u = $stmt->fetch();
        if ($u) {
            $_SESSION['user_id']  = $u['id_guru'];
            $_SESSION['username'] = $u['username'];
            $_SESSION['nama']     = $u['nama'];
            $_SESSION['foto']     = $u['foto'];
            $_SESSION['role']     = 'guru';
            $_SESSION['tugas_tambahan'] = $u['tugas_tambahan'] ?? '';
            generate_remember_token($u['id_guru'], 'guru', $u['password']);
            return true;
        }
    }
    return false;
}
