<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\User;
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
     * Upload d'un document
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,txt,zip'
        ]);

        $file = $request->file('file');
        $path = $file->store('documents');

        $document = Document::create([
            'user_id'   => $request->user()->id,
            'name'      => $file->getClientOriginalName(),
            'path'      => $path,
            'mime_type' => $file->getMimeType(),
            'size'      => $file->getSize()
        ]);

        return response()->json([
            'message'  => 'Document uploadé avec succès',
            'document' => $document
        ], 201);
    }

    /**
     * Liste des documents
     */
    public function index(Request $request)
{
    $user = $request->user();
    $perPage = $request->get('per_page', 10);
    $search = $request->get('search', '');

    if ($user->role && $user->role->name === 'admin') {
        $documents = Document::with(['user', 'sharedWith'])
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'type'      => 'admin',
            'documents' => $documents
        ]);
    }

    $ownedDocuments = $user->documents()
        ->with('sharedWith')
        ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
        ->latest()
        ->paginate($perPage);

    $sharedDocuments = $user->sharedDocuments()
        ->with('user')
        ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
        ->latest()
        ->paginate($perPage);

    return response()->json([
        'type'             => 'user',
        'owned_documents'  => $ownedDocuments,
        'shared_documents' => $sharedDocuments
    ]);
}

    /**
     * Télécharger un document
     */
    public function download(Request $request, $id)
    {
        $document = Document::findOrFail($id);
        $user     = $request->user();

        $hasAccess = $user->role->name === 'admin'
            || $document->user_id === $user->id
            || $document->sharedWith->contains($user->id);

        if (!$hasAccess) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        return Storage::download($document->path, $document->name);
    }

    /**
     * Supprimer un document
     */
    public function destroy(Request $request, $id)
    {
        $document = Document::findOrFail($id);
        $user     = $request->user();

        if ($user->role->name !== 'admin' && $document->user_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        Storage::delete($document->path);
        $document->delete();

        return response()->json(['message' => 'Document supprimé']);
    }

    /**
     * Partager avec un utilisateur par EMAIL
     */
    public function share(Request $request, $id)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $document = Document::findOrFail($id);
        $user     = $request->user();

        if ($document->user_id !== $user->id && $user->role->name !== 'admin') {
            return response()->json(['message' => 'Seul le propriétaire peut partager'], 403);
        }

        $userToShare = User::where('email', $request->email)->first();

        // Évite les doublons
        $document->sharedWith()->syncWithoutDetaching([$userToShare->id]);

        return response()->json([
            'message' => 'Document partagé avec ' . $userToShare->name
        ]);
    }

    /**
     * Génération d'un lien public
     */
    public function generatePublicLink(Request $request, Document $document)
    {
        if ($request->user()->id !== $document->user_id && $request->user()->role->name !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'permission'      => 'required|in:read,edit',
            'allow_download'  => 'required|boolean',
            'expires_in_days' => 'required|integer|min:1|max:30',
            'password'        => 'nullable|string|min:4',
            'email'           => 'nullable|email'
        ]);

        $share = DocumentShare::create([
            'document_id'    => $document->id,
            'user_id'        => $request->user()->id,
            'token'          => Str::uuid(),
            'expires_at'     => now()->addDays($validated['expires_in_days']),
            'permission'     => $validated['permission'],
            'allow_download' => $validated['allow_download'],
            'password'       => $validated['password'] ? bcrypt($validated['password']) : null
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'public.share',
            $share->expires_at,
            ['token' => $share->token]
        );

        if (!empty($validated['email'])) {
            Mail::to($validated['email'])
                ->send(new ShareLinkMail($signedUrl, $share->expires_at));
        }

        ActivityLog::create([
            'user_id'      => $request->user()->id,
            'action'       => 'document_shared',
            'description'  => 'Lien public généré',
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'success'      => true,
            'loggable_id'  => $share->id,
            'loggable_type' => DocumentShare::class
        ]);

        return response()->json([
            'public_url' => $signedUrl,
            'expires_at' => $share->expires_at
        ]);
    }

    /**
     * Accès via lien public
     */
    public function accessPublicDocument(Request $request, $token)
    {
        $share = DocumentShare::where('token', $token)->first();

        if (!$share) {
            return response()->json(['message' => 'Invalid link'], 404);
        }

        if ($share->isExpired()) {
            ActivityLog::create([
                'action'       => 'public_access',
                'description'  => 'Lien expiré',
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'success'      => false,
                'loggable_id'  => $share->id,
                'loggable_type' => DocumentShare::class
            ]);
            return response()->json(['message' => 'Link expired'], 403);
        }

        if ($share->password) {
            if (!$request->has('password') || !Hash::check($request->password, $share->password)) {
                ActivityLog::create([
                    'action'       => 'public_access',
                    'description'  => 'Mot de passe incorrect',
                    'ip_address'   => $request->ip(),
                    'user_agent'   => $request->userAgent(),
                    'success'      => false,
                    'loggable_id'  => $share->id,
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
            'action'       => 'public_access',
            'description'  => 'Téléchargement réussi',
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'success'      => true,
            'loggable_id'  => $share->id,
            'loggable_type' => DocumentShare::class
        ]);

        return Storage::download($share->document->path, $share->document->name);
    }
}