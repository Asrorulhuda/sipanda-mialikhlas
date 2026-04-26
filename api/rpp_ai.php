<?php
header('Content-Type: application/json; charset=utf-8');
// Matikan tampilan error ke layar (HTML) agar tidak merusak format JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/ai_helper.php';
cek_role(['guru']);

// Cek fitur AI RPP aktif
if (!fitur_aktif('ai_rpp')) {
    echo json_encode(['status' => 'error', 'message' => 'Fitur AI RPP Generator tidak tersedia dalam paket lisensi Anda.']);
    exit;
}

// ── Ambil API Key ────────────────────────────────────────────────────────────
$q_set = $pdo->query("SELECT gemini_api_key FROM tbl_setting WHERE id=1");
$api_key = $q_set->fetchColumn();

if (!$api_key) {
    echo json_encode(['status' => 'error', 'message' => 'Lengkapi Gemini API Key di Pengaturan Administrasi terlebih dahulu.']);
    exit;
}

// ── Input ────────────────────────────────────────────────────────────────────
$action = $_POST['action'] ?? '';
$type = $_POST['type'] ?? 'Kurmer';
$mapel = trim($_POST['mapel'] ?? '');
$kelas = trim($_POST['kelas'] ?? '');
$topic = trim($_POST['topic'] ?? '');
$cp = trim($_POST['cp'] ?? '');
$dpl_manual = trim($_POST['dpl'] ?? '');
$pc_manual = trim($_POST['panca_cinta'] ?? '');
$pedagogis_manual = trim($_POST['pedagogis'] ?? '');
$semester = trim($_POST['semester'] ?? 'Ganjil');
$jp = max(1, (int) ($_POST['alokasi_jp'] ?? 1));
$menit = max(1, (int) ($_POST['menit_per_jp'] ?? 35));
$total_m = $jp * $menit;

$is_json_action = in_array($action, ['magic_fill', 'full_generate'], true);

if (!$topic) {
    echo json_encode(['status' => 'error', 'message' => 'Topik/Materi pokok harus diisi agar AI bisa bekerja.']);
    exit;
}

// ── Deteksi Fase & Jenjang Otomatis ──────────────────────────────────────────
$fase = '-';
$jenjang = 'SD'; // default
preg_match('/\d+/', $kelas, $m_num);
$k_num = isset($m_num[0]) ? (int) $m_num[0] : 0;
$fase_map = [1 => 'A', 2 => 'A', 3 => 'B', 4 => 'B', 5 => 'C', 6 => 'C', 7 => 'D', 8 => 'D', 9 => 'D', 10 => 'E', 11 => 'F', 12 => 'F'];
if (isset($fase_map[$k_num])) $fase = $fase_map[$k_num];
if ($k_num >= 7 && $k_num <= 9) $jenjang = 'SMP';
elseif ($k_num >= 10) $jenjang = 'SMA';

$pedagogis_instruction = $pedagogis_manual
    ? "PRAKTIK PEDAGOGIS YANG DIPILIH GURU:\n{$pedagogis_manual}\nATURAN: Guru telah memilih praktik pedagogis tersebut. Jelaskan BAGAIMANA praktik tersebut diterapkan dalam langkah pembelajaran ini."
    : "DAFTAR REFERENSI PRAKTIK PEDAGOGIS...\nATURAN: Pilih 1-2 praktik yang sesuai dengan jenjang.";

$dpl_instruction = $dpl_manual
    ? "DIMENSI PROFIL LULUSAN (DPL / P3) YANG DIPILIH GURU:\n{$dpl_manual}\nATURAN: Guru telah memilih dimensi tersebut. Jelaskan korelasi spesifik masing-masing dimensi yang dipilih dengan topik/materi."
    : "DAFTAR DIMENSI PROFIL LULUSAN...\nATURAN: Pilih MAKSIMAL 3 dimensi. Sebutkan nama dimensinya dan jelaskan korelasi spesifik dengan topik/materi.";

$pc_instruction = $pc_manual
    ? "NILAI PANCA CINTA YANG DIPILIH GURU:\n{$pc_manual}\nATURAN: Guru telah memilih nilai tersebut. Desain pembelajaran harus mengintegrasikan HANYA nilai yang dipilih tersebut secara bermakna."
    : "DAFTAR PANCA CINTA...\nATURAN: Pilih 1-3 nilai Panca Cinta. Desain pembelajaran harus mengintegrasikan HANYA nilai yang dipilih secara bermakna.";

