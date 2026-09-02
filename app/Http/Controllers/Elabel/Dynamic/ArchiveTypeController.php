<?php

namespace App\Http\Controllers\Elabel\Dynamic;

use App\Http\Controllers\Controller;
use App\Models\Elabel\Dynamic\ArchiveType;
use App\Services\Elabel\DynamicArchiveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ArchiveTypeController extends Controller implements HasMiddleware
{
    protected DynamicArchiveService $archiveService;

    public function __construct(DynamicArchiveService $archiveService)
    {
        $this->archiveService = $archiveService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request): View
    {
        $query = trim((string) $request->get('q'));
        $builder = ArchiveType::withCount(['items', 'boxes'])->orderBy('nama', 'asc');

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('kode', 'LIKE', "%{$query}%")
                  ->orWhere('nama', 'LIKE', "%{$query}%")
                  ->orWhere('deskripsi', 'LIKE', "%{$query}%");
            });
        }

        $types = $builder->get();

        return view('elabel.dynamic.types.index', [
            'types'       => $types,
            'searchQuery' => $query,
            'activeMenu'  => 'dynamic_types',
        ]);
    }

    public function create(): View
    {
        return view('elabel.dynamic.types.create', [
            'activeMenu' => 'dynamic_types',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'kode'          => 'required|string|max:50|alpha_dash|unique:archive_types,kode',
            'nama'          => 'required|string|max:150',
            'deskripsi'     => 'nullable|string',
            'icon'          => 'nullable|string|max:100',
            'warna_badge'   => 'nullable|string|max:50',
            'schema_fields' => 'nullable|array',
        ]);

        $schemaFields = $this->sanitizeSchemaFields($request->input('schema_fields', []));

        $type = ArchiveType::create([
            'kode'          => strtoupper(trim($request->kode)),
            'nama'          => trim($request->nama),
            'deskripsi'     => $request->deskripsi,
            'icon'          => $request->icon ?: 'bi-folder2',
            'warna_badge'   => $request->warna_badge ?: 'primary',
            'schema_fields' => $schemaFields,
            'is_active'     => $request->has('is_active') ? (bool) $request->is_active : true,
        ]);

        $this->archiveService->logActivity('create', 'Master Jenis Arsip', "Membuat jenis arsip baru: {$type->kode} - {$type->nama}", 'archive_type', $type->id);

        return redirect()->route('elabel.dynamic.types.index')->with('success', "Jenis arsip '{$type->nama}' berhasil dibuat!");
    }

    public function edit(int $id): View|RedirectResponse
    {
        $type = ArchiveType::find($id);
        if (!$type) {
            return redirect()->route('elabel.dynamic.types.index')->with('error', 'Jenis arsip tidak ditemukan.');
        }

        return view('elabel.dynamic.types.edit', [
            'type'       => $type,
            'activeMenu' => 'dynamic_types',
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $type = ArchiveType::find($id);
        if (!$type) {
            return redirect()->route('elabel.dynamic.types.index')->with('error', 'Jenis arsip tidak ditemukan.');
        }

        $request->validate([
            'kode'          => "required|string|max:50|alpha_dash|unique:archive_types,kode,{$id}",
            'nama'          => 'required|string|max:150',
            'deskripsi'     => 'nullable|string',
            'icon'          => 'nullable|string|max:100',
            'warna_badge'   => 'nullable|string|max:50',
            'schema_fields' => 'nullable|array',
        ]);

        $schemaFields = $this->sanitizeSchemaFields($request->input('schema_fields', []));

        $type->update([
            'kode'          => strtoupper(trim($request->kode)),
            'nama'          => trim($request->nama),
            'deskripsi'     => $request->deskripsi,
            'icon'          => $request->icon ?: 'bi-folder2',
            'warna_badge'   => $request->warna_badge ?: 'primary',
            'schema_fields' => $schemaFields,
            'is_active'     => $request->has('is_active') ? (bool) $request->is_active : true,
        ]);

        $this->archiveService->logActivity('update', 'Master Jenis Arsip', "Memperbarui jenis arsip: {$type->kode} - {$type->nama}", 'archive_type', $type->id);

        return redirect()->route('elabel.dynamic.types.index')->with('success', "Jenis arsip '{$type->nama}' berhasil diperbarui!");
    }

    public function destroy(int $id): RedirectResponse
    {
        $type = ArchiveType::withCount('items')->find($id);
        if (!$type) {
            return redirect()->route('elabel.dynamic.types.index')->with('error', 'Jenis arsip tidak ditemukan.');
        }

        if ($type->items_count > 0) {
            return redirect()->route('elabel.dynamic.types.index')->with('error', "Gagal menghapus jenis arsip '{$type->nama}' karena masih memiliki {$type->items_count} data arsip di dalamnya.");
        }

        $nama = $type->nama;
        $type->delete();

        $this->archiveService->logActivity('delete', 'Master Jenis Arsip', "Menghapus jenis arsip: {$nama}", 'archive_type', $id);

        return redirect()->route('elabel.dynamic.types.index')->with('success', "Jenis arsip '{$nama}' berhasil dihapus.");
    }

    /**
     * Sanitasi dan format array schema_fields dari form builder
     */
    protected function sanitizeSchemaFields(array $rawFields): array
    {
        $sanitized = [];
        foreach ($rawFields as $field) {
            if (empty($field['name']) || empty($field['label'])) {
                continue;
            }

            // Normalisasi name jadi snake_case bersih
            $fieldName = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower(str_replace(' ', '_', trim($field['name']))));
            
            $options = [];
            if (!empty($field['options'])) {
                if (is_array($field['options'])) {
                    $options = array_values(array_filter(array_map('trim', $field['options'])));
                } else {
                    $options = array_values(array_filter(array_map('trim', explode(',', (string) $field['options']))));
                }
            }

            $sanitized[] = [
                'name'        => $fieldName,
                'label'       => trim($field['label']),
                'type'        => in_array($field['type'] ?? 'text', ['text', 'number', 'date', 'select', 'textarea', 'file']) ? $field['type'] : 'text',
                'required'    => !empty($field['required']),
                'placeholder' => trim($field['placeholder'] ?? ''),
                'help_text'   => trim($field['help_text'] ?? ''),
                'options'     => $options,
            ];
        }

        return $sanitized;
    }
}
