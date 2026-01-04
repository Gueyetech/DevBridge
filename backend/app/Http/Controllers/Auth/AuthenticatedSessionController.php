<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Utilisateur;
use App\Models\LogActivite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        /** @var Utilisateur $utilisateur */
        $utilisateur = Auth::user();

        // Vérifier si l'utilisateur est actif
        if (!$utilisateur->est_actif) {
            Auth::logout();
            return response()->json([
                'message' => 'Votre compte a été désactivé. Contactez l\'administrateur.',
            ], 403);
        }

        // Générer un token API
        $token = $utilisateur->createToken('auth-token')->plainTextToken;

        // Log de connexion
        LogActivite::connexion($utilisateur->id);

        return response()->json([
            'message' => 'Connexion réussie',
            'utilisateur' => [
                'id' => $utilisateur->id,
                'prenom' => $utilisateur->prenom,
                'nom' => $utilisateur->nom,
                'nom_complet' => $utilisateur->nom_complet,
                'email' => $utilisateur->email,
                'role' => $utilisateur->role,
                'niveau' => $utilisateur->niveau,
                'points' => $utilisateur->points,
                'avatar' => $utilisateur->avatar,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): JsonResponse
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        if ($utilisateur) {
            // Log de déconnexion
            LogActivite::deconnexion($utilisateur->id);

            // Révoquer le token actuel
            $utilisateur->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function utilisateur(Request $request): JsonResponse
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        return response()->json([
            'utilisateur' => [
                'id' => $utilisateur->id,
                'prenom' => $utilisateur->prenom,
                'nom' => $utilisateur->nom,
                'nom_complet' => $utilisateur->nom_complet,
                'email' => $utilisateur->email,
                'role' => $utilisateur->role,
                'niveau' => $utilisateur->niveau,
                'points' => $utilisateur->points,
                'avatar' => $utilisateur->avatar,
                'est_etudiant' => $utilisateur->est_etudiant,
                'est_mentor' => $utilisateur->est_mentor,
                'est_administrateur' => $utilisateur->est_administrateur,
            ],
        ]);
    }
}
