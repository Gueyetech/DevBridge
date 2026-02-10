<?php

namespace App\Http\Controllers\Api\V1\Etudiant;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\CommentaireTache;
use App\Models\MembreProjet;
use App\Http\Resources\Api\V1\Projet\ProjetRessource;
use App\Http\Resources\Api\V1\Projet\TacheRessource;
use App\Http\Resources\Api\V1\Projet\CommentaireTacheRessource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControleurProjet extends ControleurApiBase
{
    /**
     * Lister les projets de l'utilisateur
     */
    public function index(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();

        $projets = Projet::whereHas('membres', function ($query) use ($utilisateur) {
            $query->where('utilisateur_id', $utilisateur->id);
        })
            ->orWhere('createur_id', $utilisateur->id)
            ->with(['createur', 'membres', 'taches'])
            ->withCount(['membres', 'taches'])
            ->orderByDesc('created_at')
            ->paginate($requete->input('par_page', 15));

        return $this->reponseSucces([
            'projets' => ProjetRessource::collection($projets),
            'message' => 'Projets récupérés avec succès'
        ], 200);
    }

    /**
     * Afficher un projet
     */
    public function afficher(string $id): JsonResponse
    {
        $projet = Projet::with(['createur', 'membres.utilisateur', 'taches.assignee'])
            ->findOrFail($id);

        return $this->reponseSucces([
            'projet' => new ProjetRessource($projet),
            'message' => 'Projet récupéré avec succès'
        ], 200);
    }

    /**
     * Créer un nouveau projet
     */
    public function creer(Request $requete): JsonResponse
    {
        $validees = $requete->validate([
            'titre' => 'required|string|max:255',
            'description' => 'required|string',
            'technologies' => 'nullable|array',
            'difficulte' => 'required|in:debutant,intermediaire,avance',
            'date_fin_prevue' => 'nullable|date|after:today',
            'nombre_maximum_participants' => 'nullable|integer|min:1|max:20',
            'est_public' => 'boolean',
            'url_depot' => 'nullable|url',
        ]);

        $utilisateur = $requete->user();

        DB::beginTransaction();
        try {
            $projet = Projet::create([
                ...$validees,
                'createur_id' => $utilisateur->id,
                'statut' => 'en_cours',
                'date_debut' => now(),
            ]);

            // Ajouter le créateur comme membre
            MembreProjet::create([
                'projet_id' => $projet->id,
                'utilisateur_id' => $utilisateur->id,
                'role' => 'proprietaire',
                'rejoint_a' => now(),
            ]);

            DB::commit();

            return $this->reponseSucces(
                new ProjetRessource($projet->load(['createur', 'membres'])),
                'Projet créé avec succès',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->reponseErreur('Erreur lors de la création du projet', 500);
        }
    }

    /**
     * Rejoindre un projet
     */
    public function rejoindre(string $id, Request $requete): JsonResponse
    {
        $projet = Projet::findOrFail($id);
        $utilisateur = $requete->user();

        // Vérifier si l'utilisateur est déjà membre
        $estMembre = $projet->membres()->where('utilisateur_id', $utilisateur->id)->exists();
        if ($estMembre) {
            return $this->reponseErreur('Vous êtes déjà membre de ce projet', 400);
        }

        // Vérifier le nombre maximum de participants
        if ($projet->nombre_maximum_participants && 
            $projet->membres()->count() >= $projet->nombre_maximum_participants) {
            return $this->reponseErreur('Le projet a atteint le nombre maximum de participants', 400);
        }

        MembreProjet::create([
            'projet_id' => $projet->id,
            'utilisateur_id' => $utilisateur->id,
            'role' => 'membre',
            'rejoint_a' => now(),
        ]);

        return $this->reponseSucces(
            new ProjetRessource($projet->fresh(['createur', 'membres'])),
            'Vous avez rejoint le projet avec succès'
        );
    }

    /**
     * Mettre à jour un projet
     */
    public function mettreAJour(string $id, Request $requete): JsonResponse
    {
        $projet = Projet::findOrFail($id);
        $utilisateur = $requete->user();

        // Vérifier les permissions
        if ($projet->createur_id !== $utilisateur->id) {
            return $this->reponseErreur('Vous n\'êtes pas autorisé à modifier ce projet', 403);
        }

        $validees = $requete->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'technologies' => 'nullable|array',
            'difficulte' => 'sometimes|in:debutant,intermediaire,avance',
            'statut' => 'sometimes|in:en_cours,en_pause,termine,abandonne',
            'date_fin_prevue' => 'nullable|date',
            'nombre_maximum_participants' => 'nullable|integer|min:1|max:20',
            'est_public' => 'boolean',
            'url_depot' => 'nullable|url',
        ]);

        $projet->update($validees);

        return $this->reponseSucces(
            new ProjetRessource($projet->fresh(['createur', 'membres', 'taches'])),
            'Projet mis à jour avec succès'
        );
    }

    /**
     * Supprimer un projet
     */
    public function supprimer(string $id, Request $requete): JsonResponse
    {
        $projet = Projet::findOrFail($id);
        $utilisateur = $requete->user();

        if ($projet->createur_id !== $utilisateur->id) {
            return $this->reponseErreur('Vous n\'êtes pas autorisé à supprimer ce projet', 403);
        }

        $projet->delete();

        return $this->reponseSucces(null, 'Projet supprimé avec succès');
    }

    /**
     * Marquer un projet comme terminé
     */
    public function completer(string $id, Request $requete): JsonResponse
    {
        $projet = Projet::findOrFail($id);
        $utilisateur = $requete->user();

        if ($projet->createur_id !== $utilisateur->id) {
            return $this->reponseErreur('Vous n\'êtes pas autorisé à modifier ce projet', 403);
        }

        $projet->update([
            'statut' => 'termine',
            'date_fin_reelle' => now(),
        ]);

        return $this->reponseSucces(
            new ProjetRessource($projet->fresh(['createur', 'membres', 'taches'])),
            'Projet marqué comme terminé'
        );
    }

    // ==================== TÂCHES ====================

    /**
     * Lister les tâches d'un projet
     */
    public function taches(string $id): JsonResponse
    {
        $projet = Projet::findOrFail($id);
        $taches = $projet->taches()->with(['assignee', 'commentaires'])->get();

        return $this->reponseSucces(
            TacheRessource::collection($taches),
            'Tâches récupérées avec succès'
        );
    }

    /**
     * Créer une tâche
     */
    public function creerTache(string $id, Request $requete): JsonResponse
    {
        $projet = Projet::findOrFail($id);

        $validees = $requete->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priorite' => 'required|in:basse,normale,haute,urgente',
            'date_echeance' => 'nullable|date',
            'assignee_id' => 'nullable|exists:utilisateurs,id',
        ]);

        $tache = Tache::create([
            ...$validees,
            'projet_id' => $projet->id,
            'statut' => 'a_faire',
        ]);

        return $this->reponseSucces(
            new TacheRessource($tache->load('assignee')),
            'Tâche créée avec succès',
            201
        );
    }

    /**
     * Mettre à jour une tâche
     */
    public function mettreAJourTache(string $id, string $tacheId, Request $requete): JsonResponse
    {
        $tache = Tache::where('projet_id', $id)->findOrFail($tacheId);

        $validees = $requete->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'statut' => 'sometimes|in:a_faire,en_cours,en_revision,terminee',
            'priorite' => 'sometimes|in:basse,normale,haute,urgente',
            'date_echeance' => 'nullable|date',
            'assignee_id' => 'nullable|exists:utilisateurs,id',
        ]);

        $tache->update($validees);

        return $this->reponseSucces(
            new TacheRessource($tache->fresh('assignee')),
            'Tâche mise à jour avec succès'
        );
    }

    /**
     * Supprimer une tâche
     */
    public function supprimerTache(string $id, string $tacheId): JsonResponse
    {
        $tache = Tache::where('projet_id', $id)->findOrFail($tacheId);
        $tache->delete();

        return $this->reponseSucces(null, 'Tâche supprimée avec succès');
    }

    /**
     * Assigner une tâche
     */
    public function assignerTache(string $id, string $tacheId, Request $requete): JsonResponse
    {
        $tache = Tache::where('projet_id', $id)->findOrFail($tacheId);

        $validees = $requete->validate([
            'assignee_id' => 'required|exists:utilisateurs,id',
        ]);

        $tache->update(['assignee_id' => $validees['assignee_id']]);

        return $this->reponseSucces(
            new TacheRessource($tache->fresh('assignee')),
            'Tâche assignée avec succès'
        );
    }

    /**
     * Terminer une tâche
     */
    public function terminerTache(string $id, string $tacheId): JsonResponse
    {
        $tache = Tache::where('projet_id', $id)->findOrFail($tacheId);
        $tache->update(['statut' => 'terminee']);

        return $this->reponseSucces(
            new TacheRessource($tache->fresh('assignee')),
            'Tâche marquée comme terminée'
        );
    }

    // ==================== COMMENTAIRES ====================

    /**
     * Lister les commentaires d'une tâche
     */
    public function commentaires(string $id, string $tacheId): JsonResponse
    {
        $tache = Tache::where('projet_id', $id)->findOrFail($tacheId);
        $commentaires = $tache->commentaires()->with('auteur')->orderBy('created_at')->get();

        return $this->reponseSucces(
            CommentaireTacheRessource::collection($commentaires),
            'Commentaires récupérés avec succès'
        );
    }

    /**
     * Créer un commentaire
     */
    public function creerCommentaire(string $id, string $tacheId, Request $requete): JsonResponse
    {
        $tache = Tache::where('projet_id', $id)->findOrFail($tacheId);

        $validees = $requete->validate([
            'contenu' => 'required|string',
        ]);

        $commentaire = CommentaireTache::create([
            'tache_id' => $tache->id,
            'utilisateur_id' => $requete->user()->id,
            'contenu' => $validees['contenu'],
        ]);

        return $this->reponseSucces(
            new CommentaireTacheRessource($commentaire->load('auteur')),
            'Commentaire ajouté avec succès',
            201
        );
    }

    /**
     * Mettre à jour un commentaire
     */
    public function mettreAJourCommentaire(string $id, string $tacheId, string $commentaireId, Request $requete): JsonResponse
    {
        $commentaire = CommentaireTache::where('tache_id', $tacheId)
            ->where('utilisateur_id', $requete->user()->id)
            ->findOrFail($commentaireId);

        $validees = $requete->validate([
            'contenu' => 'required|string',
        ]);

        $commentaire->update($validees);

        return $this->reponseSucces(
            new CommentaireTacheRessource($commentaire->fresh('auteur')),
            'Commentaire mis à jour avec succès'
        );
    }

    /**
     * Supprimer un commentaire
     */
    public function supprimerCommentaire(string $id, string $tacheId, string $commentaireId, Request $requete): JsonResponse
    {
        $commentaire = CommentaireTache::where('tache_id', $tacheId)
            ->where('utilisateur_id', $requete->user()->id)
            ->findOrFail($commentaireId);

        $commentaire->delete();

        return $this->reponseSucces(null, 'Commentaire supprimé avec succès');
    }
}
