<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Controller warisan MasterSipatOpdController.
 * Seluruh pengelolaan Master OPD kini telah dipusatkan pada route('opds.index') (Tabel Tunggal opds).
 */
class MasterSipatOpdController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin,admin'),
        ];
    }

    public function index(Request $request)
    {
        return redirect()->route('opds.index')
            ->with('info', 'Master Data OPD kini telah terpusat dalam satu pintu untuk seluruh modul aset (Tanah, Bangunan, Kendaraan, dan Arsip).');
    }

    public function store(Request $request)
    {
        return redirect()->route('opds.index');
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('opds.index');
    }

    public function destroy($id)
    {
        return redirect()->route('opds.index');
    }
}
