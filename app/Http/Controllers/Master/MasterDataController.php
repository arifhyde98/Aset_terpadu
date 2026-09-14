<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;


/**
 * Controller untuk Hub Master Data
 * 
 * Berfungsi sebagai pusat navigasi dan ringkasan statistik untuk berbagai modul master data.
 */
class MasterDataController extends Controller implements HasMiddleware
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
            new Middleware('role:superadmin,admin'),
        ];
    }

    /**
     * Menampilkan halaman utama Hub Master Data dengan ringkasan statistik.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $stats = [
            'total_kendaraan' => Vehicle::count(),
            'total_pengguna' => User::count(),
            'total_opd' => \App\Models\Opd::count(),
            'total_sopir' => 12, // Masih statis sesuai kebutuhan tampilan
        ];

        return view('master-data.index', compact('stats'));
    }
}
