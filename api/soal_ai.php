<?php
/**
 * API: AI Generator Soal Ujian (Gemini)
 * POST /api/soal_ai.php
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/ai_helper.php';
cek_role(['guru']);

// Cek fitur AI Soal aktif
if (!fitur_aktif('ai_soal')) {
    echo json_encode(['status' => 'error', 'message' => 'Fitur AI Generator Soal tidak tersedia dalam paket lisensi Anda.']);
    exit;
}

$api_key = $pdo->query("SELECT gemini_api_key FROM tbl_setting WHERE id=1")->fetchColumn();
if (!$api_key) {
    echo json_encode(['status' => 'error', 'message' => 'Gemini API Key belum diatur di Pengaturan.']);
    exit;
}

$action = $_POST['action'] ?? '';
$id_guru = $_SESSION['user_id'];

// ═══════════════════════════════════════════════════════════
// ACTION: GENERATE SOAL
// ═══════════════════════════════════════════════════════════
if ($action === 'generate') {
    $mapel       = trim($_POST['mapel'] ?? '');
    $kelas       = trim($_POST['kelas'] ?? '');
    $topik       = trim($_POST['topik'] ?? '');
    $cp          = trim($_POST['cp'] ?? '');
    $jml_pg      = (int)($_POST['jml_pg'] ?? 10);
    $jml_essay   = (int)($_POST['jml_essay'] ?? 0);
    $opsi_pg     = (int)($_POST['opsi_pg'] ?? 5);
    $tingkat     = $_POST['tingkat'] ?? 'Campuran';
    $berpikir    = $_POST['berpikir'] ?? 'Campuran';

    if (!$topik) {
        echo json_encode(['status' => 'error', 'message' => 'Topik/Materi harus diisi.']);
        exit;
    }

    $opsi_labels = ['A'];
    if ($opsi_pg >= 2) $opsi_labels[] = 'B';
    if ($opsi_pg >= 3) $opsi_labels[] = 'C';
    if ($opsi_pg >= 4) $opsi_labels[] = 'D';
    if ($opsi_pg >= 5) $opsi_labels[] = 'E';
    $opsi_str = implode(', ', $opsi_labels);

    $tingkat_instruksi = '';
    if ($tingkat === 'Campuran') {
        $tingkat_instruksi = 'Distribusikan tingkat kesulitan: 30% Mudah, 50% Sedang, 20% Sulit.';
    } else {
        $tingkat_instruksi = "Semua soal bertingkat kesulitan: {$tingkat}.";
    }

    $berpikir_instruksi = '';
    if ($berpikir === 'LOTS') {
        $berpikir_instruksi = 'Fokus pada taksonomi C1 (Mengingat), C2 (Memahami), C3 (Mengaplikasikan).';
    } elseif ($berpikir === 'HOTS') {
        $berpikir_instruksi = 'Fokus pada taksonomi C4 (Menganalisis), C5 (Mengevaluasi), C6 (Mencipta).';
    } else {
        $berpikir_instruksi = 'Campurkan taksonomi C1-C6 secara merata.';
    }

    $prompt_pg = '';
    if ($jml_pg > 0) {
        $prompt_pg = "
SOAL PILIHAN GANDA: Buat {$jml_pg} soal PG dengan {$opsi_pg} opsi ({$opsi_str}).
Format setiap soal PG di array \"soal_pg\":
{
  \"pertanyaan\": \"...\",
  \"opsi_a\": \"...\", \"opsi_b\": \"...\"" . ($opsi_pg >= 3 ? ", \"opsi_c\": \"...\"" : "") . ($opsi_pg >= 4 ? ", \"opsi_d\": \"...\"" : "") . ($opsi_pg >= 5 ? ", \"opsi_e\": \"...\"" : "") . ",
  \"jawaban\": \"(huruf jawaban benar)\",
  \"pembahasan\": \"...\",
  \"tingkat\": \"Mudah|Sedang|Sulit\",
  \"taksonomi\": \"C1|C2|C3|C4|C5|C6\",
  \"indikator\": \"(indikator soal singkat)\"
}";
    }

    $prompt_essay = '';
    if ($jml_essay > 0) {
        $prompt_essay = "
SOAL ESSAY: Buat {$jml_essay} soal essay/uraian.
Format setiap soal Essay di array \"soal_essay\":
{
  \"pertanyaan\": \"...\",
  \"jawaban\": \"(jawaban lengkap)\",
  \"pembahasan\": \"...\",
  \"tingkat\": \"Mudah|Sedang|Sulit\",
  \"taksonomi\": \"C4|C5|C6\",
  \"indikator\": \"(indikator soal singkat)\"
}";
    }

    $prompt = "Anda adalah Ahli Pembuat Soal Ujian Senior di Indonesia.
TUGAS: Buatkan soal ujian yang berkualitas tinggi, profesional, dan sesuai kurikulum.
KONTEKS: Mapel: {$mapel}, Kelas: {$kelas}, Topik/Materi: {$topik}
" . ($cp ? "CAPAIAN PEMBELAJARAN (CP): {$cp}" : "") . "

ATURAN WAJIB:
1. JANGAN gunakan format Markdown (JANGAN ADA BINTANG **).
2. Bahasa Indonesia formal dan baku.
3. {$tingkat_instruksi}
4. {$berpikir_instruksi}
5. Jawaban PG harus bervariasi (jangan selalu A atau B).
6. Setiap soal WAJIB punya pembahasan yang jelas.
7. Indikator harus relevan dengan topik dan CP.
{$prompt_pg}
{$prompt_essay}

KEMBALIKAN HANYA JSON (tanpa markdown, tanpa backtick):
{" . ($jml_pg > 0 ? "\"soal_pg\": [...]" : "") . ($jml_pg > 0 && $jml_essay > 0 ? "," : "") . ($jml_essay > 0 ? "\"soal_essay\": [...]" : "") . "}";

    $result = call_gemini_api($prompt, $api_key);

    if ($result['status'] === 'success') {
        $ai_text = $result['data'];
        
        // Bersihkan formatter markdown jika AI masih bandel
        $clean_text = preg_replace('/^```(?:json)?\s*/i', '', trim($ai_text));
        $clean_text = preg_replace('/```\s*$/i', '', trim($clean_text));
        $clean_text = trim($clean_text);

        $start = strpos($clean_text, '{');
        $end = strrpos($clean_text, '}');

        if ($start !== false && $end !== false && $end > $start) {
            $json_str = substr($clean_text, $start, $end - $start + 1);
            $json_data = json_decode($json_str, true);
            
            if ($json_data) {
                echo json_encode(['status' => 'success', 'data' => $json_data]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal parsing JSON (' . json_last_error_msg() . ').', 'raw' => $ai_text]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Response AI tidak valid (tidak ada kurung kurawal).', 'raw' => $ai_text]);
        }
    } else {
        echo json_encode($result);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: SIMPAN SOAL KE BANK
// ═══════════════════════════════════════════════════════════
if ($action === 'simpan_bank') {
    $soal_json = $_POST['soal'] ?? '';
    $id_mapel  = (int)($_POST['id_mapel'] ?? 0);
    $id_kelas  = (int)($_POST['id_kelas'] ?? 0);
    $topik     = trim($_POST['topik'] ?? '');
    $kompetensi = trim($_POST['kompetensi'] ?? '');

    $soal_arr = json_decode($soal_json, true);
    if (!$soal_arr || !is_array($soal_arr)) {
        echo json_encode(['status' => 'error', 'message' => 'Data soal tidak valid.']);
        exit;
    }

    $ins = $pdo->prepare("INSERT INTO tbl_bank_soal (id_guru, id_mapel, id_kelas, tipe_soal, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, jawaban, pembahasan, tingkat, taksonomi, topik, indikator, kompetensi, sumber) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $count = 0;
    foreach ($soal_arr as $s) {
        $tipe = $s['tipe_soal'] ?? 'PG';
        $ins->execute([
            $id_guru, $id_mapel, $id_kelas ?: null, $tipe,
            $s['pertanyaan'] ?? '',
            $s['opsi_a'] ?? null, $s['opsi_b'] ?? null, $s['opsi_c'] ?? null, $s['opsi_d'] ?? null, $s['opsi_e'] ?? null,
            $s['jawaban'] ?? null, $s['pembahasan'] ?? null,
            $s['tingkat'] ?? 'Sedang', $s['taksonomi'] ?? 'C2',
            $topik, $s['indikator'] ?? null, $kompetensi, 'AI'
        ]);
        $count++;
    }

    echo json_encode(['status' => 'success', 'message' => "{$count} soal berhasil disimpan ke Bank Soal!", 'count' => $count]);
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: SIMPAN PAKET UJIAN
// ═══════════════════════════════════════════════════════════
if ($action === 'simpan_paket') {
    $nama_ujian     = trim($_POST['nama_ujian'] ?? '');
    $tipe_ujian     = trim($_POST['tipe_ujian'] ?? 'Tulis');
    $jenis_ujian    = trim($_POST['jenis_ujian'] ?? 'UH');
    $id_mapel       = (int)($_POST['id_mapel'] ?? 0);
    $id_kelas       = (int)($_POST['id_kelas'] ?? 0);
    $semester       = trim($_POST['semester'] ?? 'Ganjil');
    $tanggal_ujian  = $_POST['tanggal_ujian'] ?? null;
    $durasi         = (int)($_POST['durasi_menit'] ?? 60);
    $jumlah_opsi    = (int)($_POST['jumlah_opsi_pg'] ?? 5);
    $petunjuk       = trim($_POST['petunjuk_umum'] ?? '');
    $topik          = trim($_POST['topik'] ?? '');
    $soal_ids       = json_decode($_POST['soal_ids'] ?? '[]', true);
    $auto_latest    = (int)($_POST['auto_latest'] ?? 0);

    // Auto-fetch latest N soal from bank for this guru
    if ($auto_latest > 0 && empty($soal_ids)) {
        $latest = $pdo->prepare("SELECT id_soal_bank FROM tbl_bank_soal WHERE id_guru=? ORDER BY id_soal_bank DESC LIMIT ?");
        $latest->execute([$id_guru, $auto_latest]);
        $soal_ids = array_reverse($latest->fetchAll(PDO::FETCH_COLUMN));
    }

    if (!$nama_ujian) {
        echo json_encode(['status' => 'error', 'message' => 'Nama ujian harus diisi.']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $ins = $pdo->prepare("INSERT INTO tbl_paket_ujian (id_guru, id_mapel, id_kelas, nama_ujian, tipe_ujian, jenis_ujian, semester, tanggal_ujian, durasi_menit, jumlah_opsi_pg, petunjuk_umum, topik) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $ins->execute([$id_guru, $id_mapel, $id_kelas ?: null, $nama_ujian, $tipe_ujian, $jenis_ujian, $semester, $tanggal_ujian ?: null, $durasi, $jumlah_opsi, $petunjuk, $topik]);
        $id_paket = $pdo->lastInsertId();

        $ins_soal = $pdo->prepare("INSERT INTO tbl_paket_soal (id_paket, id_soal_bank, nomor_urut, bobot) VALUES (?,?,?,?)");
        foreach ($soal_ids as $idx => $sid) {
            $ins_soal->execute([$id_paket, (int)$sid, $idx + 1, 1]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Paket ujian berhasil dibuat!', 'id_paket' => $id_paket]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Gagal: ' . $e->getMessage()]);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: PUSH KE QUIZ
// ═══════════════════════════════════════════════════════════
if ($action === 'push_quiz') {
    $id_paket  = (int)($_POST['id_paket'] ?? 0);
    $soal_ids  = json_decode($_POST['soal_ids'] ?? '[]', true);

    if (!$id_paket || empty($soal_ids)) {
        echo json_encode(['status' => 'error', 'message' => 'Pilih minimal 1 soal untuk di-push.']);
        exit;
    }

    $paket = $pdo->prepare("SELECT * FROM tbl_paket_ujian WHERE id_paket=? AND id_guru=?");
    $paket->execute([$id_paket, $id_guru]);
    $p = $paket->fetch();
    if (!$p) {
        echo json_encode(['status' => 'error', 'message' => 'Paket tidak ditemukan.']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $ins_quiz = $pdo->prepare("INSERT INTO tbl_quiz (judul, id_mapel, id_kelas, id_guru, waktu_menit, status) VALUES (?,?,?,?,?,?)");
        $ins_quiz->execute([$p['nama_ujian'], $p['id_mapel'], $p['id_kelas'], $id_guru, $p['durasi_menit'], 'Aktif']);
        $id_quiz = $pdo->lastInsertId();

        $ins_soal = $pdo->prepare("INSERT INTO tbl_soal (id_quiz, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban, bobot) VALUES (?,?,?,?,?,?,?,?)");

        $placeholders = implode(',', array_fill(0, count($soal_ids), '?'));
        $get_soal = $pdo->prepare("SELECT * FROM tbl_bank_soal WHERE id_soal_bank IN ({$placeholders}) AND id_guru=?");
        $get_soal->execute(array_merge($soal_ids, [$id_guru]));
        $soal_list = $get_soal->fetchAll();

        foreach ($soal_list as $s) {
            if ($s['tipe_soal'] !== 'PG') continue;
            $ins_soal->execute([$id_quiz, $s['pertanyaan'], $s['opsi_a'] ?? '', $s['opsi_b'] ?? '', $s['opsi_c'] ?? '', $s['opsi_d'] ?? '', $s['jawaban'], 1]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Soal berhasil di-push ke Quiz Online!', 'id_quiz' => $id_quiz]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Gagal: ' . $e->getMessage()]);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: HAPUS SOAL BANK
// ═══════════════════════════════════════════════════════════
if ($action === 'hapus_soal') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM tbl_bank_soal WHERE id_soal_bank=? AND id_guru=?")->execute([$id, $id_guru]);
    echo json_encode(['status' => 'success', 'message' => 'Soal dihapus.']);
    exit;
}

// ═══════════════════════════════════════════════════════════
// ACTION: HAPUS PAKET
// ═══════════════════════════════════════════════════════════
if ($action === 'hapus_paket') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM tbl_paket_ujian WHERE id_paket=? AND id_guru=?")->execute([$id, $id_guru]);
    echo json_encode(['status' => 'success', 'message' => 'Paket dihapus.']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali.']);
