<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\Domain;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // 1️⃣ Validation
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed', // password_confirmation requis
        ]);

        // 2️⃣ Vérification du domaine
        $domain = substr(strrchr($validated['email'], "@"), 1);

        if (!Domain::where('domain', $domain)->exists()) {
            return response()->json([
                'message' => 'Domaine non autorisé'
            ], 422);
        }

        // 3️⃣ Récupération sécurisée du rôle "user"
        $role = Role::where('name', 'user')->first();
        if (!$role) {
            return response()->json([
                'message' => 'Rôle utilisateur introuvable'
            ], 500);
        }

        // 4️⃣ Création de l'utilisateur
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $role->id
        ]);

        // 5️⃣ Génération token Sanctum
        $token = $user->createToken('spa-token')->plainTextToken;

        // 6️⃣ Retour JSON
        return response()->json([
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Identifiants invalides'
            ], 401);
        }

        $user = User::where('email', $request->email)->first();
        $token = $user->createToken('spa-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie'
        ]);
    }
}