<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class AuditLogsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request)
    {
        $query = DB::table('audit_logs')
            ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
            ->select('audit_logs.*', 'users.name as user_name', 'users.email as user_email');

        if ($request->filled('action')) {
            $query->where('audit_logs.action', $request->action);
        }

        if ($request->filled('entity')) {
            $query->where('audit_logs.entity', $request->entity);
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('users.name', 'LIKE', $search)
                  ->orWhere('audit_logs.entity', 'LIKE', $search)
                  ->orWhere('audit_logs.action', 'LIKE', $search)
                  ->orWhere('audit_logs.old_data', 'LIKE', $search)
                  ->orWhere('audit_logs.new_data', 'LIKE', $search);
            });
        }

        $logs = $query->orderBy('audit_logs.id', 'desc')->paginate(20)->withQueryString();

        $entities = DB::table('audit_logs')->distinct()->pluck('entity');
        $actions  = DB::table('audit_logs')->distinct()->pluck('action');

        return view('master.logs.index', compact('logs', 'entities', 'actions'));
    }

    public function show($id)
    {
        $log = DB::table('audit_logs')
            ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
            ->select('audit_logs.*', 'users.name as user_name', 'users.email as user_email')
            ->where('audit_logs.id', $id)
            ->first();

        if (!$log) {
            return response()->json(['message' => 'Log tidak ditemukan'], 404);
        }

        return response()->json([
            'id' => $log->id,
            'user' => $log->user_name ?? 'Sistem',
            'action' => strtoupper($log->action),
            'entity' => $log->entity,
            'entity_id' => $log->entity_id,
            'ip_address' => $log->ip_address,
            'created_at' => $log->created_at,
            'old_data' => json_decode($log->old_data, true),
            'new_data' => json_decode($log->new_data, true),
        ]);
    }
}
