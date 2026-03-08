<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Domain;
use Symfony\Component\HttpFoundation\Response;

class CheckAllowedDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
{
    $email = $request->input('email');

    if (!$email) {
        return response()->json([
            'message' => 'Email requis'
        ], 422);
    }

    $domain = strtolower(substr(strrchr($email, "@"), 1));
    $allowed = Domain::where('domain', $domain)->exists();

    if (!$allowed) {
        return response()->json([
            'message' => 'Domaine non autorisé'
        ], 403);
    }

    return $next($request);
}

}
