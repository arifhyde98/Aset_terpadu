<?php

namespace App\Services\Elabel;

use App\Models\Elabel\ElabelBpkb;
use App\Models\Elabel\ElabelBpkbOcrStaging;
use App\Services\UnifiedAiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BpkbOcrExtractionService
{
    protected UnifiedAiService $unifiedAi;

    public function __construct(UnifiedAiService $unifiedAi)
    {
        $this->unifiedAi = $unifiedAi;
    }

    /**
     * Memproses ekstraksi data BPKB dari berkas fisik (PDF / JPG / PNG) di storage server.
     * Mendukung OpenAI-compatible Vision Gateway (9router ag/gemini-3.8-flash-high)
     * dan Google Gemini Vision langsung, dengan fallback otomatis ke local heuristic engine.
     */
    public function extract(ElabelBpkb $bpkb): array
    {
        if (empty($bpkb->pdf_path)) {
            return [
                'success' => false,
                'message' => 'Record BPKB belum memiliki berkas fisik scan (pdf_path kosong).',
            ];
        }

        $fullPath = $this->resolvePdfPath($bpkb->pdf_path);
        if (!$fullPath || !file_exists($fullPath)) {
            return [
                'success' => false,
                'message' => "Berkas fisik tidak ditemukan pada server: {$bpkb->pdf_path}",
            ];
        }

        try {
            $extractedData = null;
            $engine = 'local';

            // 1. Coba gunakan Vision AI melalui gateway aktif (OpenAI-compatible / 9router atau Gemini Direct)
            $openAiService = $this->unifiedAi->getOpenAiService();
            $geminiService = $this->unifiedAi->getGeminiService();

            if ($openAiService->isAvailable()) {
                $aiResult = $this->extractViaOpenAiVision($fullPath, $bpkb);
                if ($aiResult['success'] && !empty($aiResult['data'])) {
                    $extractedData = $aiResult['data'];
                    $engine = 'openai_vision (' . ($aiResult['model'] ?? 'vision') . ')';
                }
            } elseif ($geminiService->isAvailable()) {
                $aiResult = $this->extractViaGeminiDirect($fullPath, $bpkb);
                if ($aiResult['success'] && !empty($aiResult['data'])) {
                    $extractedData = $aiResult['data'];
                    $engine = 'gemini_direct_vision';
                }
            }

            // 2. Fallback: Ekstraksi lokal teks PDF (pdftotext + heuristic regex)
            if (!$extractedData) {
                $localText = $this->extractTextFromPdf($fullPath);
                $extractedData = $this->parseHeuristically($localText, basename($fullPath), $bpkb);
                $engine = 'local_heuristic';
            }

            // 3. Normalisasi data hasil ekstraksi
            $normalized = $this->normalizeExtractedData($extractedData, $bpkb);

            // 4. Hitung perbedaan antara DB saat ini dan hasil ekstraksi
            $diff = $this->calculateDiff($bpkb, $normalized);

            // 5. Simpan / perbarui ke tabel staging buffer
            $staging = ElabelBpkbOcrStaging::updateOrCreate(
                [
                    'bpkb_id' => $bpkb->id,
                    'status'  => 'pending',
                ],
                [
                    'pdf_filename'       => basename($bpkb->pdf_path),
                    'raw_extracted_json' => $normalized,
                    'diff_fields_json'   => $diff,
                    'error_message'      => null,
                ]
            );

            return [
                'success'    => true,
                'engine'     => $engine,
                'staging_id' => $staging->id,
                'data'       => $normalized,
                'diff'       => $diff,
                'message'    => 'Ekstraksi BPKB berhasil diproses dan ditampung ke staging buffer.',
            ];
        } catch (\Throwable $e) {
            Log::error("BpkbOcrExtractionService Error for BPKB #{$bpkb->id}: " . $e->getMessage());

            ElabelBpkbOcrStaging::updateOrCreate(
                [
                    'bpkb_id' => $bpkb->id,
                    'status'  => 'pending',
                ],
                [
                    'pdf_filename'  => basename($bpkb->pdf_path),
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                ]
            );

            return [
                'success' => false,
                'message' => 'Gagal memproses ekstraksi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Ekstraksi via OpenAI-compatible endpoint (9router / OpenRouter / DeepSeek / OpenAI).
     */
    protected function extractViaOpenAiVision(string $filePath, ElabelBpkb $bpkb): array
    {
        $apiKey = \App\Models\Setting::get('ai_openai_api_key') ?: config('services.openai.api_key', env('OPENAI_API_KEY'));
        $baseUrl = rtrim(\App\Models\Setting::get('ai_openai_base_url') ?: config('services.openai.base_url', env('OPENAI_BASE_URL', 'https://api.openai.com/v1')), '/');
        $model = \App\Models\Setting::get('ai_openai_model') ?: config('services.openai.model', env('OPENAI_MODEL', 'ag/gemini-3.8-flash-high'));

        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'API Key OpenAI/9router belum diset.'];
        }

        // Siapkan gambar dasar base64 (maksimal 2 halaman)
        $base64Images = $this->prepareImagesForVision($filePath);
        if (empty($base64Images)) {
            return ['success' => false, 'message' => 'Gagal menyiapkan gambar dokumen untuk AI Vision.'];
        }

        $prompt = <<<PROMPT
Anda adalah asisten cerdas pencatatan aset Buku Pemilik Kendaraan Bermotor (BPKB) resmi di Indonesia.
Tugas Anda adalah membaca gambar/halaman dokumen BPKB terlampir secara teliti dan mengekstrak seluruh data identitas kendaraan ke dalam format JSON.

PANDUAN PEMBACAAN:
1. "no_bpkb": Nomor registrasi/seri BPKB tertera di bagian atas/header (contoh: T-03676564, M-08123456, K-1234567, dll).
2. "plate_number": Nomor Polisi / Registrasi Kendaraan (contoh: DN 2906 B, DN 1234 AB).
3. "no_rangka": Nomor Rangka / VIN kendaraan (biasanya 17 digit).
4. "no_mesin": Nomor Mesin kendaraan.
5. "merek": Merk kendaraan (contoh: HONDA, TOYOTA, YAMAHA, SUZUKI, MITSUBISHI, DAIHATSU).
6. "tipe": Tipe / model spesifik kendaraan (contoh: VARIO 125, AVANZA, INNOVA, BEAT, SUPRA).
7. "year": Tahun pembuatan/perakitan dalam angka 4 digit (contoh: 2018, 2021).
8. "isi_silinder": Kapasitas mesin dalam angka CC (contoh: 125, 1496, 110).
9. "warna": Warna kendaraan (contoh: HITAM, MERAH, PUTIH, SILVER).
10. "pengguna": Nama pemilik tertera pada BPKB (contoh: BADAN PERENCANAAN DAN PEMBANGUNAN DAERAH KAB. DONGGALA, PEMERINTAH KABUPATEN DONGGALA, atau nama pribadi/instansi).
11. "vehicle_type": Tulis "R2" jika sepeda motor (roda dua), atau "R4" jika mobil/bus/truk (roda empat).

ATURAN OUTPUT:
- WAJIB menghasilkan format JSON murni.
- Gunakan nilai null jika kolom tersebut tidak tercantum atau tidak ada pada halaman dokumen yang dilampirkan.
- Jangan mengarang data.

Contoh format JSON:
{
  "no_bpkb": "T-03676564",
  "plate_number": "DN 2906 B",
  "no_rangka": "MH1JM311...",
  "no_mesin": "JM31E...",
  "merek": "HONDA",
  "tipe": "VARIO 125",
  "year": 2021,
  "isi_silinder": "125",
  "warna": "HITAM",
  "pengguna": "BADAN PERENCANAAN...",
  "vehicle_type": "R2"
}
PROMPT;

        $userContent = [
            [
                'type' => 'text',
                'text' => $prompt,
            ]
        ];

        foreach ($base64Images as $b64) {
            $userContent[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => 'data:image/jpeg;base64,' . $b64,
                ]
            ];
        }

        try {
            $response = Http::timeout(50)
                ->withToken($apiKey)
                ->post($baseUrl . '/chat/completions', [
                    'model'       => $model,
                    'stream'      => false,
                    'messages'    => [
                        [
                            'role'    => 'user',
                            'content' => $userContent,
                        ]
                    ],
                    'max_tokens'  => 800,
                    'temperature' => 0.1,
                ]);

            if (!$response->successful()) {
                Log::warning("OpenAI Vision failed (HTTP {$response->status()}): " . $response->body());
                return [
                    'success' => false,
                    'message' => 'AI Vision Error: ' . $response->body(),
                ];
            }

            $resJson = $response->json();
            $rawContent = $resJson['choices'][0]['message']['content'] ?? null;

            if (!$rawContent) {
                return ['success' => false, 'message' => 'Respon AI Vision kosong.'];
            }

            $decoded = $this->parseJsonFromAiResponse($rawContent);
            if (!is_array($decoded)) {
                return ['success' => false, 'message' => 'Gagal mengurai respon JSON dari AI Vision.'];
            }

            return [
                'success' => true,
                'model'   => $model,
                'data'    => $decoded,
            ];
        } catch (\Throwable $e) {
            Log::error("Error in extractViaOpenAiVision: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Ekstraksi via Google Gemini Direct API jika diisi di setting.
     */
    protected function extractViaGeminiDirect(string $filePath, ElabelBpkb $bpkb): array
    {
        $apiKey = \App\Models\Setting::get('ai_gemini_api_key') ?: config('services.gemini.api_key', env('GEMINI_API_KEY'));
        $model = \App\Models\Setting::get('ai_gemini_model') ?: config('services.gemini.model', 'gemini-1.5-flash');

        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'API Key Gemini belum diset.'];
        }

        $base64Images = $this->prepareImagesForVision($filePath);
        if (empty($base64Images)) {
            return ['success' => false, 'message' => 'Gagal menyiapkan gambar dokumen.'];
        }

        $prompt = "Ekstrak data BPKB dari gambar ke format JSON murni dengan key: no_bpkb, plate_number, no_rangka, no_mesin, merek, tipe, year, isi_silinder, warna, pengguna, vehicle_type. Gunakan null jika tidak ada pada gambar.";

        $parts = [['text' => $prompt]];
        foreach ($base64Images as $b64) {
            $parts[] = [
                'inline_data' => [
                    'mime_type' => 'image/jpeg',
                    'data'      => $b64,
                ]
            ];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        try {
            $response = Http::timeout(45)
                ->withHeaders([
                    'Content-Type'   => 'application/json',
                    'x-goog-api-key' => $apiKey,
                ])
                ->post($url, [
                    'contents' => [
                        [
                            'role'  => 'user',
                            'parts' => $parts,
                        ]
                    ],
                    'generationConfig' => [
                        'temperature'       => 0.1,
                        'response_mime_type'=> 'application/json',
                    ],
                ]);

            if (!$response->successful()) {
                return ['success' => false, 'message' => 'Gemini API Error: ' . $response->body()];
            }

            $resJson = $response->json();
            $rawText = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if (!$rawText) {
                return ['success' => false, 'message' => 'Respon Gemini kosong.'];
            }

            $decoded = $this->parseJsonFromAiResponse($rawText);
            return [
                'success' => is_array($decoded),
                'data'    => $decoded,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Mempersiapkan berkas (baik PDF, JPG, PNG) menjadi array string base64 JPEG terkompresi optimal (< 250KB).
     * Mencegah Nginx 413 Request Entity Too Large.
     */
    protected function prepareImagesForVision(string $filePath): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $images = [];

        if ($ext === 'pdf') {
            $tmpDir = storage_path('app/temp_ocr');
            if (!file_exists($tmpDir)) {
                @mkdir($tmpDir, 0777, true);
            }

            $prefix = $tmpDir . '/ocr_' . uniqid();
            // Ambil halaman 1 & 2 dari PDF
            $cmd = "pdftoppm -jpeg -r 150 -f 1 -l 2 " . escapeshellarg($filePath) . " " . escapeshellarg($prefix) . " 2>&1";
            exec($cmd, $out, $ret);

            $generated = glob($prefix . '-*.jpg') ?: [];
            sort($generated);

            foreach ($generated as $pageFile) {
                $b64 = $this->optimizeImageForVision($pageFile);
                if ($b64) {
                    $images[] = $b64;
                }
                @unlink($pageFile);
            }
        } else {
            // Berkas berupa gambar langsung (JPG, JPEG, PNG, WEBP)
            $b64 = $this->optimizeImageForVision($filePath);
            if ($b64) {
                $images[] = $b64;
            }
        }

        return $images;
    }

    /**
     * Me-resize dan mengompresi gambar ke resolusi max 1100px dan ukuran < 200KB.
     */
    protected function optimizeImageForVision(string $imagePath): ?string
    {
        if (!file_exists($imagePath) || filesize($imagePath) === 0) {
            return null;
        }

        $info = @getimagesize($imagePath);
        if (!$info) {
            return null;
        }

        $width = $info[0];
        $height = $info[1];
        $mime = $info['mime'] ?? 'image/jpeg';

        $maxDim = 1100;
        $targetWidth = $width;
        $targetHeight = $height;

        if ($width > $maxDim || $height > $maxDim) {
            if ($width >= $height) {
                $targetWidth = $maxDim;
                $targetHeight = (int) round($height * ($maxDim / $width));
            } else {
                $targetHeight = $maxDim;
                $targetWidth = (int) round($width * ($maxDim / $height));
            }
        }

        $srcImg = null;
        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $srcImg = @imagecreatefromjpeg($imagePath);
        } elseif ($mime === 'image/png') {
            $srcImg = @imagecreatefrompng($imagePath);
        } elseif ($mime === 'image/webp') {
            $srcImg = @imagecreatefromwebp($imagePath);
        }

        if (!$srcImg) {
            return filesize($imagePath) < 300 * 1024 ? base64_encode(file_get_contents($imagePath)) : null;
        }

        $dstImg = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($dstImg, 255, 255, 255);
        imagefilledrectangle($dstImg, 0, 0, $targetWidth, $targetHeight, $white);

        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($dstImg, null, 65);
        $compressed = ob_get_clean();

        imagedestroy($srcImg);
        imagedestroy($dstImg);

        return base64_encode($compressed);
    }

    /**
     * Membersihkan dan mengurai teks JSON dari respon AI (menghapus markdown dan reasoning token).
     */
    protected function parseJsonFromAiResponse(string $rawText): ?array
    {
        // Bersihkan reasoning block <think>...</think>
        $cleaned = preg_replace('/<think>[\s\S]*?<\/think>/i', '', $rawText);
        $cleaned = trim($cleaned);

        // Ambil JSON di dalam code block ```json ... ```
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $cleaned, $matches)) {
            $cleaned = trim($matches[1]);
        }

        // Cari substring kurung kurawal pertama { hingga terakhir }
        $firstBrace = strpos($cleaned, '{');
        $lastBrace = strrpos($cleaned, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $cleaned = substr($cleaned, $firstBrace, ($lastBrace - $firstBrace + 1));
        }

        $decoded = json_decode($cleaned, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Resolusi path absolut berkas pada storage.
     */
    public function resolvePdfPath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->path($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        if (file_exists($path)) {
            return $path;
        }

        $appStoragePath = storage_path('app/' . ltrim($path, '/'));
        if (file_exists($appStoragePath)) {
            return $appStoragePath;
        }

        $privateStoragePath = storage_path('app/private/' . ltrim($path, '/'));
        if (file_exists($privateStoragePath)) {
            return $privateStoragePath;
        }

        $publicStoragePath = storage_path('app/public/' . ltrim($path, '/'));
        if (file_exists($publicStoragePath)) {
            return $publicStoragePath;
        }

        return null;
    }

    /**
     * Membaca teks mentah dari PDF menggunakan utilitas lokal pdftotext.
     */
    protected function extractTextFromPdf(string $pdfPath): string
    {
        $text = '';
        if (function_exists('exec')) {
            $cmd = "pdftotext -q -f 1 -l 2 " . escapeshellarg($pdfPath) . " - 2>&1";
            exec($cmd, $out, $ret);
            if ($ret === 0 && !empty($out)) {
                $text = implode("\n", $out);
            }
        }

        return $text;
    }

    /**
     * Parsing teks berbasis pola heuristik (Regex) sebagai fallback jika AI offline.
     */
    protected function parseHeuristically(string $text, string $filename, ElabelBpkb $bpkb): array
    {
        $data = [
            'no_bpkb'      => null,
            'plate_number' => null,
            'no_rangka'    => null,
            'no_mesin'     => null,
            'merek'        => null,
            'tipe'         => null,
            'year'         => null,
            'isi_silinder' => null,
            'warna'        => null,
            'pengguna'     => null,
            'vehicle_type' => null,
        ];

        $prefixes = 'DN|DD|DW|DA|DB|DC|DL|DM|DT|B|D|L|N|W|AA|AB|AD';
        if (preg_match('/(' . $prefixes . ')\s*(\d{1,4})\s*([A-Z]{1,3})/i', $text, $m)) {
            $data['plate_number'] = strtoupper(trim($m[1])) . " " . trim($m[2]) . " " . strtoupper(trim($m[3]));
        } elseif (preg_match('/(' . $prefixes . ')[_\s\-]*(\d{1,4})[_\s\-]*([A-Z]{1,3})/i', $filename, $m)) {
            $data['plate_number'] = strtoupper(trim($m[1])) . " " . trim($m[2]) . " " . strtoupper(trim($m[3]));
        }

        if (preg_match('/(?:NO\.?\s*BPKB|NOMOR\s+BPKB)\s*[:\.]?\s*([A-Z0-9\-\s]{6,15})/i', $text, $m)) {
            $data['no_bpkb'] = strtoupper(trim($m[1]));
        }

        if (preg_match('/(?:NO\.?\s*RANGKA|NOMOR\s+RANGKA|VIN|CHASSIS)\s*[:\.]?\s*([A-Z0-9]{10,20})/i', $text, $m)) {
            $data['no_rangka'] = strtoupper(trim($m[1]));
        }

        if (preg_match('/(?:NO\.?\s*MESIN|NOMOR\s+MESIN|ENGINE\s*NO)\s*[:\.]?\s*([A-Z0-9\-]{6,20})/i', $text, $m)) {
            $data['no_mesin'] = strtoupper(trim($m[1]));
        }

        return $data;
    }

    /**
     * Normalisasi string dan format data agar rapi dan seragam.
     */
    protected function normalizeExtractedData(array $raw, ElabelBpkb $bpkb): array
    {
        $clean = function (?string $val) {
            if ($val === null) return null;
            $val = trim($val);
            return $val === '' || strtolower($val) === 'null' ? null : $val;
        };

        $year = isset($raw['year']) && is_numeric($raw['year']) ? (int) $raw['year'] : null;
        if ($year && ($year < 1970 || $year > (int) date('Y') + 1)) {
            $year = null;
        }

        $vType = strtoupper((string) ($raw['vehicle_type'] ?? ''));
        if (!in_array($vType, ['R2', 'R4'], true)) {
            $vType = strtoupper((string) $bpkb->vehicle_type);
            if (!in_array($vType, ['R2', 'R4'], true)) {
                $vType = 'R4';
            }
        }

        return [
            'no_bpkb'      => $clean($raw['no_bpkb'] ?? null),
            'plate_number' => $clean($raw['plate_number'] ?? null),
            'no_rangka'    => $clean($raw['no_rangka'] ?? null),
            'no_mesin'     => $clean($raw['no_mesin'] ?? null),
            'merek'        => $clean($raw['merek'] ?? null),
            'tipe'         => $clean($raw['tipe'] ?? null),
            'year'         => $year,
            'isi_silinder' => $clean($raw['isi_silinder'] ?? null),
            'warna'        => $clean($raw['warna'] ?? null),
            'pengguna'     => $clean($raw['pengguna'] ?? null),
            'vehicle_type' => $vType,
        ];
    }

    /**
     * Menghitung field mana saja yang berbeda atau mengisi kekosongan pada data DB saat ini.
     */
    protected function calculateDiff(ElabelBpkb $bpkb, array $extracted): array
    {
        $fields = [
            'no_bpkb', 'plate_number', 'no_rangka', 'no_mesin',
            'merek', 'tipe', 'year', 'isi_silinder', 'warna', 'pengguna', 'vehicle_type'
        ];

        $diff = [];
        foreach ($fields as $field) {
            $currentVal = $bpkb->{$field};
            $extractedVal = $extracted[$field] ?? null;

            $isCurrentEmpty = empty($currentVal) || $currentVal === '-';
            $hasExtracted = !empty($extractedVal);

            $isDifferent = false;
            if ($hasExtracted) {
                if ($isCurrentEmpty) {
                    $isDifferent = true;
                } else {
                    $c = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $currentVal));
                    $e = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $extractedVal));
                    if ($c !== $e) {
                        $isDifferent = true;
                    }
                }
            }

            $diff[$field] = [
                'current'     => $currentVal,
                'extracted'   => $extractedVal,
                'is_empty'    => $isCurrentEmpty,
                'is_different'=> $isDifferent,
                // Rekomendasi centang otomatis jika data DB kosong atau data berbeda tapi valid
                'should_apply'=> $hasExtracted && ($isCurrentEmpty || $isDifferent),
            ];
        }

        return $diff;
    }
}
