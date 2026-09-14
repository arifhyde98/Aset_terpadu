<?php

namespace App\Http\Controllers;

use App\Models\OpdMapping;
use App\Models\OpdSipat;
use App\Models\Opd;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MasterOpdMappingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request)
    {
        $query = OpdMapping::with(['sipatOpd', 'erandisOpd']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('sipatOpd', function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%");
            })->orWhereHas('erandisOpd', function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%");
            });
        }

        $mappings = $query->orderBy('id', 'asc')->paginate(20)->withQueryString();
        $totalMappings = OpdMapping::count();
        $totalSipat = OpdSipat::count();
        $totalErandis = Opd::count();

        return view('master.opd_mapping.index', compact('mappings', 'totalMappings', 'totalSipat', 'totalErandis'));
    }

    public function destroy($id)
    {
        $mapping = OpdMapping::findOrFail($id);
        $mapping->delete();

        return redirect()->back()->with('success', 'Pemetaan OPD berhasil dihapus.');
    }
}
