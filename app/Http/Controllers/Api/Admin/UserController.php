<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Gestion des utilisateurs par l'administrateur.
 * Toutes les routes sont protégées par le Gate "admin".
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');

        $users = User::with('role')
            ->withCount(['documents', 'tokens'])
            ->withSum('documents', 'size')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($request->get('role'), fn ($q, $role) => $q->whereHas('role', fn ($q) => $q->where('name', $role)))
            ->orderBy('name')
            ->paginate(min((int) $request->get('per_page', 15), 100));

        return response()->json($users);
    }

    public function roles()
    {
        return response()->json(Role::orderBy('name')->get(['id', 'name']));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'role_id'  => 'required|exists:roles,id',
        ]);

        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);

        $this->log($request, 'admin_user_created', "Création de {$user->email}", $user);

        return response()->json($user->load('role'), 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'sometimes|required|string|max:255',
            'email'    => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'nullable', Password::min(8)->letters()->numbers()],
            'role_id'  => 'sometimes|required|exists:roles,id',
        ]);

        // Un admin ne peut pas se retirer ses propres droits (évite de perdre tout accès admin)
        if ($user->is($request->user()) && isset($validated['role_id']) && (int) $validated['role_id'] !== $user->role_id) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier votre propre rôle.'], 422);
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        // Changement de mot de passe ou de rôle : on invalide les sessions existantes
        if (isset($validated['password']) || $user->wasChanged('role_id')) {
            $user->tokens()->delete();
        }

        $this->log($request, 'admin_user_updated', "Modification de {$user->email} (" . implode(', ', array_keys($user->getChanges())) . ')', $user);

        return response()->json($user->load('role'));
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        // Les lignes documents partent en cascade, mais pas les fichiers physiques
        Storage::delete($user->documents()->pluck('path')->all());

        $email = $user->email;
        $user->tokens()->delete();
        $user->delete();

        $this->log($request, 'admin_user_deleted', "Suppression de {$email}");

        return response()->json(['message' => 'Utilisateur supprimé']);
    }

    /**
     * Déconnecte l'utilisateur de toutes ses sessions (tokens Sanctum).
     */
    public function revokeTokens(Request $request, User $user)
    {
        $count = $user->tokens()->delete();

        $this->log($request, 'admin_tokens_revoked', "{$count} session(s) révoquée(s) pour {$user->email}", $user);

        return response()->json(['message' => "{$count} session(s) révoquée(s)"]);
    }

    private function log(Request $request, string $action, string $description, ?User $target = null): void
    {
        ActivityLog::create([
            'user_id'       => $request->user()->id,
            'action'        => $action,
            'description'   => $description,
            'ip_address'    => $request->ip(),
            'user_agent'    => $request->userAgent(),
            'success'       => true,
            'loggable_id'   => $target?->id,
            'loggable_type' => $target ? User::class : null,
        ]);
    }
}
