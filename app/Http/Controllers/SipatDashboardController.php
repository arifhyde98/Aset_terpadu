<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SipatService;

class SipatDashboardController extends Controller
{
    protected $sipatService;

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
