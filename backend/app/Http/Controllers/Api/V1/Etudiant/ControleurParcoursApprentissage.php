<?php

namespace App\Http\Controllers\Api\V1\Etudiant;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Http\Requests\Api\V1\Etudiant\RequeteInscriptionParcours;
use App\Http\Resources\Api\V1\Apprentissage\ParcoursApprentissageRessource;
use App\Http\Resources\Api\V1\Apprentissage\ModuleRessource;
use App\Http\Resources\Api\V1\Apprentissage\LeconRessource;
use App\Http\Resources\Api\V1\Apprentissage\QuizRessource;
use App\Models\ParcoursApprentissage;
use App\Models\Module;
use App\Models\Lecon;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ControleurParcoursApprentissage extends ControleurApiBase
{
    /**
     * Lister tous les parcours disponibles
     */
    public function index(Request $requete): JsonResponse
    {
        $query = ParcoursApprentissage::with(['modules.lecons'])
            ->where('est_publie', true);
        
        // Filtres
        if ($requete->has('technologie')) {
            $query->where('technologie', $requete->technologie);
        }
        
        if ($requete->has('difficulte')) {
            $query->where('difficulte', $requete->difficulte);
        }
        
        if ($requete->has('recherche')) {
            $query->where(function($q) use ($requete) {
                $q->where('titre', 'like', "%{$requete->recherche}%")
                  ->orWhere('description', 'like', "%{$requete->recherche}%");
            });
        }
        
        // Tri
        $tri = $requete->input('tri', 'ordre');
        $direction = $requete->input('direction', 'asc');
        
        $query->orderBy($tri, $direction);
        
        $parcours = $query->paginate($requete->input('per_page', 12));
        
        return $this->reponseSucces([
            'parcours' => ParcoursApprentissageRessource::collection($parcours),
            'meta' => [
                'total' => $parcours->total(),
                'par_page' => $parcours->perPage(),
                'page_courante' => $parcours->currentPage(),
                'derniere_page' => $parcours->lastPage(),
            ],
        ]);
    }
    
    /**
     * Afficher un parcours spécifique
     */
    public function afficher(Request $requete, $id): JsonResponse
    {
        $parcours = ParcoursApprentissage::with([
            'modules' => function($query) {
                $query->orderBy('ordre')->with(['lecons' => function($q) {
                    $q->orderBy('ordre');
                }]);
            },
            'competencesLiees',
        ])->findOrFail($id);
        
        $utilisateur = $requete->user();
        $estInscrit = $utilisateur->parcoursInscrits()->where('parcours_id', $id)->exists();
        
        $progression = $estInscrit 
            ? $utilisateur->parcoursInscrits()->where('parcours_id', $id)->first()->pivot->progression_pourcentage 
            : 0;
        
        return $this->reponseSucces([
            'parcours' => new ParcoursApprentissageRessource($parcours),
            'est_inscrit' => $estInscrit,
            'progression' => $progression,
            'prealables_remplis' => $this->verifierPrealables($parcours, $utilisateur),
        ]);
    }
    
    /**
     * S'inscrire à un parcours
     */
    public function inscrire(RequeteInscriptionParcours $requete, $id): JsonResponse
    {
        $parcours = ParcoursApprentissage::findOrFail($id);
        $utilisateur = $requete->user();
        
        // Vérifier si déjà inscrit
        if ($utilisateur->parcoursInscrits()->where('parcours_id', $id)->exists()) {
            return $this->reponseErreur('Vous êtes déjà inscrit à ce parcours', 400);
        }
        
        // Vérifier les préalables
        if (!$this->verifierPrealables($parcours, $utilisateur)) {
            return $this->reponseErreur('Vous ne remplissez pas les préalables requis', 403);
        }
        
        // Inscription
        $utilisateur->parcoursInscrits()->attach($id, [
            'progression_pourcentage' => 0,
            'inscrit_a' => now(),
            'commence_a' => now(),
        ]);
        
        // Créer une notification
        event(new \App\Events\EtudiantInscritParcours($utilisateur, $parcours));
        
        return $this->reponseSucces([
            'message' => 'Inscription au parcours réussie',
            'parcours' => new ParcoursApprentissageRessource($parcours),
        ], 201);
    }
    
    /**
     * Marquer une leçon comme complétée
     */
    public function marquerLeconTerminee(Request $requete, $parcoursId, $leconId): JsonResponse
    {
        $utilisateur = $requete->user();
        
        // Vérifier que l'utilisateur est inscrit au parcours
        $estInscrit = $utilisateur->parcoursInscrits()->where('parcours_id', $parcoursId)->exists();
        
        if (!$estInscrit) {
            return $this->reponseErreur('Vous n\'êtes pas inscrit à ce parcours', 403);
        }
        
        $lecon = Lecon::findOrFail($leconId);
        
        // Marquer la leçon comme terminée
        $progression = $utilisateur->progresLecons()->updateOrCreate(
            ['lecon_id' => $leconId],
            [
                'est_termine' => true,
                'termine_a' => now(),
                'temps_passe_secondes' => $requete->input('temps_passe', 0),
            ]
        );
        
        // Mettre à jour la progression du parcours
        $this->mettreAJourProgressionParcours($utilisateur, $parcoursId);
        
        // Accorder des points
        $utilisateur->ajouterPoints(10); // 10 points par leçon
        
        return $this->reponseSucces([
            'message' => 'Leçon marquée comme terminée',
            'progression' => $progression,
        ]);
    }
    
    /**
     * Récupérer la progression du parcours
     */
    public function progression(Request $requete, $id): JsonResponse
    {
        $utilisateur = $requete->user();
        
        $progression = $utilisateur->parcoursInscrits()
            ->where('parcours_id', $id)
            ->first();
        
        if (!$progression) {
            return $this->reponseErreur('Vous n\'êtes pas inscrit à ce parcours', 404);
        }
        
        $parcours = ParcoursApprentissage::with(['modules.lecons'])->findOrFail($id);
        
        // Calculer la progression détaillée
        $progressionDetaillee = $this->calculerProgressionDetaillee($utilisateur, $parcours);
        
        return $this->reponseSucces([
            'progression_globale' => $progression->pivot->progression_pourcentage,
            'progression_detaillee' => $progressionDetaillee,
            'date_inscription' => $progression->pivot->inscrit_a,
            'date_debut' => $progression->pivot->commence_a,
            'date_fin' => $progression->pivot->termine_a,
        ]);
    }
    
    /**
     * Récupérer le prochain contenu à étudier
     */
    public function prochainContenu(Request $requete, $id): JsonResponse
    {
        $utilisateur = $requete->user();
        
        $parcours = ParcoursApprentissage::with(['modules.lecons'])->findOrFail($id);
        
        // Trouver la prochaine leçon non terminée
        $prochaineLecon = null;
        
        foreach ($parcours->modules as $module) {
            foreach ($module->lecons as $lecon) {
                $estTerminee = $utilisateur->progresLecons()
                    ->where('lecon_id', $lecon->id)
                    ->where('est_termine', true)
                    ->exists();
                
                if (!$estTerminee) {
                    $prochaineLecon = $lecon;
                    break 2;
                }
            }
        }
        
        return $this->reponseSucces([
            'prochaine_lecon' => $prochaineLecon ? new LeconRessource($prochaineLecon) : null,
            'parcours_termine' => $prochaineLecon === null,
        ]);
    }
    
    /**
     * Vérifier les préalables d'un parcours
     */
    private function verifierPrealables($parcours, $utilisateur): bool
    {
        if (empty($parcours->prerequis)) {
            return true;
        }
        
        $parcoursTermines = $utilisateur->parcoursInscrits()
            ->wherePivot('termine_a', '!=', null)
            ->pluck('parcours_id')
            ->toArray();
        
        foreach ($parcours->prerequis as $prerequisId) {
            if (!in_array($prerequisId, $parcoursTermines)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Mettre à jour la progression du parcours
     */
    private function mettreAJourProgressionParcours($utilisateur, $parcoursId): void
    {
        $parcours = ParcoursApprentissage::with(['modules.lecons'])->find($parcoursId);
        
        if (!$parcours) {
            return;
        }
        
        // Compter le nombre total de leçons
        $totalLecons = $parcours->modules->sum(function($module) {
            return $module->lecons->count();
        });
        
        if ($totalLecons === 0) {
            return;
        }
        
        // Compter les leçons terminées par l'utilisateur
        $leconsTerminees = 0;
        
        foreach ($parcours->modules as $module) {
            foreach ($module->lecons as $lecon) {
                $estTerminee = $utilisateur->progresLecons()
                    ->where('lecon_id', $lecon->id)
                    ->where('est_termine', true)
                    ->exists();
                
                if ($estTerminee) {
                    $leconsTerminees++;
                }
            }
        }
        
        // Calculer le pourcentage
        $pourcentage = round(($leconsTerminees / $totalLecons) * 100, 2);
        
        // Mettre à jour la progression
        $utilisateur->parcoursInscrits()->updateExistingPivot($parcoursId, [
            'progression_pourcentage' => $pourcentage,
        ]);
        
        // Si toutes les leçons sont terminées, marquer le parcours comme terminé
        if ($leconsTerminees >= $totalLecons) {
            $utilisateur->parcoursInscrits()->updateExistingPivot($parcoursId, [
                'termine_a' => now(),
            ]);
            
            // Accorder des points
            $utilisateur->ajouterPoints(100); // 100 points pour terminer un parcours
            
            // Événement : Parcours terminé
            event(new \App\Events\ParcoursTermine($utilisateur, $parcours));
        }
    }
    
    /**
     * Calculer la progression détaillée
     */
    private function calculerProgressionDetaillee($utilisateur, $parcours): array
    {
        $progression = [];
        
        foreach ($parcours->modules as $module) {
            $leconsModule = $module->lecons;
            $totalLecons = $leconsModule->count();
            
            if ($totalLecons === 0) {
                continue;
            }
            
            $leconsTerminees = 0;
            
            foreach ($leconsModule as $lecon) {
                $estTerminee = $utilisateur->progresLecons()
                    ->where('lecon_id', $lecon->id)
                    ->where('est_termine', true)
                    ->exists();
                
                if ($estTerminee) {
                    $leconsTerminees++;
                }
            }
            
            $progression[] = [
                'module_id' => $module->id,
                'module_titre' => $module->titre,
                'lecons_terminees' => $leconsTerminees,
                'total_lecons' => $totalLecons,
                'pourcentage' => round(($leconsTerminees / $totalLecons) * 100, 2),
            ];
        }
        
        return $progression;
    }
}