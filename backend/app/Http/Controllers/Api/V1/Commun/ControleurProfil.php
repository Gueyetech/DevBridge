<?php

namespace App\Http\Controllers\Api\V1\Commun;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Models\Profil;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class ControleurProfil extends Controller
{
    /**
     * Récupérer le profil complet de l'utilisateur connecté
     */
    public function show(Request $request): JsonResponse
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();
        $utilisateur->load('profil');

        return response()->json([
            'utilisateur' => [
                'id' => $utilisateur->id,
                'prenom' => $utilisateur->prenom,
                'nom' => $utilisateur->nom,
                'nom_complet' => $utilisateur->nom_complet,
                'email' => $utilisateur->email,
                'role' => $utilisateur->role,
                'avatar' => $utilisateur->avatar,
                'niveau' => $utilisateur->niveau,
                'points' => $utilisateur->points,
                'est_actif' => $utilisateur->est_actif,
                'created_at' => $utilisateur->created_at,
            ],
            'profil' => $utilisateur->profil ? [
                'bio' => $utilisateur->profil->bio,
                'niveau' => $utilisateur->profil->niveau,
                'technologies' => $utilisateur->profil->technologies ?? [],
                'github_url' => $utilisateur->profil->github_url,
                'linkedin_url' => $utilisateur->profil->linkedin_url,
                'portfolio_url' => $utilisateur->profil->portfolio_url,
                'ville' => $utilisateur->profil->ville,
                'pays' => $utilisateur->profil->pays,
                'est_disponible_mentorat' => $utilisateur->profil->est_disponible_mentorat,
            ] : null,
        ]);
    }

    /**
     * Mettre à jour les informations de base de l'utilisateur
     */
    public function updateInfos(Request $request): JsonResponse
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        $validated = $request->validate([
            'prenom' => ['sometimes', 'string', 'max:255'],
            'nom' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes', 
                'string', 
                'lowercase', 
                'email', 
                'max:255', 
                Rule::unique('utilisateurs', 'email')->ignore($utilisateur->id)
            ],
        ]);

        $utilisateur->update($validated);

        return response()->json([
            'message' => 'Informations mises à jour avec succès',
            'utilisateur' => [
                'id' => $utilisateur->id,
                'prenom' => $utilisateur->prenom,
                'nom' => $utilisateur->nom,
                'nom_complet' => $utilisateur->nom_complet,
                'email' => $utilisateur->email,
                'role' => $utilisateur->role,
                'avatar' => $utilisateur->avatar,
            ],
        ]);
    }

    /**
     * Mettre à jour le profil (bio, technologies, réseaux sociaux, etc.)
     */
    public function updateProfil(Request $request): JsonResponse
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        $validated = $request->validate([
            'bio' => ['nullable', 'string', 'max:1000'],
            'niveau' => ['nullable', 'string', 'in:debutant,intermediaire,avance,expert'],
            'technologies' => ['nullable', 'array'],
            'technologies.*' => ['string', 'max:100'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'ville' => ['nullable', 'string', 'max:255'],
            'pays' => ['nullable', 'string', 'max:255'],
            'est_disponible_mentorat' => ['nullable', 'boolean'],
        ]);

        $profil = $utilisateur->profil;

        if (!$profil) {
            $profil = Profil::create([
                'utilisateur_id' => $utilisateur->id,
                ...$validated,
            ]);
        } else {
            $profil->update($validated);
        }

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'profil' => [
                'bio' => $profil->bio,
                'niveau' => $profil->niveau,
                'technologies' => $profil->technologies ?? [],
                'github_url' => $profil->github_url,
                'linkedin_url' => $profil->linkedin_url,
                'portfolio_url' => $profil->portfolio_url,
                'ville' => $profil->ville,
                'pays' => $profil->pays,
                'est_disponible_mentorat' => $profil->est_disponible_mentorat,
            ],
        ]);
    }

    /**
     * Mettre à jour l'avatar
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        // Supprimer l'ancien avatar s'il existe
        if ($utilisateur->avatar && Storage::disk('public')->exists($utilisateur->avatar)) {
            Storage::disk('public')->delete($utilisateur->avatar);
        }

        // Stocker le nouvel avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        
        $utilisateur->update(['avatar' => $path]);

        return response()->json([
            'message' => 'Avatar mis à jour avec succès',
            'avatar' => $path,
            'avatar_url' => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * Supprimer l'avatar
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        if ($utilisateur->avatar && Storage::disk('public')->exists($utilisateur->avatar)) {
            Storage::disk('public')->delete($utilisateur->avatar);
        }

        $utilisateur->update(['avatar' => null]);

        return response()->json([
            'message' => 'Avatar supprimé avec succès',
        ]);
    }

    /**
     * Changer le mot de passe
     */
    public function updatePassword(Request $request): JsonResponse
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        $validated = $request->validate([
            'mot_de_passe_actuel' => ['required', 'string'],
            'mot_de_passe' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Vérifier le mot de passe actuel
        if (!Hash::check($validated['mot_de_passe_actuel'], $utilisateur->mot_de_passe)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect',
                'errors' => [
                    'mot_de_passe_actuel' => ['Le mot de passe actuel est incorrect'],
                ],
            ], 422);
        }

        $utilisateur->update([
            'mot_de_passe' => Hash::make($validated['mot_de_passe']),
        ]);

        return response()->json([
            'message' => 'Mot de passe mis à jour avec succès',
        ]);
    }

    /**
     * Récupérer les statistiques de l'utilisateur
     */
    public function stats(Request $request): JsonResponse
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        $stats = [
            'points' => $utilisateur->points,
            'niveau' => $utilisateur->niveau,
            'parcours_inscrits' => $utilisateur->parcoursInscrits()->count(),
            'parcours_termines' => $utilisateur->parcoursInscrits()
                ->wherePivotNotNull('termine_a')
                ->count(),
            'projets' => $utilisateur->projets()->count(),
            'badges' => $utilisateur->badges()->count(),
            'competences' => $utilisateur->competences()->count(),
        ];

        // Stats spécifiques pour les mentors
        if ($utilisateur->role === 'mentor') {
            $stats['etudiants_mentores'] = $utilisateur->mentoratsCommeMentor()
                ->where('statut', 'actif')
                ->count();
            $stats['sessions_mentorat'] = $utilisateur->mentoratsCommeMentor()->count();
        }

        return response()->json([
            'statistiques' => $stats,
        ]);
    }

    /**
     * Récupérer l'activité récente de l'utilisateur
     */
    public function activiteRecente(Request $request): JsonResponse
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();
        $limite = $request->query('limite', 10);

        $activites = $utilisateur->logsActivites()
            ->orderBy('created_at', 'desc')
            ->take($limite)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => $log->type_activite,
                    'description' => $log->description,
                    'metadata' => $log->metadata,
                    'date' => $log->created_at,
                ];
            });

        return response()->json([
            'activites' => $activites,
        ]);
    }

    /**
     * Supprimer le compte (soft delete)
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        $request->validate([
            'mot_de_passe' => ['required', 'string'],
        ]);

        // Vérifier le mot de passe
        if (!Hash::check($request->mot_de_passe, $utilisateur->mot_de_passe)) {
            return response()->json([
                'message' => 'Mot de passe incorrect',
                'errors' => [
                    'mot_de_passe' => ['Le mot de passe est incorrect'],
                ],
            ], 422);
        }

        // Révoquer tous les tokens
        $utilisateur->tokens()->delete();

        // Soft delete
        $utilisateur->delete();

        return response()->json([
            'message' => 'Votre compte a été supprimé avec succès',
        ]);
    }
}
