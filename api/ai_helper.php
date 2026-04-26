<?php
/**
 * AI Helper for SIPANDA (Gemini API)
 * v2.0 — SSL aman, system instruction, retry, finishReason check
 */
function call_gemini_api($prompt_or_contents, string $api_key, string $system_instruction = '', int $max_retries = 2): array
{
    if (empty($api_key)) {
        return ['status' => 'error', 'message' => 'API Key Gemini belum diatur di Pengaturan.'];
    }

    // Menggunakan model masa depan (2.5 flash-lite) yang khusus terbuka di API key Anda!
    $model = 'gemini-2.5-flash-lite';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($api_key);

    $contents = is_array($prompt_or_contents) ? $prompt_or_contents : [
        ['role' => 'user', 'parts' => [['text' => $prompt_or_contents]]]
    ];

    $payload = [
        'contents' => $contents,
        'safetySettings' => [
            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 65536,
        ],
    ];

    // System instruction — membuat AI lebih terarah
    if (!empty($system_instruction)) {
        $payload['systemInstruction'] = [
            'parts' => [['text' => $system_instruction]]
        ];
    }

    $attempt = 0;
    $last_error = '';

    while ($attempt <= $max_retries) {
        $attempt++;
        $delay = $attempt > 1 ? (1500 * ($attempt - 1)) : 0; // 0ms, 1500ms, 3000ms
        if ($delay > 0)
            usleep($delay * 1000);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_SSL_VERIFYPEER => true,   // AMAN — verifikasi SSL aktif
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        // curl_close() is deprecated in PHP 8.5 since CurlHandle objects close automatically
        
        if ($curl_err) {
            $last_error = 'Koneksi gagal (CURL): ' . $curl_err;
            continue; // coba lagi
        }

        $result = json_decode($response, true) ?? [];

        // Rate limit (429) atau server error (5xx) — retry
        if ($http_code === 429 || $http_code >= 500) {
            $last_error = "Gemini API Error ({$http_code}): " . ($result['error']['message'] ?? 'Server error');
            continue;
        }

        if ($http_code !== 200) {
            $msg = $result['error']['message'] ?? 'Kesalahan tidak dikenal / API Endpoint tidak valid';
            return ['status' => 'error', 'message' => "Gemini API Error ({$http_code}): {$msg}"];
        }

        // Cek finish reason
        $finish_reason = $result['candidates'][0]['finishReason'] ?? 'STOP';
        if ($finish_reason === 'MAX_TOKENS') {
            // Tetap kembalikan teks tapi beri flag peringatan
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if ($text) {
                return [
                    'status' => 'success',
                    'data' => trim($text),
                    'warning' => 'Respons mungkin terpotong karena melebihi batas token.',
                ];
            }
        }

        if ($finish_reason === 'SAFETY') {
            return ['status' => 'error', 'message' => 'Konten diblokir oleh filter keamanan AI.'];
        }

        if (!empty($result['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'status' => 'success',
                'data' => trim($result['candidates'][0]['content']['parts'][0]['text']),
            ];
        }

        return ['status' => 'error', 'message' => 'Respons AI tidak valid atau kosong.'];
    }

    return ['status' => 'error', 'message' => $last_error ?: 'Gagal menghubungi Gemini API setelah beberapa percobaan.'];
}