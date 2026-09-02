<?php

namespace App\Http\Controllers;

use App\Models\StatusProses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

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
        $allStatus = StatusProses::orderBy('urutan', 'asc')->get();

        $selectedCategory = $request->input('kategori');
        if (!empty($selectedCategory)) {
            $statusProses = $allStatus->filter(function ($status) use ($selectedCategory) {
                return $status->hasCategory($selectedCategory);
            })->values();
        } else {
            $statusProses = $allStatus;
        }

        // Count items per category
        $counts = [
            'total'         => $allStatus->count(),
            'belum_diurus'  => $allStatus->filter(fn($s) => $s->hasCategory('belum_diurus'))->count(),
            'proses'        => $allStatus->filter(fn($s) => $s->hasCategory('proses'))->count(),
            'bersertifikat' => $allStatus->filter(fn($s) => $s->hasCategory('bersertifikat'))->count(),
            'kendala'       => $allStatus->filter(fn($s) => $s->hasCategory('kendala'))->count(),
        ];

        // Gather any additional custom categories from database
        $customCategories = [];
        foreach ($allStatus as $st) {
            foreach ($st->categories as $cat) {
                $cat = strtolower(trim($cat));
                if (!empty($cat) && !in_array($cat, ['bersertifikat', 'proses', 'belum_diurus', 'kendala'], true)) {
                    $customCategories[$cat] = ($customCategories[$cat] ?? 0) + 1;
                }
            }
        }

        foreach ($customCategories as $customKat => $cnt) {
            $counts[$customKat] = $cnt;
        }

        $customCategoryKeys = array_keys($customCategories);

        return view('master.status_proses.index', compact('statusProses', 'counts', 'customCategoryKeys'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_status'     => 'required|string|max:100',
            'urutan'          => 'nullable|integer',
            'warna'           => 'nullable|string|max:30',
            'kategori'        => 'nullable|array',
            'custom_kategori' => 'nullable|string|max:100',
        ]);

        $categories = (array) $request->input('kategori', []);

        // Tambah custom category jika diinput
        if ($request->filled('custom_kategori')) {
            $customParts = explode(',', $request->input('custom_kategori'));
            foreach ($customParts as $cp) {
                $cleaned = Str::slug(trim($cp), '_');
                if (!empty($cleaned) && !in_array($cleaned, $categories, true)) {
                    $categories[] = $cleaned;
                }
            }
        }

        // Jika tidak ada kategori yang dipilih, default ke 'proses'
        if (empty($categories)) {
            $categories = ['proses'];
        }

        $urutan = $request->input('urutan');
        if (empty($urutan)) {
            $urutan = (StatusProses::max('urutan') ?? 0) + 1;
        }

        StatusProses::create([
            'nama_status' => $request->input('nama_status'),
            'urutan'      => $urutan,
            'warna'       => $request->input('warna', 'primary'),
            'kategori'    => implode(',', array_unique($categories)),
        ]);

        Cache::forget('sipat_dashboard_stats');

        return redirect()->route('status-proses.index')->with('success', 'Status Proses berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $status = StatusProses::findOrFail($id);

        $request->validate([
            'nama_status'     => 'required|string|max:100',
            'urutan'          => 'nullable|integer',
            'warna'           => 'nullable|string|max:30',
            'kategori'        => 'nullable|array',
            'custom_kategori' => 'nullable|string|max:100',
        ]);

        $categories = (array) $request->input('kategori', []);

        // Tambah custom category jika diinput
        if ($request->filled('custom_kategori')) {
            $customParts = explode(',', $request->input('custom_kategori'));
            foreach ($customParts as $cp) {
                $cleaned = Str::slug(trim($cp), '_');
                if (!empty($cleaned) && !in_array($cleaned, $categories, true)) {
                    $categories[] = $cleaned;
                }
            }
        }

        if (empty($categories)) {
            $categories = ['proses'];
        }

        $data = [
            'nama_status' => $request->input('nama_status'),
            'warna'       => $request->input('warna', $status->warna),
            'kategori'    => implode(',', array_unique($categories)),
        ];

        if ($request->filled('urutan')) {
            $data['urutan'] = (int) $request->input('urutan');
        }

        $status->update($data);

        Cache::forget('sipat_dashboard_stats');

        return redirect()->route('status-proses.index')->with('success', 'Status Proses berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $status = StatusProses::findOrFail($id);
        $status->delete();

        Cache::forget('sipat_dashboard_stats');

        return redirect()->route('status-proses.index')->with('success', 'Status Proses berhasil dihapus.');
    }
}
