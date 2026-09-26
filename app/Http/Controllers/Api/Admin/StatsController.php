<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Domain;
use App\Models\User;

/**
 * Chiffres clés pour la vue d'ensemble du panneau admin.
 */
class StatsController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            'users'          => User::count(),
            'admins'         => User::whereHas('role', fn ($q) => $q->where('name', 'admin'))->count(),
            'new_users_7d'   => User::where('created_at', '>=', now()->subDays(7))->count(),
            'documents'      => Document::count(),
            'storage_used'   => (int) Document::sum('size'),
            'domains'        => Domain::count(),
            'logs_failed_7d' => ActivityLog::where('success', false)->where('created_at', '>=', now()->subDays(7))->count(),
            // whereHas : évite les sommes NULL, triées en tête par PostgreSQL en DESC
            'top_users'      => User::whereHas('documents')
                ->withSum('documents', 'size')
                ->withCount('documents')
                ->orderByDesc('documents_sum_size')
                ->limit(5)
                ->get(['id', 'name', 'email']),
            'recent_logs'    => ActivityLog::with('user:id,name')->latest()->limit(8)->get(),
        ]);
    }
}
