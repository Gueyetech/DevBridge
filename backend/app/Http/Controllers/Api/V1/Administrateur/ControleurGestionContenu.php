<?php

namespace App\Http\Controllers\Api\V1\Administrateur;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Http\Requests\Api\V1\Administrateur\RequeteCreerParcours;
use App\Http\Requests\Api\V1\Administrateur\RequeteMettreAJourParcours;
use App\Http\Resources\Api\V1\Apprentissage\ParcoursApprentissageRessource;
use App\Http\Resources\Api\V1\Apprentissage\ModuleRessource;
use App\Http\Resources\Api\V1\Apprentissage\LeconRessource;
use App\Models\ParcoursApprentissage;
use App\Models\Module;
use App\Models\Lecon;
use App\Models\Quiz;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControleurGestionContenu extends ControleurApiBase
{
    /**
     * Lister tous les parcours (avec drafts)
     */
    public function index(Request $requete): JsonResponse
    {
        $query = ParcoursApprentissage::with(['modules.lecons']);

        // Filtres
        if ($requete->has('est_publie')) {
            $query->where('est_publie', $requete->est_publie === 'true');
        }

        if ($requete->has('technologie')) {
            $query->where('technologie', $requete->technologie);
        }

        if ($requete->has('difficulte')) {
            $query->where('difficulte', $requete->difficulte);
        }

        if ($requete->has('recherche')) {
            $query->where(function ($q) use ($requete) {
                $q->where('titre', 'like', "%{$requete->recherche}%")
                    ->orWhere('description', 'like', "%{$requete->recherche}%");
            });
        }

        // Tri
        $tri = $requete->input('tri', 'created_at');
        $direction = $requete->input('direction', 'desc');

        $parcours = $query->orderBy($tri, $direction)
            ->paginate($requete->input('per_page', 20));

        $statistiques = [
            'total' => ParcoursApprentissage::count(),
            'publies' => ParcoursApprentissage::where('est_publie', true)->count(),
            'brouillons' => ParcoursApprentissage::where('est_publie', false)->count(),
            'par_difficulte' => ParcoursApprentissage::selectRaw('difficulte, COUNT(*) as count')
                ->groupBy('difficulte')
                ->pluck('count', 'difficulte'),
        ];

        return $this->reponseSucces([
            'parcours' => ParcoursApprentissageRessource::collection($parcours),
            'statistiques' => $statistiques,
            'meta' => [
                'total' => $parcours->total(),
                'par_page' => $parcours->perPage(),
                'page_courante' => $parcours->currentPage(),
                'derniere_page' => $parcours->lastPage(),
            ],
        ]);
    }

    /**
     * Créer un nouveau parcours
     */
    public function store(RequeteCreerParcours $requete): JsonResponse
    {
        $donnees = $requete->validated();

        DB::beginTransaction();

        try {
            $parcours = ParcoursApprentissage::create([
                'titre' => $donnees['titre'],
                'description' => $donnees['description'],
                'technologie' => $donnees['technologie'],
                'difficulte' => $donnees['difficulte'],
                'duree_estimee_heures' => $donnees['duree_estimee_heures'] ?? 0,
                'image_couverture' => $donnees['image_couverture'] ?? null,
                'prerequis' => $donnees['prerequis'] ?? [],
                'objectifs' => $donnees['objectifs'] ?? [],
                'est_publie' => $donnees['est_publie'] ?? false,
                'ordre' => $donnees['ordre'] ?? 0,
                'createur_id' => $requete->user()->id,
            ]);

            // Créer les modules si fournis
            if (isset($donnees['modules'])) {
                foreach ($donnees['modules'] as $index => $moduleData) {
                    $module = $parcours->modules()->create([
                        'titre' => $moduleData['titre'],
                        'description' => $moduleData['description'] ?? null,
                        'ordre' => $moduleData['ordre'] ?? $index + 1,
                    ]);

                    // Créer les leçons du module si fournies
                    if (isset($moduleData['lecons'])) {
                        foreach ($moduleData['lecons'] as $leconIndex => $leconData) {
                            $module->lecons()->create([
                                'titre' => $leconData['titre'],
                                'contenu' => $leconData['contenu'] ?? null,
                                'type' => $leconData['type'] ?? 'texte',
                                'duree_estimee_minutes' => $leconData['duree_estimee_minutes'] ?? 15,
                                'ordre' => $leconData['ordre'] ?? $leconIndex + 1,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return $this->reponseCree([
                'message' => 'Parcours créé avec succès',
                'parcours' => new ParcoursApprentissageRessource($parcours->load(['modules.lecons'])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->reponseErreur('Erreur lors de la création du parcours: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Afficher un parcours spécifique
     */
    public function show(string $id): JsonResponse
    {
        $parcours = ParcoursApprentissage::with([
            'modules' => function ($query) {
                $query->orderBy('ordre')->with(['lecons' => function ($q) {
                    $q->orderBy('ordre');
                }, 'quiz.questions']);
            },
        ])->findOrFail($id);

        $statistiques = [
            'total_modules' => $parcours->modules->count(),
            'total_lecons' => $parcours->modules->sum(fn($m) => $m->lecons->count()),
            'total_quiz' => $parcours->modules->sum(fn($m) => $m->quiz ? 1 : 0),
            'inscrits' => $parcours->utilisateursInscrits()->count(),
            'taux_completion' => $this->calculerTauxCompletion($parcours),
        ];

        return $this->reponseSucces([
            'parcours' => new ParcoursApprentissageRessource($parcours),
            'statistiques' => $statistiques,
        ]);
    }

    /**
     * Mettre à jour un parcours
     */
    public function update(RequeteMettreAJourParcours $requete, string $id): JsonResponse
    {
        $parcours = ParcoursApprentissage::findOrFail($id);
        $donnees = $requete->validated();

        DB::beginTransaction();

        try {
            $parcours->update([
                'titre' => $donnees['titre'] ?? $parcours->titre,
                'description' => $donnees['description'] ?? $parcours->description,
                'technologie' => $donnees['technologie'] ?? $parcours->technologie,
                'difficulte' => $donnees['difficulte'] ?? $parcours->difficulte,
                'duree_estimee_heures' => $donnees['duree_estimee_heures'] ?? $parcours->duree_estimee_heures,
                'image_couverture' => $donnees['image_couverture'] ?? $parcours->image_couverture,
                'prerequis' => $donnees['prerequis'] ?? $parcours->prerequis,
                'objectifs' => $donnees['objectifs'] ?? $parcours->objectifs,
                'est_publie' => $donnees['est_publie'] ?? $parcours->est_publie,
                'ordre' => $donnees['ordre'] ?? $parcours->ordre,
            ]);

            DB::commit();

            return $this->reponseSucces([
                'message' => 'Parcours mis à jour avec succès',
                'parcours' => new ParcoursApprentissageRessource($parcours->fresh(['modules.lecons'])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->reponseErreur('Erreur lors de la mise à jour: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Supprimer un parcours
     */
    public function destroy(string $id): JsonResponse
    {
        $parcours = ParcoursApprentissage::findOrFail($id);

        // Vérifier s'il y a des utilisateurs inscrits
        if ($parcours->utilisateursInscrits()->count() > 0) {
            return $this->reponseErreur('Impossible de supprimer un parcours avec des utilisateurs inscrits', 400);
        }

        DB::beginTransaction();

        try {
            // Supprimer les modules et leçons associés
            foreach ($parcours->modules as $module) {
                $module->lecons()->delete();
                if ($module->quiz) {
                    $module->quiz->questions()->delete();
                    $module->quiz->delete();
                }
            }
            $parcours->modules()->delete();
            $parcours->delete();

            DB::commit();

            return $this->reponseSupprime();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->reponseErreur('Erreur lors de la suppression: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Publier ou dépublier un parcours
     */
    public function togglePublication(string $id): JsonResponse
    {
        $parcours = ParcoursApprentissage::findOrFail($id);

        // Vérifier qu'il y a du contenu avant de publier
        if (!$parcours->est_publie && $parcours->modules()->count() === 0) {
            return $this->reponseErreur('Impossible de publier un parcours sans modules', 400);
        }

        $parcours->update(['est_publie' => !$parcours->est_publie]);

        return $this->reponseSucces([
            'message' => $parcours->est_publie ? 'Parcours publié' : 'Parcours dépublié',
            'est_publie' => $parcours->est_publie,
        ]);
    }

    /**
     * Dupliquer un parcours
     */
    public function dupliquer(string $id): JsonResponse
    {
        $original = ParcoursApprentissage::with(['modules.lecons', 'modules.quiz.questions'])->findOrFail($id);

        DB::beginTransaction();

        try {
            $copie = $original->replicate();
            $copie->titre = $original->titre . ' (copie)';
            $copie->est_publie = false;
            $copie->save();

            foreach ($original->modules as $module) {
                $moduleCopie = $module->replicate();
                $moduleCopie->parcours_id = $copie->id;
                $moduleCopie->save();

                foreach ($module->lecons as $lecon) {
                    $leconCopie = $lecon->replicate();
                    $leconCopie->module_id = $moduleCopie->id;
                    $leconCopie->save();
                }

                if ($module->quiz) {
                    $quizCopie = $module->quiz->replicate();
                    $quizCopie->module_id = $moduleCopie->id;
                    $quizCopie->save();

                    foreach ($module->quiz->questions as $question) {
                        $questionCopie = $question->replicate();
                        $questionCopie->quiz_id = $quizCopie->id;
                        $questionCopie->save();
                    }
                }
            }

            DB::commit();

            return $this->reponseCree([
                'message' => 'Parcours dupliqué avec succès',
                'parcours' => new ParcoursApprentissageRessource($copie->load(['modules.lecons'])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->reponseErreur('Erreur lors de la duplication: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Gérer les modules d'un parcours
     */
    public function modules(string $parcoursId): JsonResponse
    {
        $parcours = ParcoursApprentissage::findOrFail($parcoursId);
        $modules = $parcours->modules()->with('lecons')->orderBy('ordre')->get();

        return $this->reponseSucces([
            'modules' => ModuleRessource::collection($modules),
        ]);
    }

    /**
     * Ajouter un module à un parcours
     */
    public function ajouterModule(Request $requete, string $parcoursId): JsonResponse
    {
        $parcours = ParcoursApprentissage::findOrFail($parcoursId);

        $requete->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ordre' => 'nullable|integer|min:1',
        ]);

        $module = $parcours->modules()->create([
            'titre' => $requete->titre,
            'description' => $requete->description,
            'ordre' => $requete->ordre ?? $parcours->modules()->max('ordre') + 1,
        ]);

        return $this->reponseCree([
            'message' => 'Module ajouté avec succès',
            'module' => new ModuleRessource($module),
        ]);
    }

    /**
     * Ajouter une leçon à un module
     */
    public function ajouterLecon(Request $requete, string $moduleId): JsonResponse
    {
        $module = Module::findOrFail($moduleId);

        $requete->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'nullable|string',
            'type' => 'nullable|in:texte,video,exercice,code',
            'duree_estimee_minutes' => 'nullable|integer|min:1',
            'ordre' => 'nullable|integer|min:1',
        ]);

        $lecon = $module->lecons()->create([
            'titre' => $requete->titre,
            'contenu' => $requete->contenu,
            'type' => $requete->type ?? 'texte',
            'duree_estimee_minutes' => $requete->duree_estimee_minutes ?? 15,
            'ordre' => $requete->ordre ?? $module->lecons()->max('ordre') + 1,
        ]);

        return $this->reponseCree([
            'message' => 'Leçon ajoutée avec succès',
            'lecon' => new LeconRessource($lecon),
        ]);
    }

    /**
     * Réorganiser les modules
     */
    public function reorganiserModules(Request $requete, string $parcoursId): JsonResponse
    {
        $requete->validate([
            'modules' => 'required|array',
            'modules.*.id' => 'required|exists:modules,id',
            'modules.*.ordre' => 'required|integer|min:1',
        ]);

        foreach ($requete->modules as $moduleData) {
            Module::where('id', $moduleData['id'])->update(['ordre' => $moduleData['ordre']]);
        }

        return $this->reponseSucces([
            'message' => 'Modules réorganisés avec succès',
        ]);
    }

    /**
     * Calculer le taux de complétion d'un parcours
     */
    private function calculerTauxCompletion(ParcoursApprentissage $parcours): float
    {
        $inscrits = $parcours->utilisateursInscrits()->count();
        if ($inscrits === 0) {
            return 0;
        }

        $termines = $parcours->utilisateursInscrits()
            ->wherePivot('termine_a', '!=', null)
            ->count();

        return round(($termines / $inscrits) * 100, 2);
    }

    // ==================== ALIAS POUR ROUTES ADMIN PARCOURS ====================

    /**
     * Alias pour index - Liste des parcours
     */
    public function parcoursIndex(Request $requete): JsonResponse
    {
        return $this->index($requete);
    }

    /**
     * Alias pour store - Créer un parcours
     */
    public function parcoursCreer(RequeteCreerParcours $requete): JsonResponse
    {
        return $this->store($requete);
    }

    /**
     * Alias pour show - Afficher un parcours
     */
    public function parcoursAfficher(string $id): JsonResponse
    {
        return $this->show($id);
    }

    /**
     * Alias pour update - Mettre à jour un parcours
     */
    public function parcoursMettreAJour(RequeteMettreAJourParcours $requete, string $id): JsonResponse
    {
        return $this->update($requete, $id);
    }

    /**
     * Alias pour destroy - Supprimer un parcours
     */
    public function parcoursSupprimer(string $id): JsonResponse
    {
        return $this->destroy($id);
    }

    /**
     * Publier un parcours
     */
    public function parcoursPublier(string $id): JsonResponse
    {
        $parcours = ParcoursApprentissage::findOrFail($id);

        if ($parcours->modules()->count() === 0) {
            return $this->reponseErreur('Impossible de publier un parcours sans modules', 400);
        }

        $parcours->update(['est_publie' => true]);

        return $this->reponseSucces([
            'message' => 'Parcours publié avec succès',
            'est_publie' => true,
        ]);
    }

    /**
     * Dépublier un parcours
     */
    public function parcoursDepublier(string $id): JsonResponse
    {
        $parcours = ParcoursApprentissage::findOrFail($id);
        $parcours->update(['est_publie' => false]);

        return $this->reponseSucces([
            'message' => 'Parcours dépublié avec succès',
            'est_publie' => false,
        ]);
    }

    /**
     * Alias pour modules - Liste des modules d'un parcours
     */
    public function modulesIndex(string $parcoursId): JsonResponse
    {
        return $this->modules($parcoursId);
    }

    /**
     * Créer un module
     */
    public function moduleCreer(Request $requete, string $parcoursId): JsonResponse
    {
        return $this->ajouterModule($requete, $parcoursId);
    }

    /**
     * Afficher un module
     */
    public function moduleAfficher(string $parcoursId, string $moduleId): JsonResponse
    {
        $module = Module::with('lecons')->findOrFail($moduleId);
        return $this->reponseSucces([
            'module' => new ModuleRessource($module),
        ]);
    }

    /**
     * Mettre à jour un module
     */
    public function moduleMettreAJour(Request $requete, string $parcoursId, string $moduleId): JsonResponse
    {
        $module = Module::findOrFail($moduleId);

        $requete->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'ordre' => 'nullable|integer|min:1',
        ]);

        $module->update($requete->only(['titre', 'description', 'ordre']));

        return $this->reponseSucces([
            'message' => 'Module mis à jour avec succès',
            'module' => new ModuleRessource($module->fresh()),
        ]);
    }

    /**
     * Supprimer un module
     */
    public function moduleSupprimer(string $parcoursId, string $moduleId): JsonResponse
    {
        $module = Module::findOrFail($moduleId);
        $module->lecons()->delete();
        if ($module->quiz) {
            $module->quiz->questions()->delete();
            $module->quiz->delete();
        }
        $module->delete();

        return $this->reponseSupprime();
    }

    // ==================== LEÇONS ====================

    /**
     * Liste des leçons d'un module
     */
    public function leconsIndex(string $parcoursId, string $moduleId): JsonResponse
    {
        $module = Module::findOrFail($moduleId);
        $lecons = $module->lecons()->orderBy('ordre')->get();

        return $this->reponseSucces([
            'lecons' => LeconRessource::collection($lecons),
        ]);
    }

    /**
     * Créer une leçon
     */
    public function leconCreer(Request $requete, string $parcoursId, string $moduleId): JsonResponse
    {
        return $this->ajouterLecon($requete, $moduleId);
    }

    /**
     * Afficher une leçon
     */
    public function leconAfficher(string $parcoursId, string $moduleId, string $leconId): JsonResponse
    {
        $lecon = Lecon::findOrFail($leconId);
        return $this->reponseSucces([
            'lecon' => new LeconRessource($lecon),
        ]);
    }

    /**
     * Mettre à jour une leçon
     */
    public function leconMettreAJour(Request $requete, string $parcoursId, string $moduleId, string $leconId): JsonResponse
    {
        $lecon = Lecon::findOrFail($leconId);

        $requete->validate([
            'titre' => 'sometimes|string|max:255',
            'contenu' => 'nullable|string',
            'type_contenu' => 'nullable|in:article,video,exercice,projet',
            'duree_estimee_minutes' => 'nullable|integer|min:1',
            'ordre' => 'nullable|integer|min:1',
        ]);

        $lecon->update($requete->only(['titre', 'contenu', 'type_contenu', 'duree_estimee_minutes', 'ordre']));

        return $this->reponseSucces([
            'message' => 'Leçon mise à jour avec succès',
            'lecon' => new LeconRessource($lecon->fresh()),
        ]);
    }

    /**
     * Supprimer une leçon
     */
    public function leconSupprimer(string $parcoursId, string $moduleId, string $leconId): JsonResponse
    {
        $lecon = Lecon::findOrFail($leconId);
        $lecon->delete();

        return $this->reponseSupprime();
    }

    // ==================== QUIZ ====================

    /**
     * Liste des quiz d'un module
     */
    public function quizIndex(string $parcoursId, string $moduleId): JsonResponse
    {
        $module = Module::findOrFail($moduleId);
        $quiz = $module->quiz;

        return $this->reponseSucces([
            'quiz' => $quiz,
        ]);
    }

    /**
     * Créer un quiz
     */
    public function quizCreer(Request $requete, string $parcoursId, string $moduleId): JsonResponse
    {
        $module = Module::findOrFail($moduleId);

        $requete->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duree_minutes' => 'nullable|integer|min:1',
            'score_minimum' => 'nullable|integer|min:0|max:100',
        ]);

        $quiz = Quiz::create([
            'module_id' => $moduleId,
            'titre' => $requete->titre,
            'description' => $requete->description,
            'duree_minutes' => $requete->duree_minutes ?? 30,
            'score_minimum' => $requete->score_minimum ?? 70,
        ]);

        return $this->reponseCree([
            'message' => 'Quiz créé avec succès',
            'quiz' => $quiz,
        ]);
    }

    /**
     * Afficher un quiz
     */
    public function quizAfficher(string $parcoursId, string $moduleId, string $quizId): JsonResponse
    {
        $quiz = Quiz::with('questions')->findOrFail($quizId);
        return $this->reponseSucces([
            'quiz' => $quiz,
        ]);
    }

    /**
     * Mettre à jour un quiz
     */
    public function quizMettreAJour(Request $requete, string $parcoursId, string $moduleId, string $quizId): JsonResponse
    {
        $quiz = Quiz::findOrFail($quizId);

        $requete->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'duree_minutes' => 'nullable|integer|min:1',
            'score_minimum' => 'nullable|integer|min:0|max:100',
        ]);

        $quiz->update($requete->only(['titre', 'description', 'duree_minutes', 'score_minimum']));

        return $this->reponseSucces([
            'message' => 'Quiz mis à jour avec succès',
            'quiz' => $quiz->fresh(),
        ]);
    }

    /**
     * Supprimer un quiz
     */
    public function quizSupprimer(string $parcoursId, string $moduleId, string $quizId): JsonResponse
    {
        $quiz = Quiz::findOrFail($quizId);
        $quiz->questions()->delete();
        $quiz->delete();

        return $this->reponseSupprime();
    }
}
