<?php

namespace App\Http\Controllers\Elabel;

use App\Http\Controllers\Controller;
use App\Models\Elabel\ElabelActivityLog;
use App\Models\Elabel\ElabelSuratPenyerahan;
use App\Models\Elabel\ElabelSuratPenyerahanBox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ElabelSuratPenyerahanBoxController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request): View
    {
        $query = trim((string) $request->get('q'));
        $builder = ElabelSuratPenyerahanBox::with('creator')
            ->withCount('surats');

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('box_code', 'LIKE', "%{$query}%")
                  ->orWhere('lokasi', 'LIKE', "%{$query}%");
            });
        }

        $items = $builder->orderBy('id', 'desc')->get();

        return view('elabel.surat_penyerahan_boxes.index', [
            'items'       => $items,
            'searchQuery' => $query,
            'activeMenu'  => 'surat_penyerahan_boxes',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'box_code' => ['required', 'string', 'max:30', 'unique:elabel_surat_penyerahan_boxes,box_code'],
            'lokasi'   => ['required', 'string', 'max:255'],
        ]);

        $box = ElabelSuratPenyerahanBox::create([
            'box_code'   => strtoupper(trim($request->get('box_code'))),
            'lokasi'     => trim($request->get('lokasi')),
            'created_by' => Auth::id() ?: 1,
        ]);

        ElabelActivityLog::create([
            'user_id'        => Auth::id() ?: 1,
            'action'         => 'create',
            'module'         => 'Box Surat Penyerahan',
            'description'    => 'Menambahkan Box Surat Penyerahan ' . $box->box_code . '.',
            'reference_type' => 'elabel_surat_penyerahan_boxes',
            'reference_id'   => $box->id,
            'created_at'     => now(),
        ]);

        return redirect()->route('elabel.surat-penyerahan-boxes.index')->with('success', 'Box Surat Penyerahan berhasil ditambahkan.');
    }

    public function show(int $id): View|RedirectResponse
    {
        $box = ElabelSuratPenyerahanBox::with(['creator', 'surats'])->find($id);
        if (!$box) {
            return redirect()->route('elabel.surat-penyerahan-boxes.index')->with('error', 'Box Surat Penyerahan tidak ditemukan.');
        }

        return view('elabel.surat_penyerahan_boxes.show', [
            'box'        => $box,
            'activeMenu' => 'surat_penyerahan_boxes',
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $box = ElabelSuratPenyerahanBox::find($id);
        if (!$box) {
            return redirect()->route('elabel.surat-penyerahan-boxes.index')->with('error', 'Box Surat Penyerahan tidak ditemukan.');
        }

        $count = ElabelSuratPenyerahan::where('box_id', $id)->count();
        if ($count > 0) {
            return redirect()->back()->with('error', 'Box Surat Penyerahan tidak dapat dihapus karena masih terisi ' . $count . ' data surat.');
        }

        $boxCode = $box->box_code;
        $box->delete();

        ElabelActivityLog::create([
            'user_id'     => Auth::id() ?: 1,
            'action'      => 'delete',
            'module'      => 'Box Surat Penyerahan',
            'description' => 'Menghapus Box Surat Penyerahan ' . $boxCode . '.',
            'created_at'  => now(),
        ]);

        return redirect()->route('elabel.surat-penyerahan-boxes.index')->with('success', 'Box Surat Penyerahan berhasil dihapus.');
    }
}
