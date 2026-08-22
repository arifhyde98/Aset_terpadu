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

    public function index()
    {
        $statusProses = StatusProses::orderBy('urutan', 'asc')->get();
        return view('master.status_proses.index', compact('statusProses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_status' => 'required|string|max:100',
            'urutan' => 'required|integer',
            'warna' => 'nullable|string|max:30',
            'kategori' => 'nullable|string|max:50',
        ]);

        StatusProses::create($request->all());

        return redirect()->route('status-proses.index')->with('success', 'Status Proses berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $status = StatusProses::findOrFail($id);

        $request->validate([
            'nama_status' => 'required|string|max:100',
            'urutan' => 'required|integer',
            'warna' => 'nullable|string|max:30',
            'kategori' => 'nullable|string|max:50',
        ]);

        $status->update($request->all());

        return redirect()->route('status-proses.index')->with('success', 'Status Proses berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $status = StatusProses::findOrFail($id);
        $status->delete();

        return redirect()->route('status-proses.index')->with('success', 'Status Proses berhasil dihapus.');
    }
}
