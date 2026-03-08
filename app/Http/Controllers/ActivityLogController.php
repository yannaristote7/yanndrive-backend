<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LISTE DES LOGS (ADMIN)
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        // Sécurité : admin seulement
        if ($request->user()->role->name !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $logs = ActivityLog::with('user')
            ->latest()
            ->paginate(20);

        return response()->json($logs);
    }

    /*
    |--------------------------------------------------------------------------
    | STATISTIQUES
    |--------------------------------------------------------------------------
    */

    public function stats(Request $request)
    {
        if ($request->user()->role->name !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'total_logs' => ActivityLog::count(),
            'success_logs' => ActivityLog::where('success', true)->count(),
            'failed_logs' => ActivityLog::where('success', false)->count(),
        ]);
    }
}