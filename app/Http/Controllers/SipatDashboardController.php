<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SipatService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SipatDashboardController extends Controller implements HasMiddleware
{
    protected $sipatService;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function __construct(SipatService $sipatService)
    {
        $this->sipatService = $sipatService;
    }

    public function index()
    {
        $data = $this->sipatService->getDashboardStats();

        if (request()->ajax()) {
            return response()->json($data);
        }

        return view('sipat.dashboard', $data);
    }

    public function realtimeStats()
    {
        return response()->json($this->sipatService->getDashboardStats());
    }
}
