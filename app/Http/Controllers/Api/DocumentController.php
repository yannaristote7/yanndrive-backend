<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\DocumentShare;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\Mail\ShareLinkMail;
use App\Models\ActivityLog;



class DocumentController extends Controller
{
    /**
     * Upload d’un document
     */
    public function store(Request $request)
    {
        // Validation
        $request->validate([
            'file' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png'
        ]);

        // Récupération fichier
        $file = $request->file('file');

        // Stockage dans storage/app/documents
        $path = $file->store('documents');

        // Enregistrement en base
        $document = Document::create([
            'user_id' => $request->user()->id,
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize()
        ]);

        return response()->json([
            'message' => 'Document uploadé avec succès',
            'document' => $document
        ], 201);
    }

    /**
 * Liste des documents de l'utilisateur connecté
 */
public function index(Request $request)
{
    $user = $request->user();

    // CAS ADMIN : voit tout
    if ($user->role && $user->role->name === 'admin') {

        $documents = Document::with(['user', 'sharedWith'])
            ->latest()
            ->get();

        return response()->json([
            'type' => 'admin',
            'documents' => $documents
        ]);
    }

    // CAS USER NORMAL

    // Documents possédés
    $ownedDocuments = $user->documents()
        ->with('sharedWith')
        ->latest()
        ->get();

    // Documents partagés avec lui
    $sharedDocuments = $user->sharedDocuments()
        ->with('user')
        ->latest()
        ->get();

    return response()->json([
        'type' => 'user',
        'owned_documents' => $ownedDocuments,
        'shared_documents' => $sharedDocuments
    ]);
}

/**
 * Télécharger un document
 */
public function download(Request $request, $id)
{
    $document = Document::findOrFail($id);

    $user = $request->user();

    if (
        $user->role->name !== 'admin' &&
        $document->user_id !== $user->id
    ) {
        return response()->json([
            'message' => 'Accès non autorisé'
        ], 403);
    }

    return Storage::download($document->path, $document->name);
}
/**
 * Supprimer un document
 */
public function destroy(Request $request, $id)
{
    $document = Document::findOrFail($id);

    $user = $request->user();

    if (
        $user->role->name !== 'admin' &&
        $document->user_id !== $user->id
    ) {
        return response()->json([
            'message' => 'Accès non autorisé'
        ], 403);
    }

    Storage::delete($document->path);
    $document->delete();

    return response()->json([
        'message' => 'Document supprimé'
    ]);
}
public function share(Request $request, $id)
{
    $request->validate([
        'user_id' => 'required|exists:users,id'
    ]);

    $document = Document::findOrFail($id);

    $user = $request->user();

    if ($document->user_id !== $user->id) {
        return response()->json([
            'message' => 'Seul le propriétaire peut partager'
        ], 403);
    }

    $document->sharedWith()->attach($request->user_id);

    return response()->json([
        'message' => 'Document partagé avec succès'
    ]);
}
// Génération d’un lien public pour un document
public function generatePublicLink(Request $request, Document $document)
{
    if ($request->user()->id !== $document->user_id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $validated = $request->validate([
        'permission' => 'required|in:read,edit',
        'allow_download' => 'required|boolean',
        'expires_in_days' => 'required|integer|min:1|max:30',
        'password' => 'nullable|string|min:4',
        'email' => 'nullable|email'
    ]);

    $share = DocumentShare::create([
        'document_id' => $document->id,
        'token' => Str::uuid(),
        'expires_at' => now()->addDays($validated['expires_in_days']),
        'permission' => $validated['permission'],
        'allow_download' => $validated['allow_download'],
        'password' => $validated['password']
            ? bcrypt($validated['password'])
            : null
    ]);

    // URL SIGNÉE TEMPORAIRE
    $signedUrl = URL::temporarySignedRoute(
        'public.share',
        $share->expires_at,
        ['token' => $share->token]
    );

    // Envoi email automatique si fourni
    if (!empty($validated['email'])) {
        Mail::to($validated['email'])
            ->send(new ShareLinkMail($signedUrl, $share->expires_at));
    }

    // Log activité
    ActivityLog::create([
        'user_id' => $request->user()->id,
        'action' => 'document_shared',
        'description' => 'Lien public généré',
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'success' => true,
        'loggable_id' => $share->id,
        'loggable_type' => DocumentShare::class
    ]);

    return response()->json([
        'public_url' => $signedUrl,
        'expires_at' => $share->expires_at
    ]);
}

// Accès à un document via lien public
public function accessPublicDocument(Request $request, $token)
{
    $share = DocumentShare::where('token', $token)->first();

    if (!$share) {
        return response()->json(['message' => 'Invalid link'], 404);
    }

    if ($share->isExpired()) {

        ActivityLog::create([
            'action' => 'public_access',
            'description' => 'Lien expiré',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'success' => false,
            'loggable_id' => $share->id,
            'loggable_type' => DocumentShare::class
        ]);

        return response()->json(['message' => 'Link expired'], 403);
    }

    if ($share->password) {
        if (!$request->has('password') ||
            !Hash::check($request->password, $share->password)) {

            ActivityLog::create([
                'action' => 'public_access',
                'description' => 'Mot de passe incorrect',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'success' => false,
                'loggable_id' => $share->id,
                'loggable_type' => DocumentShare::class
            ]);

            return response()->json(['message' => 'Password incorrect'], 403);
        }
    }

    if (!$share->allow_download) {
        return response()->json(['message' => 'Download not allowed'], 403);
    }

    $share->increment('download_count');

    ActivityLog::create([
        'action' => 'public_access',
        'description' => 'Téléchargement réussi',
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'success' => true,
        'loggable_id' => $share->id,
        'loggable_type' => DocumentShare::class
    ]);

    return Storage::download($share->document->path, $share->document->name);
}
}