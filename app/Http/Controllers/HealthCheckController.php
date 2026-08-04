<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class HealthCheckController extends Controller
{
    /**
     * Endpoint untuk mengecek status kesehatan proyek (Spoke).
     */
    public function check(): JsonResponse
    {
        $status = 'healthy';
        $dbStatus = 'connected';
        
        try {
            DB::connection()->getPdo();
        } catch (Exception $e) {
            $status = 'unhealthy';
            $dbStatus = 'disconnected: ' . $e->getMessage();
        }

        return response()->json([
            'project' => 'E-RANDIS',
            'framework' => 'Laravel ' . app()->version(),
            'status' => $status,
            'database' => $dbStatus,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
