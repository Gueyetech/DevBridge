<?php

namespace App\Http\Controllers\Api\V1\Etudiant;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Http\Requests\Api\V1\Etudiant\RequeteSoumissionQuiz;
use App\Http\Resources\Api\V1\Apprentissage\QuizRessource;
use App\Http\Resources\Api\V1\Apprentissage\QuestionRessource;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\TentativeQuiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ControleurQuiz extends ControleurApiBase
{
    /**
     * Commencer un quiz
     */
    public function commencer(Request $requete, $quizId): JsonResponse
    {
        $quiz = Quiz::with(['questions' => function($query) {
            $query->orderBy('ordre');
        }])->findOrFail($quizId);
        
        $utilisateur = $requete->user();
        
        // Vérifier les tentatives restantes
        $tentatives = $utilisateur->tentativesQuiz()
            ->where('quiz_id', $quizId)
            ->count();
        
        if ($tentatives >= $quiz->tentatives_maximum) {
            return $this->reponseErreur('Nombre maximum de tentatives atteint', 403);
        }
        
        // Vérifier si le quiz est actif
        if (!$quiz->est_actif) {
            return $this->reponseErreur('Ce quiz n\'est plus disponible', 403);
        }
        
        // Créer une nouvelle tentative
        $tentative = TentativeQuiz::create([
            'utilisateur_id' => $utilisateur->id,
            'quiz_id' => $quizId,
            'commence_a' => now(),
            'score_maximum' => $quiz->score_maximum,
        ]);
        
        return $this->reponseSucces([
            'quiz' => new QuizRessource($quiz),
            'tentative_id' => $tentative->id,
            'duree_limite' => $quiz->duree_limite_minutes,
            'tentatives_restantes' => $quiz->tentatives_maximum - $tentatives - 1,
            'score_minimum' => $quiz->score_minimum_reussite,
        ]);
    }
    
    /**
     * Soumettre les réponses d'un quiz
     */
    public function soumettre(RequeteSoumissionQuiz $requete, $quizId, $tentativeId): JsonResponse
    {
        $quiz = Quiz::with('questions')->findOrFail($quizId);
        $tentative = TentativeQuiz::findOrFail($tentativeId);
        $utilisateur = $requete->user();
        
        // Vérifier que la tentative appartient à l'utilisateur
        if ($tentative->utilisateur_id !== $utilisateur->id) {
            return $this->reponseErreur('Cette tentative ne vous appartient pas', 403);
        }
        
        // Vérifier que la tentative n'est pas déjà terminée
        if ($tentative->termine_a) {
            return $this->reponseErreur('Cette tentative est déjà terminée', 400);
        }
        
        // Vérifier le temps limite
        if ($quiz->duree_limite_minutes) {
            $tempsEcoule = now()->diffInMinutes($tentative->commence_a);
            if ($tempsEcoule > $quiz->duree_limite_minutes) {
                $tentative->update(['termine_a' => now()]);
                return $this->reponseErreur('Temps limite dépassé', 400);
            }
        }
        
        $reponses = $requete->validated()['reponses'];
        
        // Évaluer les réponses
        $evaluation = $quiz->evaluerReponses($reponses);
        
        // Mettre à jour la tentative
        $tentative->update([
            'score' => $evaluation['score'],
            'est_reussi' => $evaluation['est_reussi'],
            'reponses' => $reponses,
            'termine_a' => now(),
            'temps_passe_secondes' => now()->diffInSeconds($tentative->commence_a),
        ]);
        
        // Si réussi, accorder des points
        if ($evaluation['est_reussi']) {
            $points = round(($evaluation['score'] / $evaluation['score_maximum']) * 50); // Max 50 points
            $utilisateur->ajouterPoints($points);
            
            // Marquer les compétences acquises si applicable
            $this->validerCompetencesQuiz($utilisateur, $quiz);
        }
        
        return $this->reponseSucces([
            'score' => $evaluation['score'],
            'score_maximum' => $evaluation['score_maximum'],
            'est_reussi' => $evaluation['est_reussi'],
            'pourcentage' => $evaluation['pourcentage'],
            'corrections' => $evaluation['corrections'],
            'points_gagnes' => $evaluation['est_reussi'] ? $points : 0,
            'temps_passe' => $tentative->temps_passe_formate,
        ]);
    }
    
    /**
     * Obtenir les résultats d'une tentative
     */
    public function resultats(Request $requete, $quizId, $tentativeId): JsonResponse
    {
        $tentative = TentativeQuiz::with(['quiz.questions'])->findOrFail($tentativeId);
        $utilisateur = $requete->user();
        
        // Vérifier que la tentative appartient à l'utilisateur
        if ($tentative->utilisateur_id !== $utilisateur->id) {
            return $this->reponseErreur('Cette tentative ne vous appartient pas', 403);
        }
        
        // Recalculer les corrections pour l'affichage
        $quiz = $tentative->quiz;
        $evaluation = $quiz->evaluerReponses($tentative->reponses ?? []);
        
        return $this->reponseSucces([
            'tentative' => $tentative,
            'corrections' => $evaluation['corrections'],
            'statistiques' => [
                'taux_reussite_global' => $quiz->pourcentage_reussite,
                'position_classement' => $this->obtenirPositionClassement($quizId, $utilisateur->id),
            ],
        ]);
    }
    
    /**
     * Lister les tentatives d'un utilisateur
     */
    public function tentatives(Request $requete, $quizId = null): JsonResponse
    {
        $utilisateur = $requete->user();
        $query = $utilisateur->tentativesQuiz()->with('quiz');
        
        if ($quizId) {
            $query->where('quiz_id', $quizId);
        }
        
        $tentatives = $query->orderByDesc('termine_a')->paginate(10);
        
        return $this->reponseSucces([
            'tentatives' => $tentatives,
            'statistiques' => [
                'total_tentatives' => $query->count(),
                'tentatives_reussies' => $query->where('est_reussi', true)->count(),
                'meilleur_score' => $query->max('score'),
                'score_moyen' => round($query->avg('score'), 2),
            ],
        ]);
    }
    
    /**
     * Obtenir le classement d'un quiz
     */
    public function classement(Request $requete, $quizId): JsonResponse
    {
        $quiz = Quiz::findOrFail($quizId);
        
        $classement = TentativeQuiz::where('quiz_id', $quizId)
            ->where('est_reussi', true)
            ->selectRaw('utilisateur_id, MAX(score) as meilleur_score, MIN(temps_passe_secondes) as meilleur_temps')
            ->groupBy('utilisateur_id')
            ->with('utilisateur')
            ->orderByDesc('meilleur_score')
            ->orderBy('meilleur_temps')
            ->limit(20)
            ->get()
            ->map(function($item, $index) {
                return [
                    'position' => $index + 1,
                    'utilisateur' => $item->utilisateur->nom_complet,
                    'score' => $item->meilleur_score,
                    'temps' => $item->meilleur_temps,
                ];
            });
        
        $positionUtilisateur = $this->obtenirPositionClassement($quizId, $requete->user()->id);
        
        return $this->reponseSucces([
            'classement' => $classement,
            'position_utilisateur' => $positionUtilisateur,
            'total_participants' => TentativeQuiz::where('quiz_id', $quizId)
                ->where('est_reussi', true)
                ->distinct('utilisateur_id')
                ->count(),
        ]);
    }
    
    /**
     * Valider les compétences après un quiz réussi
     */
    private function validerCompetencesQuiz($utilisateur, $quiz): void
    {
        // Cette méthode dépendrait de la liaison entre quiz et compétences
        // Pour l'instant, c'est un placeholder
        if ($quiz->module) {
            $parcours = $quiz->module->parcours;
            if ($parcours && $parcours->competences_acquises) {
                foreach ($parcours->competences_acquises as $competenceId) {
                    $existe = DB::table('competences_utilisateurs')
                        ->where('utilisateur_id', $utilisateur->id)
                        ->where('competence_id', $competenceId)
                        ->exists();

                    if ($existe) {
                        DB::table('competences_utilisateurs')
                            ->where('utilisateur_id', $utilisateur->id)
                            ->where('competence_id', $competenceId)
                            ->update([
                                'valide_a' => now(),
                                'methode_validation' => 'quiz',
                                'niveau_maitrise' => 1,
                                'updated_at' => now(),
                            ]);
                    } else {
                        DB::table('competences_utilisateurs')->insert([
                            'id' => (string) Str::uuid(),
                            'utilisateur_id' => $utilisateur->id,
                            'competence_id' => $competenceId,
                            'valide_a' => now(),
                            'valide_par' => null,
                            'methode_validation' => 'quiz',
                            'niveau_maitrise' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
    
    /**
     * Obtenir la position d'un utilisateur dans le classement
     */
    private function obtenirPositionClassement($quizId, $utilisateurId): ?int
    {
        $meilleureTentative = TentativeQuiz::where('quiz_id', $quizId)
            ->where('utilisateur_id', $utilisateurId)
            ->where('est_reussi', true)
            ->orderByDesc('score')
            ->orderBy('temps_passe_secondes')
            ->first();
        
        if (!$meilleureTentative) {
            return null;
        }
        
        $position = TentativeQuiz::where('quiz_id', $quizId)
            ->where('est_reussi', true)
            ->where(function($query) use ($meilleureTentative) {
                $query->where('score', '>', $meilleureTentative->score)
                      ->orWhere(function($q) use ($meilleureTentative) {
                          $q->where('score', '=', $meilleureTentative->score)
                            ->where('temps_passe_secondes', '<', $meilleureTentative->temps_passe_secondes);
                      });
            })
            ->distinct('utilisateur_id')
            ->count() + 1;
        
        return $position;
    }
}