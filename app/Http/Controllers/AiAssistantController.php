<?php

namespace App\Http\Controllers;

use App\Services\GeminiAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AiAssistantController extends Controller implements HasMiddleware
{
    protected GeminiAiService $ai;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function __construct(GeminiAiService $ai)
    {
        $this->ai = $ai;
    }

    /**
     * Memeriksa status kesiapan layanan Google Gemini AI.
     */
    public function status(Request $request): JsonResponse
    {
        $isOnline = $this->ai->isAvailable();
        $modelName = $this->ai->getActiveModelName();

        return response()->json([
            'status'            => $isOnline ? 'online' : 'offline',
            'provider'          => 'google-gemini',
            'message'           => $isOnline ? "Google Gemini Cloud AI Siap Digunakan ({$modelName})" : 'GEMINI_API_KEY belum diatur di .env',
            'default_model'     => $modelName,
            'has_default_model' => true,
        ]);
    }

    /**
     * Tanya jawab (Prompting) seputar data atau pertanyaan umum aset.
     */
    public function ask(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string|max:4000',
            'system' => 'nullable|string|max:1000',
        ]);

        $defaultSystem = "Kamu adalah Asisten Pintar Pengelolaan Barang Milik Daerah (BMD) dan Aset Terpadu (SIPAT & E-RANDIS).\n\n"
            . "PETUNJUK:\n"
            . "- Langsung berikan jawaban akhir yang rapi dan profesional untuk pengguna dalam Bahasa Indonesia.\n"
            . "- Dilarang keras menampilkan proses berpikir, catatan drafting, internal monologue, atau analisis peran.\n"
            . "- Gunakan format poin-poin yang terstruktur, padat, dan informatif.";

        $system = $request->input('system', $defaultSystem);

        $result = $this->ai->generate(
            $request->input('prompt'),
            $system
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
                'source'  => $result['source'],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'data'    => $result['content'],
            'source'  => $result['source'],
        ]);
    }

    /**
     * Membuat narasi ringkasan laporan aset/kendaraan secara otomatis.
     */
    public function generateSummary(Request $request): JsonResponse
    {
        $request->validate([
            'context_data' => 'required|string|max:5000',
            'topic'        => 'nullable|string|max:100',
        ]);

        $topic = $request->input('topic', 'Laporan Rekapitulasi Aset');
        $system = 'Kamu adalah analis data aset daerah. Tugasmu membuat narasi laporan eksekutif yang padat, informatif, dan siap dicetak.';
        $prompt = "Buatkan narasi ringkasan eksekutif untuk topik: {$topic}.\nBerikut data yang tersedia:\n" . $request->input('context_data');

        $result = $this->ai->generate($prompt, $system);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
                'source'  => $result['source'],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'summary' => $result['content'],
            'source'  => $result['source'],
        ]);
    }
}