// ── System Instruction — berlaku untuk semua prompt ──────────────────────────
$system_instruction = <<<SYS
Anda adalah Ahli Kurikulum Senior Indonesia dengan pengalaman lebih dari 20 tahun dalam menyusun perangkat pembelajaran.
Anda menguasai Kurikulum Merdeka (Permendikbudristek No.12/2024) dan Kurikulum Berbasis Cinta (KBC) berbasis Deep Learning model PEDATTI.
Tugas Anda: menyusun RPP yang profesional, mendalam, kontekstual, dan siap pakai.

ATURAN WAJIB:
- JANGAN gunakan format Markdown (DILARANG KERAS menggunakan tanda bintang ** untuk bold).
- JANGAN mengulangi nama bagian/judul di dalam isi narasi.
- Gunakan penomoran biasa (1., 2., 3.) atau strip (-) untuk daftar.
- Gunakan bahasa Indonesia formal dan baku.
- Berikan langkah pembelajaran yang KONKRET, DETAIL, dan OPERASIONAL — bukan sekadar deskripsi umum.
- Setiap kegiatan harus menyebut aktivitas SISWA dan peran GURU secara eksplisit.
- Tujuan Pembelajaran wajib menggunakan format: "Melalui [aktivitas], peserta didik dapat [kompetensi] dengan [sikap/nilai]."
- Integrasikan roh Deep Learning: pastikan pengalaman belajar dikemas agar Joyful (menyenangkan), Meaningful (bermakna), dan Mindful (berpusat pada kesadaran).

═══════════════════════════════════════════════
{$pedagogis_instruction}

═══════════════════════════════════════════════
{$dpl_instruction}

═══════════════════════════════════════════════
{$pc_instruction}
SYS;

// ── Konteks dasar ─────────────────────────────────────────────────────────────
$model_manual = trim($_POST['model_pembelajaran'] ?? '');
$model_info = $model_manual ? " | Model Pembelajaran: {$model_manual}" : "";
$ctx = "Mapel: {$mapel} | Kelas: {$kelas} | Fase: {$fase} | Jenjang: {$jenjang} | Semester: {$semester} | Topik: {$topic} | Durasi: {$jp} JP x {$menit} menit (Total: {$total_m} menit){$model_info}";

// CP adalah input manual guru — WAJIB dijadikan patokan utama
$cp_block = $cp
    ? "\n\nCAPAIAN PEMBELAJARAN (CP) — PATOKAN UTAMA (ditulis oleh guru, JANGAN diubah):\n{$cp}\n\nGunakan CP di atas sebagai PATOKAN UTAMA untuk menyusun Tujuan Pembelajaran (TP) dan seluruh komponen RPP. TP harus diturunkan langsung dari CP ini."
    : "\n\nCP: Guru belum mengisi CP. Rumuskan TP berdasarkan konteks mapel, kelas, dan topik.";

// ── Routing Prompt ────────────────────────────────────────────────────────────
$pedagogis_prompt = $pedagogis_manual
    ? "Jelaskan BAGAIMANA Praktik Pedagogis yang dipilih guru ({$pedagogis_manual}) diterapkan."
    : "PILIH 1-2 praktik pedagogis dari DAFTAR REFERENSI di system instruction yang paling cocok untuk jenjang {$jenjang} dan topik ini. Format: sebutkan nama praktik, lalu jelaskan MENGAPA dipilih dan BAGAIMANA diterapkan.";

$dpl_prompt = $dpl_manual
    ? "Jelaskan HANYA korelasi spesifik untuk dimensi (DPL) yang telah dipilih guru dalam konteks materi dan jenjang {$jenjang}."
    : "PILIH MAKSIMAL 3 dimensi dari DAFTAR DPL di system instruction. Format: nama dimensi dan korelasi spesifik dengan topik.";

$pc_prompt = $pc_manual
    ? "Rancang susunan aktivitas yang mengintegrasikan HANYA nilai Panca Cinta yang telah dipilih guru secara bermakna."
    : "PILIH 1-3 nilai Panca Cinta dari DAFTAR PANCA CINTA di system instruction. Rancang aktivitas yang mengintegrasikan nilainya.";

