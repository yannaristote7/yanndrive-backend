<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Domain;
use Illuminate\Http\Request;

/**
 * Domaines email autorisés à s'inscrire.
 */
class DomainController extends Controller
{
    public function index()
    {
        return response()->json(Domain::orderBy('domain')->get());
    }

    public function store(Request $request)
    {
        $request->merge(['domain' => strtolower(trim((string) $request->input('domain')))]);

        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:255', 'regex:/^(?!-)[a-z0-9-]+(\.[a-z0-9-]+)+$/', 'unique:domains,domain'],
        ], [
            'domain.regex' => 'Format de domaine invalide (ex. exemple.com).',
        ]);

        $domain = Domain::create($validated);

        $this->log($request, 'admin_domain_added', "Ajout du domaine {$domain->domain}");

        return response()->json($domain, 201);
    }

    public function destroy(Request $request, Domain $domain)
    {
        $domain->delete();

        $this->log($request, 'admin_domain_removed', "Suppression du domaine {$domain->domain}");

        return response()->json(['message' => 'Domaine supprimé']);
    }

    private function log(Request $request, string $action, string $description): void
    {
        ActivityLog::create([
            'user_id'     => $request->user()->id,
            'action'      => $action,
            'description' => $description,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'success'     => true,
        ]);
    }
}
