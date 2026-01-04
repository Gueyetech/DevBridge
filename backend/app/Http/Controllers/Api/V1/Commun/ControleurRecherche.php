<?php

namespace App\Http\Controllers\Api\V1\Commun;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Http\Resources\Api\V1\Apprentissage\ParcoursApprentissageRessource;
use App\Http\Resources\Api\V1\Projet\ProjetRessource;
use App\Http\Resources\Api\V1\UtilisateurRessource;
use App\Http\Resources\Api\V1\Apprentissage\CompetenceRessource;
use App\Models\ParcoursApprentissage;
use App\Models\Projet;
use App\Models\Utilisateur;
use App\Models\Competence;
use App\Models\DiscussionForum;
use App\Models\Defi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControleurRecherche extends ControleurApiBase
{
    /**
     * Recherche globale
     */
    public function rechercheGlobale(Request $requete): JsonResponse
    {
        $query = $requete->input('q', '');
        $limit = $requete->input('limit', 10);
        
        if (empty($query) || strlen($query) < 2) {
            return $this->reponseErreur('La recherche doit contenir au moins 2 caractères', 400);
        }
        
        $resultats = [
            'parcours' => $this->rechercherParcours($query, $limit),
            'projets' => $this->rechercherProjets($query, $limit),
            'utilisateurs' => $this->rechercherUtilisateurs($query, $limit),
            'competences' => $this->rechercherCompetences($query, $limit),
            'discussions' => $this->rechercherDiscussions($query, $limit),
            'defis' => $this->rechercherDefis($query, $limit),
        ];
        
        // Statistiques
        $totalResultats = collect($resultats)->sum(function($items) {
            return count($items);
        });
        
        // Enregistrer la recherche
        $this->enregistrerRecherche($query, $requete->user());
        
        return $this->reponseSucces([
            'resultats' => $resultats,
            'total' => $totalResultats,
            'requete' => $query,
            'suggestions' => $this->obtenirSuggestions($query),
        ]);
    }
    
    /**
     * Recherche de parcours
     */
    public function rechercheParcours(Request $requete): JsonResponse
    {
        $query = ParcoursApprentissage::with(['modules.lecons'])
            ->where('est_publie', true);
        
        // Recherche par texte
        if ($requete->has('q')) {
            $query->where(function($q) use ($requete) {
                $q->where('titre', 'like', "%{$requete->q}%")
                  ->orWhere('description', 'like', "%{$requete->q}%")
                  ->orWhere('technologie', 'like', "%{$requete->q}%");
            });
        }
        
        // Filtres
        if ($requete->has('technologie')) {
            $query->where('technologie', $requete->technologie);
        }
        
        if ($requete->has('difficulte')) {
            $query->where('difficulte', $requete->difficulte);
        }
        
        if ($requete->has('competence_id')) {
            $query->whereHas('competencesLiees', function($q) use ($requete) {
                $q->where('competences.id', $requete->competence_id);
            });
        }
        
        // Tri
        $tri = $requete->input('tri', 'popularite');
        $direction = $requete->input('direction', 'desc');
        
        if ($tri === 'popularite') {
            $query->withCount('utilisateursInscrits')
                  ->orderBy('utilisateurs_inscrits_count', $direction);
        } elseif ($tri === 'date') {
            $query->orderBy('created_at', $direction);
        } elseif ($tri === 'duree') {
            $query->orderBy('duree_estimee_heures', $direction);
        } else {
            $query->orderBy($tri, $direction);
        }
        
        $parcours = $query->paginate($requete->input('per_page', 12));
        
        // Suggestions de technologies
        $technologies = ParcoursApprentissage::where('est_publie', true)
            ->select('technologie')
            ->distinct()
            ->orderBy('technologie')
            ->pluck('technologie');
        
        return $this->reponseSucces([
            'parcours' => ParcoursApprentissageRessource::collection($parcours),
            'filtres' => [
                'technologies' => $technologies,
                'difficultes' => ['debutant', 'intermediaire', 'avance'],
            ],
            'meta' => [
                'total' => $parcours->total(),
                'par_page' => $parcours->perPage(),
                'page_courante' => $parcours->currentPage(),
            ],
        ]);
    }
    
    /**
     * Recherche de projets
     */
    public function rechercheProjets(Request $requete): JsonResponse
    {
        $query = Projet::with(['createur.profil', 'membres']);
        
        // Recherche par texte
        if ($requete->has('q')) {
            $query->where(function($q) use ($requete) {
                $q->where('titre', 'like', "%{$requete->q}%")
                  ->orWhere('description', 'like', "%{$requete->q}%");
            });
        }
        
        // Filtres
        if ($requete->has('statut')) {
            $query->where('statut', $requete->statut);
        }
        
        if ($requete->has('difficulte')) {
            $query->where('difficulte', $requete->difficulte);
        }
        
        if ($requete->has('technologie')) {
            $query->whereJsonContains('technologies', $requete->technologie);
        }
        
        if ($requete->has('competence_id')) {
            $query->whereHas('competencesRequises', function($q) use ($requete) {
                $q->where('competences.id', $requete->competence_id);
            });
        }
        
        // Projets avec places disponibles
        if ($requete->has('places_disponibles') && $requete->places_disponibles === 'true') {
            $query->whereRaw('(SELECT COUNT(*) FROM membres_projets WHERE projet_id = projets.id) < projets.nombre_maximum_participants');
        }
        
        // Tri
        $tri = $requete->input('tri', 'date');
        $direction = $requete->input('direction', 'desc');
        
        $query->orderBy($tri, $direction);
        
        $projets = $query->paginate($requete->input('per_page', 12));
        
        // Technologies disponibles
        $technologies = Projet::select('technologies')
            ->get()
            ->flatMap(function($projet) {
                return $projet->technologies ?? [];
            })
            ->unique()
            ->sort()
            ->values();
        
        return $this->reponseSucces([
            'projets' => ProjetRessource::collection($projets),
            'filtres' => [
                'statuts' => ['brouillon', 'ouvert', 'en_cours', 'en_revision', 'termine'],
                'difficultes' => ['debutant', 'intermediaire', 'avance'],
                'technologies' => $technologies,
            ],
            'meta' => [
                'total' => $projets->total(),
                'par_page' => $projets->perPage(),
                'page_courante' => $projets->currentPage(),
            ],
        ]);
    }
    
    /**
     * Recherche d'utilisateurs
     */
    public function rechercheUtilisateurs(Request $requete): JsonResponse
    {
        $query = Utilisateur::with('profil')
            ->where('est_actif', true);
        
        // Recherche par texte
        if ($requete->has('q')) {
            $query->where(function($q) use ($requete) {
                $q->where('prenom', 'like', "%{$requete->q}%")
                  ->orWhere('nom', 'like', "%{$requete->q}%")
                  ->orWhere('email', 'like', "%{$requete->q}%");
            });
        }
        
        // Filtres
        if ($requete->has('role')) {
            $query->where('role', $requete->role);
        }
        
        if ($requete->has('niveau')) {
            $query->whereHas('profil', function($q) use ($requete) {
                $q->where('niveau', $requete->niveau);
            });
        }
        
        if ($requete->has('technologie')) {
            $query->whereHas('profil', function($q) use ($requete) {
                $q->whereJsonContains('technologies', $requete->technologie);
            });
        }
        
        if ($requete->has('competence_id')) {
            $query->whereHas('competences', function($q) use ($requete) {
                $q->where('competences.id', $requete->competence_id);
            });
        }
        
        // Mentor disponible
        if ($requete->has('mentor_disponible') && $requete->mentor_disponible === 'true') {
            $query->whereHas('profil', function($q) {
                $q->where('est_disponible_mentorat', true);
            })->where('role', 'mentor');
        }
        
        // Tri
        $tri = $requete->input('tri', 'nom');
        $direction = $requete->input('direction', 'asc');
        
        $query->orderBy($tri, $direction);
        
        $utilisateurs = $query->paginate($requete->input('per_page', 20));
        
        return $this->reponseSucces([
            'utilisateurs' => UtilisateurRessource::collection($utilisateurs),
            'filtres' => [
                'roles' => ['etudiant', 'mentor', 'administrateur'],
                'niveaux' => ['debutant', 'intermediaire', 'avance'],
            ],
            'meta' => [
                'total' => $utilisateurs->total(),
                'par_page' => $utilisateurs->perPage(),
                'page_courante' => $utilisateurs->currentPage(),
            ],
        ]);
    }
    
    /**
     * Recherche de compétences
     */
    public function rechercheCompetences(Request $requete): JsonResponse
    {
        $query = Competence::withCount(['utilisateurs', 'parcours']);
        
        // Recherche par texte
        if ($requete->has('q')) {
            $query->where(function($q) use ($requete) {
                $q->where('nom', 'like', "%{$requete->q}%")
                  ->orWhere('description', 'like', "%{$requete->q}%");
            });
        }
        
        // Filtres
        if ($requete->has('categorie')) {
            $query->where('categorie', $requete->categorie);
        }
        
        if ($requete->has('niveau')) {
            $query->where('niveau', $requete->niveau);
        }
        
        // Tri
        $tri = $requete->input('tri', 'popularite');
        $direction = $requete->input('direction', 'desc');
        
        if ($tri === 'popularite') {
            $query->orderBy('utilisateurs_count', $direction);
        } else {
            $query->orderBy($tri, $direction);
        }
        
        $competences = $query->paginate($requete->input('per_page', 20));
        
        return $this->reponseSucces([
            'competences' => CompetenceRessource::collection($competences),
            'filtres' => [
                'categories' => ['frontend', 'backend', 'base_de_donnees', 'devops', 'outils', 'soft_skills'],
                'niveaux' => ['debutant', 'intermediaire', 'avance'],
            ],
            'meta' => [
                'total' => $competences->total(),
                'par_page' => $competences->perPage(),
                'page_courante' => $competences->currentPage(),
            ],
        ]);
    }
    
    /**
     * Soumettre un feedback
     */
    public function soumettreFeedback(Request $requete): JsonResponse
    {
        $requete->validate([
            'type' => 'required|in:bug,amelioration,suggestion,autre',
            'contenu' => 'required|string|min:10|max:2000',
            'urgence' => 'required|in:basse,moyenne,haute',
            'page_url' => 'nullable|url',
            'capture_ecran' => 'nullable|url',
        ]);
        
        $utilisateur = $requete->user();
        
        DB::table('feedbacks')->insert([
            'utilisateur_id' => $utilisateur->id,
            'type' => $requete->type,
            'contenu' => $requete->contenu,
            'urgence' => $requete->urgence,
            'page_url' => $requete->page_url,
            'capture_ecran' => $requete->capture_ecran,
            'statut' => 'nouveau',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Notifier les administrateurs
        event(new \App\Events\FeedbackSoumis($utilisateur, $requete->type, $requete->urgence));
        
        return $this->reponseSucces([
            'message' => 'Feedback soumis avec succès. Merci pour votre contribution!',
        ], 201);
    }
    
    /**
     * Signaler un problème
     */
    public function signalerProbleme(Request $requete): JsonResponse
    {
        $requete->validate([
            'type_probleme' => 'required|in:contenu,utilisateur,technique,securite,autre',
            'description' => 'required|string|min:20|max:2000',
            'element_id' => 'nullable',
            'element_type' => 'nullable|in:parcours,projet,utilisateur,discussion,message',
            'preuves' => 'nullable|array',
            'preuves.*' => 'url',
        ]);
        
        $utilisateur = $requete->user();
        
        DB::table('signalements')->insert([
            'utilisateur_id' => $utilisateur->id,
            'type_probleme' => $requete->type_probleme,
            'description' => $requete->description,
            'element_id' => $requete->element_id,
            'element_type' => $requete->element_type,
            'preuves' => json_encode($requete->preuves ?? []),
            'statut' => 'en_attente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return $this->reponseSucces([
            'message' => 'Problème signalé aux administrateurs',
        ], 201);
    }
    
    /**
     * FAQ (Foire Aux Questions)
     */
    public function faq(Request $requete): JsonResponse
    {
        $categories = [
            'general' => [
                'Qu\'est-ce que DevBridge?',
                'Comment s\'inscrire?',
                'Est-ce gratuit?',
            ],
            'apprentissage' => [
                'Comment fonctionnent les parcours?',
                'Comment valider mes compétences?',
                'Puis-je sauter des leçons?',
            ],
            'projets' => [
                'Comment rejoindre un projet?',
                'Comment créer mon propre projet?',
                'Comment fonctionne la collaboration?',
            ],
            'mentorat' => [
                'Comment trouver un mentor?',
                'Comment devenir mentor?',
                'Quelles sont les attentes?',
            ],
            'technique' => [
                'Problèmes de connexion',
                'Problèmes de vidéo/audio',
                'Problèmes de téléchargement',
            ],
        ];
        
        $questions = DB::table('faq')
            ->where('est_actif', true)
            ->orderBy('ordre')
            ->get()
            ->groupBy('categorie');
        
        return $this->reponseSucces([
            'categories' => $categories,
            'questions' => $questions,
            'recherche_populaire' => $this->obtenirRecherchesPopulaires(),
        ]);
    }
    
    /**
     * Obtenir les suggestions de recherche
     */
    public function suggestions(Request $requete): JsonResponse
    {
        $query = $requete->input('q', '');
        
        if (strlen($query) < 2) {
            return $this->reponseSucces(['suggestions' => []]);
        }
        
        $suggestions = $this->obtenirSuggestions($query);
        
        return $this->reponseSucces([
            'suggestions' => $suggestions,
            'requete' => $query,
        ]);
    }
    
    // ==================== MÉTHODES PRIVÉES ====================
    
    /**
     * Rechercher des parcours
     */
    private function rechercherParcours(string $query, int $limit): array
    {
        return ParcoursApprentissage::where('est_publie', true)
            ->where(function($q) use ($query) {
                $q->where('titre', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('technologie', 'like', "%{$query}%");
            })
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function($parcours) {
                return [
                    'id' => $parcours->id,
                    'titre' => $parcours->titre,
                    'description' => $parcours->description,
                    'technologie' => $parcours->technologie,
                    'difficulte' => $parcours->difficulte,
                    'url' => route('api.v1.etudiant.parcours.afficher', $parcours->id),
                    'type' => 'parcours',
                ];
            })
            ->toArray();
    }
    
    /**
     * Rechercher des projets
     */
    private function rechercherProjets(string $query, int $limit): array
    {
        return Projet::whereIn('statut', ['ouvert', 'en_cours'])
            ->where(function($q) use ($query) {
                $q->where('titre', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function($projet) {
                return [
                    'id' => $projet->id,
                    'titre' => $projet->titre,
                    'description' => $projet->description,
                    'technologies' => $projet->technologies,
                    'difficulte' => $projet->difficulte,
                    'url' => route('api.v1.etudiant.projets.afficher', $projet->id),
                    'type' => 'projet',
                ];
            })
            ->toArray();
    }
    
    /**
     * Rechercher des utilisateurs
     */
    private function rechercherUtilisateurs(string $query, int $limit): array
    {
        return Utilisateur::where('est_actif', true)
            ->where(function($q) use ($query) {
                $q->where('prenom', 'like', "%{$query}%")
                  ->orWhere('nom', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->with('profil')
            ->orderBy('nom')
            ->limit($limit)
            ->get()
            ->map(function($utilisateur) {
                return [
                    'id' => $utilisateur->id,
                    'nom_complet' => $utilisateur->nom_complet,
                    'role' => $utilisateur->role,
                    'niveau' => $utilisateur->profil->niveau ?? 'debutant',
                    'technologies' => $utilisateur->profil->technologies ?? [],
                    'url' => route('api.v1.commun.recherche.utilisateurs', ['q' => $utilisateur->nom_complet]),
                    'type' => 'utilisateur',
                ];
            })
            ->toArray();
    }
    
    /**
     * Rechercher des compétences
     */
    private function rechercherCompetences(string $query, int $limit): array
    {
        return Competence::where('nom', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->orderBy('nom')
            ->limit($limit)
            ->get()
            ->map(function($competence) {
                return [
                    'id' => $competence->id,
                    'nom' => $competence->nom,
                    'categorie' => $competence->categorie,
                    'niveau' => $competence->niveau,
                    'url' => route('api.v1.commun.recherche.competences', ['q' => $competence->nom]),
                    'type' => 'competence',
                ];
            })
            ->toArray();
    }
    
    /**
     * Rechercher des discussions
     */
    private function rechercherDiscussions(string $query, int $limit): array
    {
        return DiscussionForum::where('titre', 'like', "%{$query}%")
            ->orWhere('contenu', 'like', "%{$query}%")
            ->with('categorie')
            ->orderByDesc('dernier_message_at')
            ->limit($limit)
            ->get()
            ->map(function($discussion) {
                return [
                    'id' => $discussion->id,
                    'titre' => $discussion->titre,
                    'categorie' => $discussion->categorie->nom,
                    'est_resolu' => $discussion->est_resolu,
                    'url' => route('api.v1.commun.forum.discussions.detail', $discussion->id),
                    'type' => 'discussion',
                ];
            })
            ->toArray();
    }
    
    /**
     * Rechercher des défis
     */
    private function rechercherDefis(string $query, int $limit): array
    {
        return Defi::where('est_actif', true)
            ->where(function($q) use ($query) {
                $q->where('titre', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderByDesc('date_debut')
            ->limit($limit)
            ->get()
            ->map(function($defi) {
                return [
                    'id' => $defi->id,
                    'titre' => $defi->titre,
                    'type' => $defi->type,
                    'difficulte' => $defi->difficulte,
                    'date_fin' => $defi->date_fin,
                    'url' => route('api.v1.etudiant.defis.afficher', $defi->id),
                    'type' => 'defi',
                ];
            })
            ->toArray();
    }
    
    /**
     * Obtenir des suggestions
     */
    private function obtenirSuggestions(string $query): array
    {
        // Suggestions basées sur la recherche populaire
        $suggestions = DB::table('recherches')
            ->select('requete', DB::raw('COUNT(*) as count'))
            ->where('requete', 'like', $query . '%')
            ->groupBy('requete')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('requete')
            ->toArray();
        
        // Suggestions basées sur le contenu
        if (count($suggestions) < 5) {
            $contenuSuggestions = ParcoursApprentissage::where('titre', 'like', $query . '%')
                ->orWhere('technologie', 'like', $query . '%')
                ->limit(5 - count($suggestions))
                ->pluck('titre')
                ->toArray();
            
            $suggestions = array_merge($suggestions, $contenuSuggestions);
        }
        
        return array_unique($suggestions);
    }
    
    /**
     * Enregistrer une recherche
     */
    private function enregistrerRecherche(string $query, $utilisateur): void
    {
        DB::table('recherches')->insert([
            'requete' => $query,
            'utilisateur_id' => $utilisateur ? $utilisateur->id : null,
            'resultats' => 0, // À calculer
            'created_at' => now(),
        ]);
    }
    
    /**
     * Obtenir les recherches populaires
     */
    private function obtenirRecherchesPopulaires(): array
    {
        return DB::table('recherches')
            ->select('requete', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('requete')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('requete', 'count')
            ->toArray();
    }
}