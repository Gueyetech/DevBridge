<?php

namespace App\Http\Controllers\Api\V1\Administrateur;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Http\Requests\Api\V1\Administrateur\RequeteCreerUtilisateur;
use App\Http\Requests\Api\V1\Administrateur\RequeteMettreAJourUtilisateur;
use App\Http\Resources\Api\V1\Administrateur\UtilisateurRessourceAdmin;
use App\Models\Utilisateur;
use App\Enums\RoleUtilisateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ControleurGestionUtilisateurs extends ControleurApiBase
{
    /**
     * Lister tous les utilisateurs
     */
    public function index(Request $requete): JsonResponse
    {
        $query = Utilisateur::with('profil');
        
        // Filtres
        if ($requete->has('role')) {
            $query->where('role', $requete->role);
        }
        
        if ($requete->has('est_actif')) {
            $query->where('est_actif', $requete->est_actif === 'true');
        }
        
        if ($requete->has('recherche')) {
            $query->where(function($q) use ($requete) {
                $q->where('prenom', 'like', "%{$requete->recherche}%")
                  ->orWhere('nom', 'like', "%{$requete->recherche}%")
                  ->orWhere('email', 'like', "%{$requete->recherche}%");
            });
        }
        
        // Tri
        $tri = $requete->input('tri', 'created_at');
        $direction = $requete->input('direction', 'desc');
        
        $utilisateurs = $query->orderBy($tri, $direction)
            ->paginate($requete->input('per_page', 20));
        
        $statistiques = [
            'total' => Utilisateur::count(),
            'etudiants' => Utilisateur::where('role', RoleUtilisateur::ETUDIANT->value)->count(),
            'mentors' => Utilisateur::where('role', RoleUtilisateur::MENTOR->value)->count(),
            'administrateurs' => Utilisateur::where('role', RoleUtilisateur::ADMINISTRATEUR->value)->count(),
            'actifs' => Utilisateur::where('est_actif', true)->count(),
        ];
        
        return $this->reponseSucces([
            'utilisateurs' => UtilisateurRessourceAdmin::collection($utilisateurs),
            'statistiques' => $statistiques,
            'meta' => [
                'total' => $utilisateurs->total(),
                'par_page' => $utilisateurs->perPage(),
                'page_courante' => $utilisateurs->currentPage(),
                'derniere_page' => $utilisateurs->lastPage(),
            ],
        ]);
    }
    
    /**
     * Afficher un utilisateur spécifique
     */
    public function afficher($id): JsonResponse
    {
        $utilisateur = Utilisateur::with([
            'profil',
            'parcoursInscrits',
            'projets',
            'badges',
            'competences',
            'tentativesQuiz',
            'suiviTemps' => function($query) {
                $query->orderByDesc('debut_a')->limit(20);
            },
        ])->findOrFail($id);
        
        $statistiques = [
            'points' => $utilisateur->points,
            'niveau' => $utilisateur->niveau,
            'parcours_termines' => $utilisateur->parcoursInscrits()
                ->wherePivot('termine_a', '!=', null)
                ->count(),
            'projets_termines' => $utilisateur->projets()
                ->where('statut', 'termine')
                ->count(),
            'temps_apprentissage_total' => $utilisateur->suiviTemps()->sum('duree_secondes') / 3600,
            'derniere_connexion' => $utilisateur->derniere_connexion,
        ];
        
        return $this->reponseSucces([
            'utilisateur' => new UtilisateurRessourceAdmin($utilisateur),
            'statistiques' => $statistiques,
        ]);
    }
    
    /**
     * Créer un nouvel utilisateur (admin)
     */
    public function creer(RequeteCreerUtilisateur $requete): JsonResponse
    {
        $donnees = $requete->validated();
        
        $utilisateur = Utilisateur::create([
            'prenom' => $donnees['prenom'],
            'nom' => $donnees['nom'],
            'email' => $donnees['email'],
            'mot_de_passe' => Hash::make($donnees['mot_de_passe']),
            'role' => $donnees['role'] ?? RoleUtilisateur::ETUDIANT->value,
            'est_actif' => $donnees['est_actif'] ?? true,
        ]);
        
        // Créer le profil
        $utilisateur->profil()->create([
            'niveau' => $donnees['niveau'] ?? 'debutant',
            'technologies' => $donnees['technologies'] ?? [],
        ]);
        
        return $this->reponseSucces([
            'message' => 'Utilisateur créé avec succès',
            'utilisateur' => new UtilisateurRessourceAdmin($utilisateur),
        ], 201);
    }
    
    /**
     * Mettre à jour un utilisateur
     */
    public function mettreAJour(RequeteMettreAJourUtilisateur $requete, $id): JsonResponse
    {
        $utilisateur = Utilisateur::findOrFail($id);
        $donnees = $requete->validated();
        
        // Mettre à jour l'utilisateur
        $misesAJour = [];
        
        if (isset($donnees['prenom'])) {
            $misesAJour['prenom'] = $donnees['prenom'];
        }
        
        if (isset($donnees['nom'])) {
            $misesAJour['nom'] = $donnees['nom'];
        }
        
        if (isset($donnees['email'])) {
            $misesAJour['email'] = $donnees['email'];
        }
        
        if (isset($donnees['role'])) {
            $misesAJour['role'] = $donnees['role'];
        }
        
        if (isset($donnees['est_actif'])) {
            $misesAJour['est_actif'] = $donnees['est_actif'];
        }
        
        if (isset($donnees['mot_de_passe'])) {
            $misesAJour['mot_de_passe'] = Hash::make($donnees['mot_de_passe']);
        }
        
        if (!empty($misesAJour)) {
            $utilisateur->update($misesAJour);
        }
        
        // Mettre à jour le profil
        if ($utilisateur->profil && isset($donnees['profil'])) {
            $utilisateur->profil->update($donnees['profil']);
        }
        
        $utilisateur->refresh()->load('profil');
        
        return $this->reponseSucces([
            'message' => 'Utilisateur mis à jour avec succès',
            'utilisateur' => new UtilisateurRessourceAdmin($utilisateur),
        ]);
    }
    
    /**
     * Désactiver un utilisateur
     */
    public function desactiver($id): JsonResponse
    {
        $utilisateur = Utilisateur::findOrFail($id);
        
        $utilisateur->update(['est_actif' => false]);
        
        // Supprimer les tokens d'accès
        $utilisateur->tokens()->delete();
        
        return $this->reponseSucces([
            'message' => 'Utilisateur désactivé avec succès',
        ]);
    }
    
    /**
     * Réactiver un utilisateur
     */
    public function reactiver($id): JsonResponse
    {
        $utilisateur = Utilisateur::findOrFail($id);
        
        $utilisateur->update(['est_actif' => true]);
        
        return $this->reponseSucces([
            'message' => 'Utilisateur réactivé avec succès',
        ]);
    }
    
    /**
     * Supprimer un utilisateur (soft delete)
     */
    public function supprimer($id): JsonResponse
    {
        $utilisateur = Utilisateur::findOrFail($id);
        
        $utilisateur->delete();
        
        return $this->reponseSucces([
            'message' => 'Utilisateur supprimé avec succès',
        ]);
    }
    
    /**
     * Restaurer un utilisateur supprimé
     */
    public function restaurer($id): JsonResponse
    {
        $utilisateur = Utilisateur::withTrashed()->findOrFail($id);
        
        $utilisateur->restore();
        
        return $this->reponseSucces([
            'message' => 'Utilisateur restauré avec succès',
        ]);
    }
    
    /**
     * Forcer la suppression d'un utilisateur
     */
    public function forcerSuppression($id): JsonResponse
    {
        $utilisateur = Utilisateur::withTrashed()->findOrFail($id);
        
        // Supprimer les relations (à adapter selon les contraintes de clés étrangères)
        $utilisateur->profil()->delete();
        $utilisateur->tokens()->delete();
        // ... autres relations
        
        $utilisateur->forceDelete();
        
        return $this->reponseSucces([
            'message' => 'Utilisateur définitivement supprimé',
        ]);
    }
    
    /**
     * Changer le rôle d'un utilisateur
     */
    public function changerRole(Request $requete, $id): JsonResponse
    {
        $requete->validate([
            'role' => 'required|in:etudiant,mentor,administrateur',
        ]);
        
        $utilisateur = Utilisateur::findOrFail($id);
        
        $ancienRole = $utilisateur->role;
        $utilisateur->update(['role' => $requete->role]);
        
        // Si promotion à mentor, activer la disponibilité
        if ($requete->role === 'mentor' && $ancienRole !== 'mentor') {
            if ($utilisateur->profil) {
                $utilisateur->profil->update(['est_disponible_mentorat' => true]);
            }
        }
        
        return $this->reponseSucces([
            'message' => "Rôle changé de {$ancienRole} à {$requete->role}",
            'utilisateur' => new UtilisateurRessourceAdmin($utilisateur),
        ]);
    }
    
    /**
     * Réinitialiser le mot de passe d'un utilisateur (admin)
     */
    public function reinitialiserMotDePasse(Request $requete, $id): JsonResponse
    {
        $requete->validate([
            'nouveau_mot_de_passe' => 'required|string|min:8|confirmed',
        ]);
        
        $utilisateur = Utilisateur::findOrFail($id);
        
        $utilisateur->update([
            'mot_de_passe' => Hash::make($requete->nouveau_mot_de_passe),
        ]);
        
        // Supprimer les tokens existants (forcer une nouvelle connexion)
        $utilisateur->tokens()->delete();
        
        return $this->reponseSucces([
            'message' => 'Mot de passe réinitialisé avec succès',
        ]);
    }
    
    /**
     * Obtenir les statistiques d'utilisation
     */
    public function statistiques(): JsonResponse
    {
        // Statistiques utilisateurs
        $statistiques = [
            'inscriptions_par_mois' => $this->obtenirInscriptionsParMois(),
            'activite_par_role' => $this->obtenirActiviteParRole(),
            'taux_retention' => $this->calculerTauxRetention(),
            'utilisateurs_actifs' => $this->obtenirUtilisateursActifs(),
        ];
        
        return $this->reponseSucces([
            'statistiques' => $statistiques,
        ]);
    }
    
    /**
     * Obtenir les inscriptions par mois
     */
    private function obtenirInscriptionsParMois(): array
    {
        return Utilisateur::selectRaw('YEAR(created_at) as annee, MONTH(created_at) as mois, COUNT(*) as total')
            ->where('created_at', '>=', now()->subYear())
            ->groupBy('annee', 'mois')
            ->orderBy('annee')
            ->orderBy('mois')
            ->get()
            ->map(function($item) {
                return [
                    'mois' => $item->mois . '/' . $item->annee,
                    'total' => $item->total,
                ];
            })
            ->toArray();
    }
    
    /**
     * Obtenir l'activité par rôle
     */
    private function obtenirActiviteParRole(): array
    {
        return [
            'etudiants' => [
                'total' => Utilisateur::where('role', 'etudiant')->count(),
                'actifs' => Utilisateur::where('role', 'etudiant')
                    ->where('est_actif', true)
                    ->count(),
                'moyenne_points' => round(Utilisateur::where('role', 'etudiant')->avg('points') ?? 0, 2),
            ],
            'mentors' => [
                'total' => Utilisateur::where('role', 'mentor')->count(),
                'actifs' => Utilisateur::where('role', 'mentor')
                    ->where('est_actif', true)
                    ->count(),
                'disponibles' => \DB::table('profils')
                    ->join('utilisateurs', 'profils.utilisateur_id', '=', 'utilisateurs.id')
                    ->where('utilisateurs.role', 'mentor')
                    ->where('profils.est_disponible_mentorat', true)
                    ->count(),
            ],
            'administrateurs' => [
                'total' => Utilisateur::where('role', 'administrateur')->count(),
                'actifs' => Utilisateur::where('role', 'administrateur')
                    ->where('est_actif', true)
                    ->count(),
            ],
        ];
    }
    
    /**
     * Calculer le taux de rétention
     */
    private function calculerTauxRetention(): array
    {
        // Taux de rétention sur 30 jours
        $ilYA30Jours = now()->subDays(30);
        
        $utilisateursIlYA30Jours = Utilisateur::where('created_at', '<=', $ilYA30Jours)->count();
        
        if ($utilisateursIlYA30Jours === 0) {
            return ['pourcentage' => 0, 'total' => 0];
        }
        
        $utilisateursActifs = Utilisateur::where('created_at', '<=', $ilYA30Jours)
            ->where('derniere_connexion', '>=', now()->subDays(7))
            ->count();
        
        $taux = round(($utilisateursActifs / $utilisateursIlYA30Jours) * 100, 2);
        
        return [
            'pourcentage' => $taux,
            'total' => $utilisateursActifs,
            'base' => $utilisateursIlYA30Jours,
        ];
    }
    
    /**
     * Obtenir les utilisateurs actifs
     */
    private function obtenirUtilisateursActifs(): array
    {
        return [
            'derniere_semaine' => Utilisateur::where('derniere_connexion', '>=', now()->subDays(7))->count(),
            'dernier_mois' => Utilisateur::where('derniere_connexion', '>=', now()->subDays(30))->count(),
            'derniers_3_mois' => Utilisateur::where('derniere_connexion', '>=', now()->subDays(90))->count(),
        ];
    }
}