if ($is_json_action) {

    if ($type === 'KBC') {
        $prompt = <<<PROMPT
KONTEKS: {$ctx}{$cp_block}

TUGAS: Buat RPP Kurikulum Berbasis Cinta (KBC) — Deep Learning model PEDATTI yang SANGAT DETAIL.
Semua komponen harus diturunkan dari CP yang diberikan guru sebagai patokan utama.
Jenjang sekolah: {$jenjang} — praktik pedagogis disesuaikan dengan jenjang ini.

KEMBALIKAN HANYA JSON VALID (tanpa teks lain, tanpa markdown, tanpa komentar):
{
  "praktik_pedagogis": "{$pedagogis_prompt}",
  "dimensi_dpl": "{$dpl_prompt}",
  "tujuan": "Minimal 3 tujuan pembelajaran yang LANGSUNG DITURUNKAN DARI CP. Format: Melalui [aktivitas], peserta didik dapat [kompetensi] dengan [nilai karakter].",
  "kesiapan": "Identifikasi Kesiapan Murid — cara konkret guru memetakan dan memahami kebutuhan/kondisi awal murid secara mindful. Minimal 4 langkah.",
  "pendahuluan": "Kegiatan pembuka KBC LENGKAP: salam hangat, doa bersama, presensi, apersepsi yang membangkitkan nilai Panca Cinta terpilih, menyampaikan tujuan. Minimal 5 langkah.",
  "dalami": "Tahap Dalami (Inti 1) — siswa mengeksplorasi secara joyful dan mindful. Langkah operasional guru & siswa. Minimal 4 langkah.",
  "terapkan": "Tahap Terapkan (Inti 2) — siswa mengaplikasikan pemahaman secara meaningful. Langkah operasional guru & siswa. Minimal 4 langkah.",
  "tularkan": "Tahap Tularkan (Inti 3) — refleksi berbagi hasil karya secara joyful. Minimal 3 langkah operasional.",
  "penutup": "Kegiatan penutup KBC LENGKAP: refleksi pembelajaran bermakna, penguatan nilai Panca Cinta terpilih, penyampaian materi berikutnya, doa penutup. Minimal 4 langkah.",
  "asesmen_awal": "Asesmen Diagnostik tentang kesiapan atau pemahaman prasyarat",
  "asesmen_proses": "Asesmen Formatif (evaluasi kognitif & karakter di proses dalami/terapkan/tularkan)",
  "asesmen_akhir": "Asesmen Sumatif (tes uji akhir, rubrik keberhasilan karakter dan kognitif)",
  "waktu_pendahuluan": "(estimasi dalam menit, contoh: 10 menit)",
  "waktu_inti": "(estimasi total menit untuk dalami+terapkan+tularkan)",
  "waktu_penutup": "(estimasi dalam menit, contoh: 10 menit)"
}
PROMPT;

    } else { // Kurmer
        $prompt = <<<PROMPT
KONTEKS: {$ctx}{$cp_block}

TUGAS: Buat RPP Kurikulum Merdeka yang SANGAT DETAIL dan PROFESIONAL.
Semua komponen harus diturunkan dari CP yang diberikan guru sebagai patokan utama.
Jenjang sekolah: {$jenjang} — praktik pedagogis disesuaikan dengan jenjang ini.

KEMBALIKAN HANYA JSON VALID (tanpa teks lain, tanpa markdown, tanpa komentar):
{
  "praktik_pedagogis": "{$pedagogis_prompt}",
  "dimensi_dpl": "{$dpl_prompt}",
  "tujuan": "Minimal 3 tujuan pembelajaran yang LANGSUNG DITURUNKAN DARI CP. Format: Melalui [aktivitas], peserta didik dapat [kompetensi] dengan [nilai karakter]. Selaraskan dengan CP.",
  "pendahuluan": "Kegiatan pembuka LENGKAP dan RINCI: salam, doa, presensi, apersepsi (hubungkan dengan pengetahuan awal siswa), motivasi, pertanyaan pemantik, dan penyampaian tujuan. Minimal 6 langkah.",
  "inti": "Kegiatan inti mengacu model_pembelajaran secara LENGKAP dan DETAIL. Sebutkan SETIAP fase/sintaks model beserta: instruksi guru, aktivitas siswa, media yang digunakan, dan pertanyaan pengarah. Minimal 8-10 langkah operasional.",
  "penutup": "Kegiatan penutup LENGKAP: refleksi bersama (pertanyaan refleksi konkret), rangkuman materi, umpan balik, penugasan/PR jika ada, penyampaian materi berikutnya, doa penutup. Minimal 5 langkah.",
  "penilaian": "Penilaian KOMPREHENSIF: (1) Penilaian Sikap — teknik observasi, indikator, rubrik. (2) Penilaian Pengetahuan — teknik tes, bentuk soal, contoh 2 soal. (3) Penilaian Keterampilan — teknik praktik/proyek, rubrik penilaian.",
  "waktu_pendahuluan": "(estimasi dalam menit, contoh: 10 menit)",
  "waktu_inti": "(estimasi dalam menit, contoh: 50 menit)",
  "waktu_penutup": "(estimasi dalam menit, contoh: 10 menit)"
}
PROMPT;
    }

} else {
    // ── Single section regenerate ────────────────────────────────────────────
    $section = trim($_POST['section'] ?? '');
    if (!$section) {
        echo json_encode(['status' => 'error', 'message' => 'Parameter section tidak ditemukan.']);
        exit;
    }

    $section_labels = [
        'praktik_pedagogis' => "{$pedagogis_prompt}",
        'dimensi_dpl' => "{$dpl_prompt}",
        'tujuan' => 'Tujuan Pembelajaran (format: Melalui [aktivitas], peserta didik dapat [kompetensi]). HARUS diturunkan dari CP.',
        'pendahuluan' => 'Kegiatan Pendahuluan (apersepsi, motivasi, pertanyaan pemantik, penyampaian tujuan). Minimal 5-6 langkah.',
        'inti' => "Kegiatan Inti detail per fase/sintaks model pembelajaran. Minimal 8-10 langkah.",
        'penutup' => 'Kegiatan Penutup (refleksi, rangkuman, umpan balik, penugasan, doa). Minimal 4-5 langkah.',
        'penilaian' => 'Penilaian Lengkap (sikap, pengetahuan, keterampilan dengan rubrik)',
        'kesiapan' => 'Identifikasi Kesiapan Murid (metode pemetaan awal pra-pembelajaran secara mindful)',
        'pedatti_dalami' => 'Tahap Dalami PEDATTI (eksplorasi mendalam secara Joyful, langkah demi langkah operasional)',
        'pedatti_terapkan' => 'Tahap Terapkan PEDATTI (aplikasi bermakna/Meaningful, langkah demi langkah operasional)',
        'pedatti_tularkan' => 'Tahap Tularkan PEDATTI (berbagi dan refleksi nilai, langkah demi langkah operasional)',
        'asesmen_awal' => 'Asesmen Awal / Diagnostik (pertanyaan pemantik dan teknik)',
        'asesmen_proses' => 'Asesmen Proses / Formatif (indikator dan instrumen)',
        'asesmen_akhir' => 'Asesmen Akhir / Sumatif (soal/tugas dan rubrik)',
    ];

    $section_desc = $section_labels[$section] ?? $section;

    $prompt = <<<PROMPT
KONTEKS: {$ctx}{$cp_block}
Tipe RPP: {$type} | Jenjang: {$jenjang}

TUGAS: Susun ulang konten untuk bagian "{$section_desc}" secara SANGAT DETAIL dan PROFESIONAL.
Gunakan CP sebagai patokan utama jika relevan.
Gunakan DAFTAR REFERENSI dari system instruction jika bagian ini terkait praktik pedagogis, DPL, atau Panca Cinta.

KETENTUAN:
- Langkah-langkah harus KONKRET dan OPERASIONAL (bukan deskripsi umum).
- Sebutkan aktivitas SISWA dan peran GURU secara eksplisit di setiap langkah.
- Jangan gunakan format Markdown atau tanda bintang (**).
- Gunakan penomoran biasa (1., 2., 3.) atau strip (-).
- Kembalikan HANYA isi narasinya saja, tanpa judul bagian di awal.
PROMPT;
}

