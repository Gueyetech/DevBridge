<?php

namespace App\Http\Controllers\Api\V1\Etudiant;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Http\Resources\Api\V1\Etudiant\TableauDeBordRessource;
use App\Http\Resources\Api\V1\Apprentissage\ParcoursApprentissageRessource;
use App\Http\Resources\Api\V1\Projet\ProjetRessource;
use App\Models\ParcoursApprentissage;
use App\Models\Projet;
use App\Models\Defi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ControleurTableauDeBord extends ControleurApiBase
{
    /**
     * Récupérer le tableau de bord de l'étudiant
     */
    public function index(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user()->load([
            'profil',
            'parcoursInscrits' => function($query) {
                $query->with(['modules.lecons']);
            },
            'projets' => function($query) {
                $query->whereIn('statut', ['ouvert', 'en_cours']);
            },
            'badges' => function($query) {
                $query->orderByDesc('badges_utilisateurs.obtenu_a')->limit(5);
            },
            'competences' => function($query) {
                $query->orderByDesc('competences_utilisateurs.valide_a')->limit(5);
            },
        ]);
        
        // Statistiques
        $statistiques = [
            'points_totaux' => $utilisateur->points,
            'niveau' => $utilisateur->niveau,
            'parcours_inscrits' => $utilisateur->parcoursInscrits->count(),
            'parcours_termines' => $utilisateur->parcoursInscrits()
                ->wherePivot('termine_a', '!=', null)
                ->count(),
            'projets_actifs' => $utilisateur->projets->count(),
            'badges_obtenus' => $utilisateur->badges->count(),
            'competences_validees' => $utilisateur->competences->count(),
            'streak_jours' => $this->calculerStreak($utilisateur),
        ];
        
        // Progrès des parcours actifs
        $parcoursActifs = $utilisateur->parcoursInscrits()
            ->wherePivot('termine_a', null)
            ->orderByDesc('inscriptions_parcours.updated_at')
            ->limit(3)
            ->get();
        
        // Projets actifs
        $projetsActifs = $utilisateur->projets()
            ->whereIn('statut', ['en_cours', 'ouvert'])
            ->orderByDesc('updated_at')
            ->limit(3)
            ->get();
        
        // Défis recommandés
        $defisRecommandes = Defi::where('est_actif', true)
            ->where('date_debut', '<=', now())
            ->where('date_fin', '>=', now())
            ->where('difficulte', $utilisateur->profil->niveau)
            ->whereDoesntHave('participants', function($query) use ($utilisateur) {
                $query->where('utilisateur_id', $utilisateur->id);
            })
            ->orderBy('date_fin')
            ->limit(3)
            ->get();
        
        // Parcours recommandés
        $parcoursRecommandes = ParcoursApprentissage::where('est_publie', true)
            ->where('difficulte', $utilisateur->profil->niveau)
            ->whereNotIn('id', $utilisateur->parcoursInscrits->pluck('id'))
            ->orderBy('ordre')
            ->limit(3)
            ->get();
        
        // Activité récente
        $activiteRecente = $this->obtenirActiviteRecente($utilisateur);
        
        return $this->reponseSucces([
            'statistiques' => $statistiques,
            'parcours_actifs' => ParcoursApprentissageRessource::collection($parcoursActifs),
            'projets_actifs' => ProjetRessource::collection($projetsActifs),
            'defis_recommandes' => $defisRecommandes,
            'parcours_recommandes' => ParcoursApprentissageRessource::collection($parcoursRecommandes),
            'activite_recente' => $activiteRecente,
            'badges_recents' => $utilisateur->badges,
        ]);
    }
    
    /**
     * Récupérer les statistiques détaillées
     */
    public function statistiques(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        $depuis = $requete->input('depuis', '30days');
        
        $dateDebut = match($depuis) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'year' => now()->subYear(),
            default => now()->subDays(30),
        };
        
        $statistiques = [
            'temps_apprentissage' => $this->obtenirTempsApprentissage($utilisateur, $dateDebut),
            'quiz_completes' => $this->obtenirQuizCompletes($utilisateur, $dateDebut),
            'projets_termines' => $this->obtenirProjetsTermines($utilisateur, $dateDebut),
            'points_gagnes' => $this->obtenirPointsGagnes($utilisateur, $dateDebut),
            'evolution_niveau' => $this->obtenirEvolutionNiveau($utilisateur, $dateDebut),
        ];
        
        return $this->reponseSucces([
            'statistiques' => $statistiques,
            'periode' => $depuis,
        ]);
    }
    
    /**
     * Calculer le streak de l'utilisateur
     */
    private function calculerStreak($utilisateur): int
    {
        $streak = 0;
        $date = now();
        
        for ($i = 0; $i < 30; $i++) {
            $dateDuJour = $date->copy()->subDays($i);
            
            $aEuActivite = $utilisateur->suiviTemps()
                ->whereDate('debut_a', $dateDuJour)
                ->exists();
            
            if ($aEuActivite) {
                $streak++;
            } else {
                break;
            }
        }
        
        return $streak;
    }
    
    /**
     * Obtenir l'activité récente
     */
    private function obtenirActiviteRecente($utilisateur): array
    {
        return $utilisateur->suiviTemps()
            ->with('activite')
            ->orderByDesc('debut_a')
            ->limit(10)
            ->get()
            ->map(function($suivi) {
                return [
                    'type' => $suivi->type_activite,
                    'activite' => $suivi->activite,
                    'duree' => $suivi->duree_minutes,
                    'date' => $suivi->debut_a,
                ];
            })
            ->toArray();
    }
    
    /**
     * Obtenir le temps d'apprentissage
     */
    private function obtenirTempsApprentissage($utilisateur, $dateDebut): array
    {
        $suiviTemps = $utilisateur->suiviTemps()
            ->where('debut_a', '>=', $dateDebut)
            ->selectRaw('DATE(debut_a) as date, SUM(duree_secondes) as total_secondes')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        return [
            'total_heures' => round($suiviTemps->sum('total_secondes') / 3600, 2),
            'par_jour' => $suiviTemps->map(function($item) {
                return [
                    'date' => $item->date,
                    'heures' => round($item->total_secondes / 3600, 2),
                ];
            }),
        ];
    }
    
    /**
     * Obtenir les quiz complétés
     */
    private function obtenirQuizCompletes($utilisateur, $dateDebut): array
    {
        $tentatives = $utilisateur->tentativesQuiz()
            ->where('commence_a', '>=', $dateDebut)
            ->with('quiz')
            ->get();
        
        return [
            'total' => $tentatives->count(),
            'reussis' => $tentatives->where('est_reussi', true)->count(),
            'taux_reussite' => $tentatives->count() > 0 
                ? round(($tentatives->where('est_reussi', true)->count() / $tentatives->count()) * 100, 2)
                : 0,
            'par_quiz' => $tentatives->groupBy('quiz_id')->map(function($group) {
                return [
                    'quiz' => $group->first()->quiz->titre,
                    'tentatives' => $group->count(),
                    'meilleur_score' => $group->max('score'),
                ];
            }),
        ];
    }
    
    /**
     * Obtenir les projets terminés
     */
    private function obtenirProjetsTermines($utilisateur, $dateDebut): array
    {
        $projetsTermines = $utilisateur->projets()
            ->where('statut', 'termine')
            ->where('termine_a', '>=', $dateDebut)
            ->get();
        
        return [
            'total' => $projetsTermines->count(),
            'par_technologie' => $projetsTermines->flatMap(function($projet) {
                return $projet->technologies;
            })->countBy(),
            'points_gagnes' => $projetsTermines->sum('points_recompense'),
        ];
    }
    
    /**
     * Obtenir les points gagnés
     */
    private function obtenirPointsGagnes($utilisateur, $dateDebut): array
    {
        $pointsParSource = [
            'quiz' => 0,
            'projets' => 0,
            'defis' => 0,
            'badges' => 0,
            'activites' => 0,
        ];
        
        // Points des quiz (à implémenter dans les événements)
        // Points des projets
        $pointsParSource['projets'] = $utilisateur->projets()
            ->where('statut', 'termine')
            ->where('termine_a', '>=', $dateDebut)
            ->sum('points_recompense');
        
        // Points des badges
        $pointsParSource['badges'] = $utilisateur->badges()
            ->where('badges_utilisateurs.obtenu_a', '>=', $dateDebut)
            ->sum('points_recompense');
        
        return [
            'total' => array_sum($pointsParSource),
            'par_source' => $pointsParSource,
        ];
    }
    
    /**
     * Obtenir l'évolution du niveau
     */
    private function obtenirEvolutionNiveau($utilisateur, $dateDebut): array
    {
        // Cette méthode nécessiterait un historique des niveaux
        // Pour l'instant, retourner une simulation
        return [
            'niveau_actuel' => $utilisateur->niveau,
            'points_restants' => ($utilisateur->niveau * 1000) - $utilisateur->points,
            'progression_niveau' => ($utilisateur->points % 1000) / 10,
        ];
    }
}