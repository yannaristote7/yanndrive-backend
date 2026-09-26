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
        // Sécurité : admin seulement (Gate "admin" appliqué sur la route)
        $search = $request->get('search');

        $logs = ActivityLog::with('user')
            ->when($request->filled('success'), fn ($q) => $q->where('success', $request->boolean('success')))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('action', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"))))
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
        return response()->json([
            'total_logs' => ActivityLog::count(),
            'success_logs' => ActivityLog::where('success', true)->count(),
            'failed_logs' => ActivityLog::where('success', false)->count(),
        ]);
    }
}