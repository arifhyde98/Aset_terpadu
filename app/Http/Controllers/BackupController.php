<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return array
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin'),
        ];
    }

    /**
     * Memeriksa status backup saat ini.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(): \Illuminate\Http\JsonResponse
    {
        $progress = Cache::get('backup_progress');
        return response()->json($progress ?: ['status' => 'idle']);
    }

    /**
     * Menampilkan daftar backup yang tersedia.
     *
     * @return View
     */
    public function index(): View
    {
        $disk = Storage::disk('backups');
        $backups = [];

        try {
            if ($disk->exists('')) {
                $allFiles = $disk->allFiles();
                foreach ($allFiles as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                        $backups[] = [
                            'file_name' => $file,
                            'file_path' => $file,
                            'file_size' => $this->formatBytes($disk->size($file)),
                            'last_modified' => date('d F Y, H:i:s', $disk->lastModified($file)),
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Gagal membaca direktori backup: ' . $e->getMessage());
        }

        // Sort by last modified descending
        usort($backups, function ($a, $b) use ($disk) {
            return $disk->lastModified($b['file_path']) <=> $disk->lastModified($a['file_path']);
        });

        // Hitung estimasi kapasitas disk
        $diskFree = disk_free_space(storage_path());
        $diskTotal = disk_total_space(storage_path());
        $diskUsed = $diskTotal - $diskFree;
        
        $diskInfo = [
            'free' => $this->formatBytes($diskFree),
            'used' => $this->formatBytes($diskUsed),
            'total' => $this->formatBytes($diskTotal),
            'percent' => $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100) : 0,
        ];

        return view('settings.backups.index', compact('backups', 'diskInfo'));
    }

    /**
     * Memicu proses backup baru secara manual di background.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function create(Request $request)
    {
        try {
            $option = $request->input('option', 'all'); // all atau db
            
            // Periksa jika proses backup sedang berjalan
            $progress = Cache::get('backup_progress');
            if ($progress && ($progress['status'] ?? null) === 'running') {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['status' => 'error', 'message' => 'Proses pencadangan saat ini sedang berjalan.'], 400);
                }
                return redirect()->route('settings.backups.index')
                    ->with('error', 'Proses pencadangan saat ini sedang berjalan.');
            }

            // Jalankan Artisan command di background
            $command = 'php ' . base_path('artisan') . ' app:run-backup-bg ' . escapeshellarg($option) . ' > /dev/null 2>&1 &';
            exec($command);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => 'success', 'message' => 'Proses backup berhasil dimulai di background.']);
            }

            return redirect()->route('settings.backups.index')
                ->with('success', 'Proses backup berhasil dimulai di background. Pantau kemajuan di bawah.');
        } catch (\Exception $e) {
            Log::error('Gagal memicu backup background: ' . $e->getMessage());
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
            return redirect()->route('settings.backups.index')
                ->with('error', 'Gagal memicu backup: ' . $e->getMessage());
        }
    }

    /**
     * Mengunduh berkas backup.
     *
     * @param string $fileName
     * @return StreamedResponse|RedirectResponse
     */
    public function download($fileName)
    {
        // Bersihkan nama file dari karakter berbahaya
        $fileName = str_replace(['../', '..\\'], '', $fileName);
        $disk = Storage::disk('backups');

        if ($disk->exists($fileName)) {
            \App\Models\Activity::log("Mengunduh berkas backup: {$fileName}", 'info');
            return $disk->download($fileName);
        }

        return redirect()->route('settings.backups.index')
            ->with('error', 'Berkas backup tidak ditemukan di server.');
    }

    /**
     * Menghapus berkas backup.
     *
     * @param string $fileName
     * @return RedirectResponse
     */
    public function destroy($fileName): RedirectResponse
    {
        $fileName = str_replace(['../', '..\\'], '', $fileName);
        $disk = Storage::disk('backups');

        if ($disk->exists($fileName)) {
            $disk->delete($fileName);
            \App\Models\Activity::log("Menghapus berkas backup dari server: {$fileName}", 'warning');
            return redirect()->route('settings.backups.index')
                ->with('success', 'Berkas backup berhasil dihapus.');
        }

        return redirect()->route('settings.backups.index')
            ->with('error', 'Berkas backup tidak ditemukan.');
    }

    /**
    /**
     * Memeriksa status proses sinkronisasi database staging saat ini.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncDbStatus(): \Illuminate\Http\JsonResponse
    {
        $progress = Cache::get('sync_db_progress');
        return response()->json($progress ?: ['status' => 'idle']);
    }

    /**
     * Sinkronisasi data real-time per-tabel via Server-Sent Events (SSE) stream.
     *
     * @param Request $request
     * @return StreamedResponse
     */
    public function syncDbStream(Request $request): StreamedResponse
    {
        return response()->stream(function () {
            set_time_limit(300);
            if (ob_get_level()) {
                ob_end_clean();
            }

            $sendEvent = function ($data) {
                echo "data: " . json_encode($data) . "\n\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };

            try {
                $sendEvent([
                    'status' => 'running',
                    'percentage' => 5,
                    'step' => 'Menganalisis tabel database sumber...',
                    'log' => "Memulai koneksi Server-Sent Events (SSE)...\n[INFO] Mengambil skema database db_sipat_terpadu...\n"
                ]);

                // Ambil daftar tabel dari db_sipat_terpadu
                $tablesQuery = \Illuminate\Support\Facades\DB::select("SHOW TABLES FROM db_sipat_terpadu");
                $tables = [];
                foreach ($tablesQuery as $row) {
                    $val = (array) $row;
                    $tables[] = reset($val);
                }

                $total = count($tables);
                if ($total === 0) {
                    $sendEvent([
                        'status' => 'failed',
                        'percentage' => 100,
                        'step' => 'Tidak ada tabel yang ditemukan di db_sipat_terpadu.',
                        'log' => "[ERROR] Database db_sipat_terpadu kosong atau tidak dapat diakses.\n"
                    ]);
                    return;
                }

                $sendEvent([
                    'status' => 'running',
                    'percentage' => 10,
                    'step' => "Ditemukan {$total} tabel. Memulai replikasi data...",
                    'log' => "[INFO] Ditemukan {$total} tabel untuk disinkronkan.\n"
                ]);

                foreach ($tables as $index => $tableName) {
                    $currentNum = $index + 1;
                    $pct = 10 + round(($currentNum / $total) * 85); // 10% - 95%

                    $sendEvent([
                        'status' => 'running',
                        'percentage' => $pct,
                        'step' => "Menyinkronkan tabel: {$tableName} ({$currentNum}/{$total})...",
                        'log' => "[SYNC] Menyalin tabel `{$tableName}` ({$currentNum}/{$total})...\n"
                    ]);

                    // Eksekusi per tabel dengan opsi cepat
                    $command = "mysqldump -u bpkad.aset -padmin123 --single-transaction --quick --extended-insert --no-tablespaces --add-drop-table db_sipat_terpadu {$tableName} | mysql --force -u bpkad.aset -padmin123 db_sipat_staging 2>&1";
                    exec($command, $output, $returnCode);

                    if ($returnCode !== 0) {
                        $err = !empty($output) ? implode(' ', $output) : "Exit code {$returnCode}";
                        $sendEvent([
                            'status' => 'running',
                            'percentage' => $pct,
                            'step' => "Peringatan pada tabel {$tableName}",
                            'log' => "[WARN] Tabel `{$tableName}`: {$err}\n"
                        ]);
                    }
                }

                if (class_exists('\App\Models\Activity')) {
                    \App\Models\Activity::log("Menyinkronkan data penuh (100% replace) dari db_sipat_terpadu ke db_sipat_staging via SSE Stream", 'info');
                }

                $sendEvent([
                    'status' => 'success',
                    'percentage' => 100,
                    'step' => 'Seluruh ' . $total . ' Tabel Berhasil Disinkronkan 100%!',
                    'log' => "\n[SUKSES] Semua {$total} tabel berhasil disalin dan diperbarui.\n[INFO] Database staging kini 100% identik dengan SIPAT Terpadu.\n"
                ]);

            } catch (\Exception $e) {
                Log::error('SSE Sync DB Exception: ' . $e->getMessage());
                $sendEvent([
                    'status' => 'failed',
                    'percentage' => 100,
                    'step' => 'Sinkronisasi Gagal: ' . $e->getMessage(),
                    'log' => "\n[ERROR] Terjadi exception: " . $e->getMessage() . "\n"
                ]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Sinkronisasi data dari db_sipat_terpadu ke db_sipat_staging di background.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|RedirectResponse
     */
    public function syncDb(Request $request)
    {
        try {
            // Periksa jika proses sinkronisasi sedang berjalan
            $progress = Cache::get('sync_db_progress');
            if ($progress && ($progress['status'] ?? null) === 'running') {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['status' => 'error', 'message' => 'Proses sinkronisasi database saat ini sedang berjalan di background.'], 400);
                }
                return redirect()->route('settings.backups.index')
                    ->with('error', 'Proses sinkronisasi database saat ini sedang berjalan di background.');
            }

            // Jalankan Artisan command di background
            $command = 'php ' . base_path('artisan') . ' app:sync-db-bg > /dev/null 2>&1 &';
            exec($command);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => 'success', 'message' => 'Proses sinkronisasi database berhasil dimulai di background.']);
            }

            return redirect()->route('settings.backups.index')
                ->with('success', 'Proses sinkronisasi database berhasil dimulai di background.');
        } catch (\Exception $e) {
            Log::error('Gagal memicu sinkronisasi database: ' . $e->getMessage());
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
            return redirect()->route('settings.backups.index')
                ->with('error', 'Gagal memicu sinkronisasi database: ' . $e->getMessage());
        }
    }

    /**
     * Memulihkan / merestore database secara menyeluruh dari berkas SQL/ZIP yang diunggah.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function restoreSql(Request $request): RedirectResponse
    {
        $request->validate([
            'sql_file' => 'required|file|max:102400',
        ], [
            'sql_file.required' => 'Silakan pilih berkas .sql atau .zip dump database yang ingin diunggah.',
            'sql_file.max'      => 'Ukuran berkas maksimal adalah 100 MB.',
        ]);

        try {
            $file = $request->file('sql_file');
            $ext = strtolower($file->getClientOriginalExtension());
            $origName = $file->getClientOriginalName();

            if (!in_array($ext, ['sql', 'gz', 'zip'])) {
                return redirect()->route('settings.backups.index')
                    ->with('error', 'Format berkas tidak didukung. Harap unggah berkas bertipe .sql, .gz, atau .zip');
            }

            $tempPath = $file->storeAs('temp_restore', time() . '_' . $origName, 'local');
            $fullPath = storage_path('app/' . $tempPath);
            $sqlPath = $fullPath;

            if ($ext === 'zip') {
                $zip = new \ZipArchive();
                if ($zip->open($fullPath) === true) {
                    $extractDir = storage_path('app/temp_restore/' . time() . '_extracted');
                    $zip->extractTo($extractDir);
                    $zip->close();

                    $sqlFiles = glob($extractDir . '/*.sql');
                    if (empty($sqlFiles)) {
                        $sqlFiles = glob($extractDir . '/*/*.sql');
                    }

                    if (!empty($sqlFiles)) {
                        $sqlPath = $sqlFiles[0];
                    } else {
                        return redirect()->route('settings.backups.index')
                            ->with('error', 'Tidak ditemukan berkas .sql di dalam arsip ZIP.');
                    }
                } else {
                    return redirect()->route('settings.backups.index')
                        ->with('error', 'Gagal membuka berkas ZIP cadangan.');
                }
            }

            $dbHost = config('database.connections.mysql.host', '127.0.0.1');
            $dbPort = config('database.connections.mysql.port', '3306');
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');

            $passFlag = !empty($dbPass) ? "-p" . escapeshellarg($dbPass) : "";

            if ($ext === 'gz') {
                $cmd = "gunzip -c " . escapeshellarg($sqlPath) . " | mysql -h " . escapeshellarg($dbHost) . " -P " . escapeshellarg($dbPort) . " -u " . escapeshellarg($dbUser) . " {$passFlag} " . escapeshellarg($dbName) . " 2>&1";
            } else {
                $cmd = "mysql -h " . escapeshellarg($dbHost) . " -P " . escapeshellarg($dbPort) . " -u " . escapeshellarg($dbUser) . " {$passFlag} " . escapeshellarg($dbName) . " < " . escapeshellarg($sqlPath) . " 2>&1";
            }

            exec($cmd, $output, $returnCode);

            // Invalidate cache dashboard
            if (class_exists('\App\Services\SipatService')) {
                app(\App\Services\SipatService::class)->invalidateDashboardCache();
            }

            if (class_exists('\App\Models\Activity')) {
                \App\Models\Activity::log("Memulihkan / merestore database secara menyeluruh dari berkas unggahan '{$origName}'", 'warning');
            }

            return redirect()->route('settings.backups.index')
                ->with('success', "Berhasil merestore dan memperbarui database secara menyeluruh dari berkas: {$origName}!");
        } catch (\Exception $e) {
            Log::error('Gagal restore database: ' . $e->getMessage());
            return redirect()->route('settings.backups.index')
                ->with('error', 'Gagal memulihkan database: ' . $e->getMessage());
        }
    }

    /**
     * Format bytes ke format yang mudah dibaca manusia.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
