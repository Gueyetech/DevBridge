<?php

namespace App\Http\Controllers\Api\V1\Etudiant;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Http\Requests\Api\V1\Etudiant\RequeteMiseAJourProfil;
use App\Http\Resources\Api\V1\UtilisateurRessource;
use App\Http\Resources\Api\V1\Apprentissage\CompetenceRessource;
use App\Models\Utilisateur;
use App\Models\Competence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ControleurProfil extends ControleurApiBase
{
    /**
     * Récupérer le profil de l'utilisateur
     */
    public function afficher(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user()->load([
            'profil',
            'parcoursInscrits' => function($query) {
                $query->with(['modules.lecons']);
            },
            'projets' => function($query) {
                $query->whereIn('statut', ['en_cours', 'termine']);
            },
            'badges',
            'competences',
            'mentoratsCommeEtudiant.mentor.profil',
            'mentoratsCommeMentor.etudiant.profil',
        ]);
        
        $statistiques = [
            'points_totaux' => $utilisateur->points,
            'niveau' => $utilisateur->niveau,
            'parcours_termines' => $utilisateur->parcoursInscrits()
                ->wherePivot('termine_a', '!=', null)
                ->count(),
            'projets_termines' => $utilisateur->projets()
                ->where('statut', 'termine')
                ->count(),
            'badges_obtenus' => $utilisateur->badges->count(),
            'competences_validees' => $utilisateur->competences->count(),
            'temps_apprentissage_total' => $this->calculerTempsApprentissage($utilisateur),
        ];
        
        return $this->reponseSucces([
            'utilisateur' => new UtilisateurRessource($utilisateur),
            'statistiques' => $statistiques,
            'activite_recente' => $this->obtenirActiviteRecente($utilisateur),
        ]);
    }
    
    /**
     * Mettre à jour le profil
     */
    public function mettreAJour(RequeteMiseAJourProfil $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        $donneesValidees = $requete->validated();
        
        // Mettre à jour l'utilisateur
        if (isset($donneesValidees['prenom']) || isset($donneesValidees['nom'])) {
            $utilisateur->update([
                'prenom' => $donneesValidees['prenom'] ?? $utilisateur->prenom,
                'nom' => $donneesValidees['nom'] ?? $utilisateur->nom,
            ]);
        }
        
        // Mettre à jour le profil
        if ($utilisateur->profil) {
            $utilisateur->profil->update([
                'bio' => $donneesValidees['bio'] ?? $utilisateur->profil->bio,
                'github_url' => $donneesValidees['github_url'] ?? $utilisateur->profil->github_url,
                'linkedin_url' => $donneesValidees['linkedin_url'] ?? $utilisateur->profil->linkedin_url,
                'portfolio_url' => $donneesValidees['portfolio_url'] ?? $utilisateur->profil->portfolio_url,
                'ville' => $donneesValidees['ville'] ?? $utilisateur->profil->ville,
                'pays' => $donneesValidees['pays'] ?? $utilisateur->profil->pays,
                'technologies' => $donneesValidees['technologies'] ?? $utilisateur->profil->technologies,
            ]);
        }
        
        $utilisateur->refresh()->load('profil');
        
        return $this->reponseSucces([
            'message' => 'Profil mis à jour avec succès',
            'utilisateur' => new UtilisateurRessource($utilisateur),
        ]);
    }
    
    /**
     * Mettre à jour l'avatar
     */
    public function mettreAJourAvatar(Request $requete): JsonResponse
    {
        $requete->validate([
            'avatar' => 'required|image|max:2048|mimes:jpg,jpeg,png,gif',
        ]);
        
        $utilisateur = $requete->user();
        
        // Supprimer l'ancien avatar s'il existe
        if ($utilisateur->avatar) {
            Storage::disk('public')->delete($utilisateur->avatar);
        }
        
        // Enregistrer le nouvel avatar
        $chemin = $requete->file('avatar')->store('avatars', 'public');
        
        $utilisateur->update(['avatar' => $chemin]);
        
        return $this->reponseSucces([
            'message' => 'Avatar mis à jour avec succès',
            'avatar_url' => Storage::url($chemin),
        ]);
    }
    
    /**
     * Lister les compétences de l'utilisateur
     */
    public function competences(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        
        $competences = $utilisateur->competences()
            ->withPivot(['niveau_maitrise', 'valide_a', 'methode_validation'])
            ->orderByDesc('competences_utilisateurs.valide_a')
            ->paginate(20);
        
        $statistiques = [
            'total' => $utilisateur->competences()->count(),
            'par_niveau' => [
                'debutant' => $utilisateur->competences()->where('niveau', 'debutant')->count(),
                'intermediaire' => $utilisateur->competences()->where('niveau', 'intermediaire')->count(),
                'avance' => $utilisateur->competences()->where('niveau', 'avance')->count(),
            ],
            'par_categorie' => $utilisateur->competences()
                ->selectRaw('categorie, COUNT(*) as count')
                ->groupBy('categorie')
                ->pluck('count', 'categorie'),
        ];
        
        return $this->reponseSucces([
            'competences' => CompetenceRessource::collection($competences),
            'statistiques' => $statistiques,
        ]);
    }
    
    /**
     * Ajouter une compétence manuellement (pour validation par mentor)
     */
    public function ajouterCompetence(Request $requete): JsonResponse
    {
        $requete->validate([
            'competence_id' => 'required|exists:competences,id',
            'niveau_maitrise' => 'required|integer|min:1|max:5',
            'preuves' => 'nullable|array',
            'preuves.*' => 'url',
        ]);
        
        $utilisateur = $requete->user();
        $competence = Competence::findOrFail($requete->competence_id);
        
        // Vérifier si la compétence n'est pas déjà validée
        if ($utilisateur->competences()->where('competence_id', $competence->id)->exists()) {
            return $this->reponseErreur('Cette compétence est déjà validée', 400);
        }
        
        // Ajouter la compétence avec statut "en attente de validation"
        $utilisateur->competences()->attach($competence->id, [
            'niveau_maitrise' => $requete->niveau_maitrise,
            'valide_a' => null, // Pas encore validée
            'valide_par' => null,
            'methode_validation' => 'demande_mentor',
            'preuves' => $requete->preuves,
        ]);
        
        return $this->reponseSucces([
            'message' => 'Compétence ajoutée en attente de validation par un mentor',
            'competence' => new CompetenceRessource($competence),
        ], 201);
    }
    
    /**
     * Calculer le temps total d'apprentissage
     */
    private function calculerTempsApprentissage($utilisateur): int
    {
        return $utilisateur->suiviTemps()
            ->where('type_activite', 'lecon')
            ->sum('duree_secondes');
    }
    
    /**
     * Obtenir l'activité récente
     */
    private function obtenirActiviteRecente($utilisateur): array
    {
        return $utilisateur->suiviTemps()
            ->with(['activite' => function($query) {
                $query->morphWith([
                    \App\Models\Lecon::class => ['module.parcours'],
                    \App\Models\Projet::class => [],
                    \App\Models\Defi::class => [],
                ]);
            }])
            ->orderByDesc('debut_a')
            ->limit(15)
            ->get()
            ->map(function($suivi) {
                $activite = $suivi->activite;
                $description = '';
                
                if ($activite instanceof \App\Models\Lecon) {
                    $description = "Leçon : {$activite->titre}";
                } elseif ($activite instanceof \App\Models\Projet) {
                    $description = "Projet : {$activite->titre}";
                } elseif ($activite instanceof \App\Models\Defi) {
                    $description = "Défi : {$activite->titre}";
                }
                
                return [
                    'type' => $suivi->type_activite,
                    'description' => $description,
                    'duree' => $suivi->duree_minutes,
                    'date' => $suivi->debut_a->format('d/m/Y H:i'),
                ];
            })
            ->toArray();
    }
}