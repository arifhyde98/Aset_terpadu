<?php

namespace App\Http\Controllers;

use App\Models\StatusProses;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class StatusProsesController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request)
    {
        $query = StatusProses::orderBy('urutan', 'asc');

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $statusProses = $query->get();

        $allStatus = StatusProses::all();
        
        $counts = [
            'total' => $allStatus->count(),
            'belum_diurus' => $allStatus->where('kategori', 'belum_diurus')->count(),
            'proses' => $allStatus->where('kategori', 'proses')->count(),
            'bersertifikat' => $allStatus->where('kategori', 'bersertifikat')->count(),
            'kendala' => $allStatus->where('kategori', 'kendala')->count(),
        ];

        // Gather any additional custom categories from database
        $customCategories = $allStatus->pluck('kategori')
            ->filter(fn($k) => !empty($k) && !in_array($k, ['bersertifikat', 'proses', 'belum_diurus', 'kendala']))
            ->unique()
            ->values();

        foreach ($customCategories as $customKat) {
            $counts[$customKat] = $allStatus->where('kategori', $customKat)->count();
        }

        return view('master.status_proses.index', compact('statusProses', 'counts', 'customCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_status' => 'required|string|max:100',
            'urutan' => 'nullable|integer',
            'warna' => 'nullable|string|max:30',
            'kategori' => 'nullable|string|max:50',
            'custom_kategori' => 'nullable|string|max:50',
        ]);

        $data = $request->only(['nama_status', 'urutan', 'warna', 'kategori']);

        if (empty($data['urutan'])) {
            $data['urutan'] = (StatusProses::max('urutan') ?? 0) + 1;
        }

        if ($request->input('kategori') === 'CUSTOM' && $request->filled('custom_kategori')) {
            $data['kategori'] = \Str::slug($request->input('custom_kategori'), '_');
        }

        StatusProses::create($data);

        return redirect()->route('status-proses.index')->with('success', 'Status Proses berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $status = StatusProses::findOrFail($id);

        $request->validate([
            'nama_status' => 'required|string|max:100',
            'urutan' => 'nullable|integer',
            'warna' => 'nullable|string|max:30',
            'kategori' => 'nullable|string|max:50',
            'custom_kategori' => 'nullable|string|max:50',
        ]);

        $data = array_filter($request->only(['nama_status', 'urutan', 'warna', 'kategori']), fn($v) => !is_null($v));

        if ($request->input('kategori') === 'CUSTOM' && $request->filled('custom_kategori')) {
            $data['kategori'] = \Str::slug($request->input('custom_kategori'), '_');
        }

        $status->update($data);

        return redirect()->route('status-proses.index')->with('success', 'Status Proses berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $status = StatusProses::findOrFail($id);
        $status->delete();

        return redirect()->route('status-proses.index')->with('success', 'Status Proses berhasil dihapus.');
    }
}
