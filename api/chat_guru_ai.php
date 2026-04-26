<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/ai_helper.php';

// Cek koneksi & session sudah di-handle oleh init.php
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$max_tamu_chat = 5;

// Cek fitur AI Chat aktif (hanya untuk user login)
if ($is_logged_in && !fitur_aktif('ai_chat')) {
    echo json_encode(['status' => 'error', 'message' => 'Fitur Guru AI Chatbot tidak tersedia dalam paket lisensi Anda.']);
    exit;
}

// Batasi akses bagi user tamu (belum login)
if (!$is_logged_in) {
    if (!isset($_SESSION['ai_chat_count'])) {
        $_SESSION['ai_chat_count'] = 0;
    }
    if ($_SESSION['ai_chat_count'] >= $max_tamu_chat) {
        echo json_encode([
            'status' => 'limit', 
            'message' => 'Anda telah mencapai batas maksimal obrolan gratis (5 pesan). Silakan Login ke aplikasi untuk mengobrol sepuasnya secara gratis dan membuka fitur belajar lainnya!'
        ]);
        exit;
    }
}

// Baca input JSON dari frontend
$input_raw = file_get_contents('php://input');
$input = json_decode($input_raw, true);

if (!$input || empty($input['message'])) {
    echo json_encode(['status' => 'error', 'message' => 'Pesan tidak boleh kosong.']);
    exit;
}

$message = trim($input['message']);
$history = $input['history'] ?? [];

// Limit jumlah riwayat agar tidak meledak panjang tokennya (maksimal 10 percakapan terakhir)
if (count($history) > 10) {
    $history = array_slice($history, -10);
}

// Susun contents (Format API Gemini: array of contents)
$contents = [];
foreach ($history as $msg) {
    $role = ($msg['role'] === 'model') ? 'model' : 'user';
    $contents[] = ['role' => $role, 'parts' => [['text' => $msg['text']]]];
}
// Tambahkan pesan user saat ini
$contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

// Ambil pengaturan sekolah untuk ditanamkan ke dalam "otak" AI
$q_set = $pdo->query("SELECT * FROM tbl_setting WHERE id=1");
$setting = $q_set->fetch(PDO::FETCH_ASSOC);
$api_key = $setting['gemini_api_key'] ?? '';

if (empty($api_key)) {
    echo json_encode(['status' => 'error', 'message' => 'Oops, Admin sekolah belum mengkonfigurasi API Key untuk AI ini.']);
    exit;
}

// Persiapan Data Sekolah
$nama_sekolah = $setting['nama_sekolah'] ?? 'Sekolah';
$telepon = $setting['telepon'] ?? '-';
$email = $setting['email'] ?? '-';
$alamat = $setting['alamat'] ?? '-';

$system_instruction = <<<SYS
Anda adalah "Guru AI", asisten cerdas dan interaktif yang dikembangkan khusus untuk Sistem Informasi Pendidikan (SIPANDA) milik {$nama_sekolah}.
Karakteristik Anda: Ramah, profesional, pendidik, cerdas tanpa batas kemampuannya, dan menggunakan Bahasa Indonesia baku nan bersahabat (atau menyesuaikan gaya sapa pengguna).

INFORMASI SANGAT PENTING (Info Sekolah):
1. Nama Instansi: {$nama_sekolah}
2. Kontak WA / Tata Usaha (TU): {$telepon} 
3. Email Resmi: {$email}
4. Alamat Lengkap: {$alamat}

ATURAN WAJIB & KEPRIBADIAN:
- Jika pengguna bertanya informasi mengenai PPDB (Penerimaan Peserta Didik Baru), Pendaftaran, Rincian Biaya, SPP, atau hal-hal administratif sekolah lainnya, Anda WAJIB memberikan informasi sekilas yang ramah lalu MENGARAHKAN MEREKA secara tegas namun halus untuk menghubungi nomor WhatsApp Administrasi/TU di {$telepon}. Sertakan nomor tersebut di jawaban Anda.
- Jika pengguna bertanya hal akademis (Pelajaran, Matematika, IPA, Sejarah, Coding, dll), bantu dengan sangat cerdas! Jangan pernah menolak membantu. Anda adalah Super AI yang pintar.
- Jika pengguna membutuhkan "clue" atau ingin memecahkan masalah mandiri, bimbing mereka seperti guru terbaik. Anda diizinkan memberikan penyelesaian dan penalaran tahap demi tahap jika itu membantu pemahaman!
- Buat jawaban Anda serapi mungkin (gunakan paragraf pendek, list/bullet points) agar nyaman dibaca di tampilan widget kecil.
- KONTEN NEGATIF & 18+: Jika pengguna memancing atau bertanya tentang topik dewasa (18+), pornografi, seksualitas, narkoba, atau kekerasan yang tidak terkait langsung dengan pelajaran Biologi/PKn/sekolahan secara ilmiah, Anda HARUS menolaknya secara TEGAS dan SOPAN. Ingatkan pengguna bahwa Anda adalah "Guru AI" sebuah institusi pendidikan yang menjunjung etika dan norma.
SYS;

// Panggil API Gemini
$result = call_gemini_api($contents, $api_key, $system_instruction);

// Jika sukses, tambahkan increment pada sesi tamu
if ($result['status'] === 'success') {
    if (!$is_logged_in) {
        $_SESSION['ai_chat_count']++;
    }
    
    // Kirim sisa kuota ke frontend jika ingin ditampilkan
    $sisa = $is_logged_in ? 'Unlimited' : ($max_tamu_chat - $_SESSION['ai_chat_count']);
    $result['sisa_limit'] = $sisa;
}

echo json_encode($result);
