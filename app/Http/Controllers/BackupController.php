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
     * Sinkronisasi data dari db_sipat_terpadu ke db_sipat_staging (Khusus Lingkungan Staging/Lokal).
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function syncDb(Request $request): RedirectResponse
    {
        try {
            $command = "mysqldump -u bpkad.aset -padmin123 --no-tablespaces --no-create-info --complete-insert --insert-ignore db_sipat_terpadu | mysql --force -u bpkad.aset -padmin123 db_sipat_staging 2>&1";
            exec($command, $output, $returnCode);

            if (class_exists('\App\Models\Activity')) {
                \App\Models\Activity::log("Menyinkronkan data dari db_sipat_terpadu ke db_sipat_staging", 'info');
            }

            return redirect()->route('settings.backups.index')
                ->with('success', 'Berhasil menyinkronkan seluruh data dari database SIPAT Terpadu!');
        } catch (\Exception $e) {
            Log::error('Gagal sinkronisasi database staging: ' . $e->getMessage());
            return redirect()->route('settings.backups.index')
                ->with('error', 'Gagal menyinkronkan database: ' . $e->getMessage());
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
