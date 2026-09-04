<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Elabel\ElabelActivityLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ActivityController extends Controller implements HasMiddleware
{
    /**
     * Mendapatkan middleware yang ditugaskan ke controller ini.
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
     * Menampilkan daftar riwayat aktivitas sistem.
     * 
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $selectedModule = $request->get('module', 'all');
        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();

        $systemLogs = Activity::with('user')->latest()->get()->map(function ($activity) {
            $moduleKey = $this->resolveModuleKey($activity);
            
            return (object) [
                'source' => 'activities',
                'id' => $activity->id,
                'module_key' => $moduleKey,
                'module_label' => $this->moduleLabel($moduleKey),
                'type' => $activity->type,
                'description' => $activity->description,
                'user' => $activity->user,
                'created_at' => $activity->created_at,
                'before_data' => json_decode($activity->old_data, true) ?: null,
                'after_data' => json_decode($activity->new_data, true) ?: null,
            ];
        });

        $elabelLogs = collect();

        if (Schema::hasTable('elabel_activity_logs')) {
            $elabelLogs = ElabelActivityLog::with('user')->latest()->get()->map(function ($log) {
                return (object) [
                    'source' => 'elabel_activity_logs',
                    'id' => $log->id,
                    'module_key' => 'elabel',
                    'module_label' => 'eLABEL',
                    'type' => $this->typeFromAction((string) $log->action),
                    'description' => $log->description,
                    'user' => $log->user,
                    'created_at' => $log->created_at,
                    'before_data' => !empty($log->old_data) ? (json_decode($log->old_data, true) ?: null) : null,
                    'after_data' => !empty($log->new_data) ? (json_decode($log->new_data, true) ?: null) : null,
                ];
            });
        }

        $sipatAuditLogs = collect();

        if (Schema::hasTable('audit_logs')) {
            $sipatAuditLogs = DB::table('audit_logs')
                ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
                ->select('audit_logs.*', 'users.name as user_name', 'users.email as user_email')
                ->latest('audit_logs.created_at')
                ->get()
                ->map(function ($log) {
                    return (object) [
                        'source' => 'audit_logs',
                        'id' => $log->id,
                        'module_key' => 'sipat',
                        'module_label' => 'SIPAT',
                        'type' => $this->typeFromAction((string) $log->action),
                        'description' => $this->auditLogDescription($log),
                        'user' => (object) [
                            'name' => $log->user_name,
                            'email' => $log->user_email,
                        ],
                        'created_at' => Carbon::parse($log->created_at),
                        'before_data' => json_decode($log->old_data, true) ?: null,
                        'after_data' => json_decode($log->new_data, true) ?: null,
                        'audit_action' => $log->action,
                        'audit_entity' => $log->entity,
                        'audit_entity_id' => $log->entity_id,
                    ];
                });
        }

        $allLogs = $systemLogs->concat($elabelLogs)->concat($sipatAuditLogs)
            ->when($selectedModule !== 'all', fn ($logs) => $logs->where('module_key', $selectedModule))
            ->sortByDesc('created_at')
            ->values();

        $activities = new LengthAwarePaginator(
            $allLogs->forPage($page, $perPage)->values(),
            $allLogs->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $modules = [
            'all' => 'Semua Modul',
            'erandis' => 'E-RANDIS',
            'sipat' => 'SIPAT',
            'elabel' => 'eLABEL',
        ];

        return view('activities.index', compact('activities', 'modules', 'selectedModule'));
    }

    /**
     * Menghapus riwayat aktivitas dari database berdasarkan modul yang dipilih.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clear(Request $request)
    {
        $modules = $request->input('modules', []);
        $cleared = [];

        // 1. E-RANDIS
        if (isset($modules['erandis']) && $modules['erandis'] == '1') {
            Activity::truncate();
            $cleared[] = 'E-RANDIS';
        }

        // 2. eLABEL
        if (isset($modules['elabel']) && $modules['elabel'] == '1') {
            if (Schema::hasTable('elabel_activity_logs')) {
                ElabelActivityLog::truncate();
                $cleared[] = 'eLABEL';
            }
        }

        // 3. SIPAT
        if (isset($modules['sipat']) && $modules['sipat'] == '1') {
            if (Schema::hasTable('audit_logs')) {
                DB::table('audit_logs')->truncate();
                $cleared[] = 'SIPAT';
            }
        }

        if (empty($cleared)) {
            return redirect()->back()->with('info', 'Tidak ada modul log aktivitas yang dipilih untuk dibersihkan.');
        }

        $modulesStr = implode(', ', $cleared);
        return redirect()->back()->with('success', "Riwayat aktivitas untuk modul [{$modulesStr}] berhasil dibersihkan.");
    }

    private function moduleLabel(string $moduleKey): string
    {
        return match ($moduleKey) {
            'sipat' => 'SIPAT',
            'elabel' => 'eLABEL',
            default => 'E-RANDIS',
        };
    }

    private function resolveModuleKey(Activity $activity): string
    {
        $moduleKey = Schema::hasColumn('activities', 'module_key')
            ? ($activity->module_key ?: 'erandis')
            : 'erandis';

        if ($moduleKey === 'erandis' && $this->looksLikeSipatActivity((string) $activity->description)) {
            return 'sipat';
        }

        return $moduleKey;
    }

    private function looksLikeSipatActivity(string $description): bool
    {
        $description = strtolower($description);

        return str_contains($description, 'sipat')
            || str_contains($description, 'aset tanah')
            || str_contains($description, 'sertifikat');
    }

    private function auditLogDescription(object $log): string
    {
        $action = strtolower((string) $log->action);
        $entity = strtolower((string) $log->entity);
        $id = $log->entity_id;
        
        // Ekstrak JSON
        $newData = json_decode($log->new_data, true) ?? [];
        $oldData = json_decode($log->old_data, true) ?? [];
        
        // Prioritaskan newData untuk create/update, oldData untuk delete
        $data = in_array($action, ['delete', 'destroy']) ? $oldData : $newData;
        
        // Ambil konteks spesifik dari data yang diekstrak
        $context = "";
        if (!empty($data)) {
            if ($entity === 'aset_tanah' && isset($data['nama_aset'])) {
                $context = ": " . $data['nama_aset'];
            } elseif ($entity === 'proses_aset' && isset($data['id_aset'])) {
                $context = " untuk Aset ID " . $data['id_aset'];
            } elseif ($entity === 'dokumen_aset' && isset($data['jenis_dokumen'])) {
                $context = " berupa " . $data['jenis_dokumen'];
            } elseif ($entity === 'opd' && isset($data['nama'])) {
                $context = ": " . $data['nama'];
            }
        }
        
        $actionText = match($action) {
            'create', 'insert' => 'Menambahkan data',
            'update', 'edit' => 'Memperbarui data',
            'delete', 'destroy' => 'Menghapus data',
            default => 'Memodifikasi',
        };
        
        $entityText = match($entity) {
            'aset_tanah' => 'aset tanah',
            'proses_aset' => 'riwayat proses BPN',
            'dokumen_aset' => 'dokumen lampiran aset',
            'pengamanan_fisik' => 'laporan pengamanan lapangan',
            'status_proses' => 'master data status BPN',
            'opd' => 'master data OPD',
            'users' => 'akun pengguna',
            default => str_replace('_', ' ', $entity),
        };

        return "{$actionText} {$entityText}{$context} [Data Lama ID: {$id}]";
    }

    private function typeFromAction(string $action): string
    {
        return match ($action) {
            'create', 'approve', 'restore', 'import' => 'success',
            'delete', 'destroy', 'reject' => 'danger',
            'update', 'merge', 'split' => 'warning',
            default => 'info',
        };
    }
}