// ── Panggil API ───────────────────────────────────────────────────────────────
$result = call_gemini_api($prompt, $api_key, $system_instruction);

if ($result['status'] !== 'success') {
    ob_end_clean();
    echo json_encode($result);
    exit;
}

$ai_text = $result['data'];

// ── Proses Response ───────────────────────────────────────────────────────────
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if ($is_json_action) {
    // Bersihkan formatter markdown jika AI masih bandel
    $clean_text = preg_replace('/^```(?:json)?\s*/i', '', trim($ai_text));
    $clean_text = preg_replace('/```\s*$/i', '', trim($clean_text));
    $clean_text = trim($clean_text);

    $start = strpos($clean_text, '{');
    $end = strrpos($clean_text, '}');
    $json_data = null;

    if ($start !== false && $end !== false && $end > $start) {
        $json_str = substr($clean_text, $start, $end - $start + 1);
        $json_data = json_decode($json_str, true);
    }

    if ($json_data) {
        $response = ['status' => 'success', 'data' => $json_data];
        if (!empty($result['warning']))
            $response['warning'] = $result['warning'];
        echo json_encode($response);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal memproses respons JSON dari AI. Silakan coba lagi.',
            'raw' => substr($ai_text, 0, 500),
        ]);
    }
} else {
    $response = ['status' => 'success', 'text' => $ai_text];
    if (!empty($result['warning']))
        $response['warning'] = $result['warning'];
    echo json_encode($response);
}