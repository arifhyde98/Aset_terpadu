<?php

namespace App\Http\Controllers;

use App\Models\OpdSipat;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class MasterSipatOpdController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request)
    {
        $query = OpdSipat::query();

        if ($request->filled('q')) {
            $search = '%' . $request->q . '%';
            $query->where('nama', 'LIKE', $search);
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('aktif', (int)$request->status);
        }

        $opdList = $query->orderBy('aktif', 'desc')->orderBy('nama', 'asc')->get();

        return view('master.opd_sipat.index', compact('opdList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:150|unique:opd,nama',
        ], [
            'nama.required' => 'Nama OPD / Instansi wajib diisi.',
            'nama.unique' => 'Nama OPD tersebut sudah ada di database.',
        ]);

        $opd = OpdSipat::create([
            'nama' => trim($request->nama),
            'aktif' => $request->has('aktif') ? 1 : 1,
        ]);

        $this->logAudit('create', 'opd', $opd->id, [], $opd->toArray());

        return redirect()->route('opd-sipat.index')->with('success', 'Master OPD SIPAT berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $opd = OpdSipat::findOrFail($id);
        $oldData = $opd->toArray();

        $request->validate([
            'nama' => 'required|string|max:150|unique:opd,nama,' . $id,
        ], [
            'nama.required' => 'Nama OPD / Instansi wajib diisi.',
            'nama.unique' => 'Nama OPD tersebut sudah digunakan.',
        ]);

        $opd->update([
            'nama' => trim($request->nama),
            'aktif' => $request->has('aktif') ? 1 : 0,
        ]);

        $this->logAudit('update', 'opd', $opd->id, $oldData, $opd->toArray());

        return redirect()->route('opd-sipat.index')->with('success', 'Master OPD SIPAT berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $opd = OpdSipat::findOrFail($id);
        $oldData = $opd->toArray();

        $opd->delete();

        $this->logAudit('delete', 'opd', $id, $oldData, []);

        return redirect()->route('opd-sipat.index')->with('success', 'Master OPD SIPAT berhasil dihapus.');
    }

    private function logAudit(string $action, string $entity, int $entityId, array $oldData = [], array $newData = []): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('audit_logs')) return;

        try {
            DB::table('audit_logs')->insert([
                'user_id' => auth()->id() ?? 1,
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entityId,
                'old_data' => !empty($oldData) ? json_encode($oldData) : null,
                'new_data' => !empty($newData) ? json_encode($newData) : null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Ignore audit log failure
        }
    }
}
