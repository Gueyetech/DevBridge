<?php

namespace App\Http\Controllers\Api\V1\Commun;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Http\Requests\Api\V1\Commun\RequeteCreerDiscussion;
use App\Http\Requests\Api\V1\Commun\RequeteCreerMessage;
use App\Http\Resources\Api\V1\Commun\DiscussionRessource;
use App\Http\Resources\Api\V1\Commun\MessageForumRessource;
use App\Models\CategorieForum;
use App\Models\DiscussionForum;
use App\Models\MessageForum;
use App\Models\SuiviDiscussion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ControleurForum extends ControleurApiBase
{
    /**
     * Lister les catégories du forum
     */
    public function categories(Request $requete): JsonResponse
    {
        $categories = CategorieForum::withCount(['discussions', 'discussions as discussions_non_lues' => function($query) {
            $query->whereDoesntHave('messages', function($q) {
                $q->where('utilisateur_id', Auth::id());
            });
        }])
        ->orderBy('ordre')
        ->get();
        
        return $this->reponseSucces([
            'categories' => $categories,
        ]);
    }
    
    /**
     * Lister les discussions
     */
    public function discussions(Request $requete): JsonResponse
    {
        $query = DiscussionForum::with([
            'createur.profil',
            'categorie',
            'dernierMessage.utilisateur',
            'messages' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(1);
            },
        ])
        ->withCount(['messages', 'likes', 'vues']);
        
        // Filtres
        if ($requete->has('categorie_id')) {
            $query->where('categorie_id', $requete->categorie_id);
        }
        
        if ($requete->has('utilisateur_id')) {
            $query->where('createur_id', $requete->utilisateur_id);
        }
        
        if ($requete->has('est_resolu')) {
            $query->where('est_resolu', $requete->est_resolu === 'true');
        }
        
        if ($requete->has('est_epingle')) {
            $query->where('est_epingle', $requete->est_epingle === 'true');
        }
        
        if ($requete->has('recherche')) {
            $query->where(function($q) use ($requete) {
                $q->where('titre', 'like', "%{$requete->recherche}%")
                  ->orWhere('contenu', 'like', "%{$requete->recherche}%");
            });
        }
        
        // Tri
        $tri = $requete->input('tri', 'dernier_message');
        $direction = $requete->input('direction', 'desc');
        
        if ($tri === 'dernier_message') {
            $query->orderBy('dernier_message_at', $direction);
        } else {
            $query->orderBy($tri, $direction);
        }
        
        $discussions = $query->paginate($requete->input('per_page', 20));
        
        // Marquer comme vues
        foreach ($discussions as $discussion) {
            $this->marquerCommeVue($discussion->id);
        }
        
        return $this->reponseSucces([
            'discussions' => DiscussionRessource::collection($discussions),
            'meta' => [
                'total' => $discussions->total(),
                'par_page' => $discussions->perPage(),
                'page_courante' => $discussions->currentPage(),
            ],
        ]);
    }
    
    /**
     * Créer une nouvelle discussion
     */
    public function creerDiscussion(RequeteCreerDiscussion $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        $donnees = $requete->validated();
        
        // Vérifier la limite de discussions
        if (!$this->peutCreerDiscussion($utilisateur)) {
            return $this->reponseErreur('Vous avez atteint la limite de discussions aujourd\'hui', 429);
        }
        
        DB::beginTransaction();
        
        try {
            // Créer la discussion
            $discussion = DiscussionForum::create([
                'titre' => $donnees['titre'],
                'contenu' => $donnees['contenu'],
                'categorie_id' => $donnees['categorie_id'],
                'createur_id' => $utilisateur->id,
                'tags' => $donnees['tags'] ?? [],
                'est_resolu' => false,
                'est_epingle' => false,
                'dernier_message_at' => now(),
            ]);
            
            // Créer le premier message
            $message = MessageForum::create([
                'discussion_id' => $discussion->id,
                'utilisateur_id' => $utilisateur->id,
                'contenu' => $donnees['contenu'],
                'est_premier_message' => true,
            ]);
            
            // Suivre automatiquement sa propre discussion
            SuiviDiscussion::create([
                'discussion_id' => $discussion->id,
                'utilisateur_id' => $utilisateur->id,
                'notifications_actives' => true,
            ]);
            
            DB::commit();
            
            // Accorder des points
            $utilisateur->ajouterPoints(10);
            
            return $this->reponseSucces([
                'message' => 'Discussion créée avec succès',
                'discussion' => new DiscussionRessource($discussion->load(['createur', 'categorie'])),
                'points_gagnes' => 10,
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->reponseErreur('Erreur lors de la création de la discussion', 500);
        }
    }
    
    /**
     * Afficher une discussion spécifique
     */
    public function discussionDetail(Request $requete, $id): JsonResponse
    {
        $discussion = DiscussionForum::with([
            'createur.profil',
            'categorie',
            'messages.utilisateur.profil',
            'messages.likes',
            'suiveurs',
        ])
        ->withCount(['messages', 'likes', 'vues'])
        ->findOrFail($id);
        
        // Marquer comme vue
        $this->marquerCommeVue($id);
        
        // Vérifier si l'utilisateur suit cette discussion
        $utilisateur = $requete->user();
        $suitDiscussion = SuiviDiscussion::where('discussion_id', $id)
            ->where('utilisateur_id', $utilisateur->id)
            ->exists();
        
        // Messages paginés
        $messages = MessageForum::with(['utilisateur.profil', 'likes'])
            ->where('discussion_id', $id)
            ->orderBy('created_at', 'asc')
            ->paginate($requete->input('per_page', 20));
        
        return $this->reponseSucces([
            'discussion' => new DiscussionRessource($discussion),
            'messages' => MessageForumRessource::collection($messages),
            'suit_discussion' => $suitDiscussion,
            'meta_messages' => [
                'total' => $messages->total(),
                'par_page' => $messages->perPage(),
                'page_courante' => $messages->currentPage(),
            ],
        ]);
    }
    
    /**
     * Mettre à jour une discussion
     */
    public function mettreAJourDiscussion(Request $requete, $id): JsonResponse
    {
        $discussion = DiscussionForum::findOrFail($id);
        $utilisateur = $requete->user();
        
        // Vérifier les permissions
        if ($discussion->createur_id !== $utilisateur->id && !$utilisateur->est_administrateur) {
            return $this->reponseErreur('Vous n\'êtes pas autorisé à modifier cette discussion', 403);
        }
        
        $donnees = $requete->validate([
            'titre' => 'sometimes|string|max:255',
            'contenu' => 'sometimes|string',
            'categorie_id' => 'sometimes|exists:categories_forum,id',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
            'est_resolu' => 'sometimes|boolean',
        ]);
        
        // Si c'est un administrateur, permettre d'épingler
        if ($utilisateur->est_administrateur && $requete->has('est_epingle')) {
            $donnees['est_epingle'] = $requete->est_epingle;
        }
        
        $discussion->update($donnees);
        
        return $this->reponseSucces([
            'message' => 'Discussion mise à jour',
            'discussion' => new DiscussionRessource($discussion->fresh()),
        ]);
    }
    
    /**
     * Supprimer une discussion
     */
    public function supprimerDiscussion(Request $requete, $id): JsonResponse
    {
        $discussion = DiscussionForum::findOrFail($id);
        $utilisateur = $requete->user();
        
        // Vérifier les permissions
        if ($discussion->createur_id !== $utilisateur->id && !$utilisateur->est_administrateur) {
            return $this->reponseErreur('Vous n\'êtes pas autorisé à supprimer cette discussion', 403);
        }
        
        $discussion->delete();
        
        return $this->reponseSucces([
            'message' => 'Discussion supprimée',
        ]);
    }
    
    /**
     * Lister les messages d'une discussion
     */
    public function messages(Request $requete, $discussionId): JsonResponse
    {
        $query = MessageForum::with(['utilisateur.profil', 'likes', 'reponses.utilisateur.profil'])
            ->where('discussion_id', $discussionId);
        
        // Tri
        $messages = $query->orderBy('created_at', 'asc')
            ->paginate($requete->input('per_page', 20));
        
        return $this->reponseSucces([
            'messages' => MessageForumRessource::collection($messages),
            'meta' => [
                'total' => $messages->total(),
                'par_page' => $messages->perPage(),
                'page_courante' => $messages->currentPage(),
            ],
        ]);
    }
    
    /**
     * Créer un nouveau message
     */
    public function creerMessage(RequeteCreerMessage $requete, $discussionId): JsonResponse
    {
        $discussion = DiscussionForum::findOrFail($discussionId);
        $utilisateur = $requete->user();
        
        // Vérifier si la discussion est verrouillée
        if ($discussion->est_verrouille && !$utilisateur->est_administrateur) {
            return $this->reponseErreur('Cette discussion est verrouillée', 403);
        }
        
        $donnees = $requete->validated();
        
        DB::beginTransaction();
        
        try {
            // Créer le message
            $message = MessageForum::create([
                'discussion_id' => $discussionId,
                'utilisateur_id' => $utilisateur->id,
                'parent_id' => $donnees['parent_id'] ?? null,
                'contenu' => $donnees['contenu'],
                'est_premier_message' => false,
            ]);
            
            // Mettre à jour la date du dernier message
            $discussion->update([
                'dernier_message_at' => now(),
            ]);
            
            // Notifier les suiveurs
            $this->notifierSuiveurs($discussion, $message, $utilisateur);
            
            DB::commit();
            
            // Accorder des points
            $utilisateur->ajouterPoints(5);
            
            return $this->reponseSucces([
                'message' => 'Message posté avec succès',
                'message' => new MessageForumRessource($message->load('utilisateur.profil')),
                'points_gagnes' => 5,
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->reponseErreur('Erreur lors de la création du message', 500);
        }
    }
    
    /**
     * Mettre à jour un message
     */
    public function mettreAJourMessage(Request $requete, $discussionId, $messageId): JsonResponse
    {
        $message = MessageForum::findOrFail($messageId);
        $utilisateur = $requete->user();
        
        // Vérifier les permissions
        if ($message->utilisateur_id !== $utilisateur->id && !$utilisateur->est_administrateur) {
            return $this->reponseErreur('Vous n\'êtes pas autorisé à modifier ce message', 403);
        }
        
        // Vérifier le délai d'édition (15 minutes)
        if (!$utilisateur->est_administrateur && $message->created_at->diffInMinutes(now()) > 15) {
            return $this->reponseErreur('Le délai d\'édition est dépassé (15 minutes)', 400);
        }
        
        $donnees = $requete->validate([
            'contenu' => 'required|string',
        ]);
        
        $message->update([
            'contenu' => $donnees['contenu'],
            'est_modifie' => true,
            'modifie_a' => now(),
        ]);
        
        return $this->reponseSucces([
            'message' => 'Message mis à jour',
            'message' => new MessageForumRessource($message->fresh()),
        ]);
    }
    
    /**
     * Supprimer un message
     */
    public function supprimerMessage(Request $requete, $discussionId, $messageId): JsonResponse
    {
        $message = MessageForum::findOrFail($messageId);
        $utilisateur = $requete->user();
        
        // Vérifier les permissions
        if ($message->utilisateur_id !== $utilisateur->id && !$utilisateur->est_administrateur) {
            return $this->reponseErreur('Vous n\'êtes pas autorisé à supprimer ce message', 403);
        }
        
        // Marquer comme supprimé plutôt que de supprimer physiquement
        $message->update([
            'contenu' => '[Message supprimé]',
            'est_supprime' => true,
            'supprime_a' => now(),
            'supprime_par' => $utilisateur->id,
        ]);
        
        return $this->reponseSucces([
            'message' => 'Message supprimé',
        ]);
    }
    
    /**
     * Aimer un message
     */
    public function aimerMessage(Request $requete, $discussionId, $messageId): JsonResponse
    {
        $message = MessageForum::findOrFail($messageId);
        $utilisateur = $requete->user();
        
        // Vérifier si l'utilisateur a déjà aimé ce message
        $dejaAime = DB::table('likes_messages')
            ->where('message_id', $messageId)
            ->where('utilisateur_id', $utilisateur->id)
            ->exists();
        
        if ($dejaAime) {
            // Retirer le like
            DB::table('likes_messages')
                ->where('message_id', $messageId)
                ->where('utilisateur_id', $utilisateur->id)
                ->delete();
            
            $action = 'retire';
        } else {
            // Ajouter le like
            DB::table('likes_messages')->insert([
                'message_id' => $messageId,
                'utilisateur_id' => $utilisateur->id,
                'created_at' => now(),
            ]);
            
            $action = 'ajoute';
            
            // Notifier l'auteur du message
            if ($message->utilisateur_id !== $utilisateur->id) {
                event(new \App\Events\MessageAime($message, $utilisateur));
                
                // Accorder des points à l'auteur du message
                $auteur = $message->utilisateur;
                $auteur->ajouterPoints(2);
            }
        }
        
        $nombreLikes = DB::table('likes_messages')
            ->where('message_id', $messageId)
            ->count();
        
        return $this->reponseSucces([
            'action' => $action,
            'nombre_likes' => $nombreLikes,
            'a_aime' => !$dejaAime,
        ]);
    }
    
    /**
     * Signaler un message
     */
    public function signalerMessage(Request $requete, $discussionId, $messageId): JsonResponse
    {
        $message = MessageForum::findOrFail($messageId);
        $utilisateur = $requete->user();
        
        $requete->validate([
            'raison' => 'required|string|max:500',
            'categorie' => 'required|in:spam,inapproprié,harcèlement,autre',
        ]);
        
        // Vérifier si l'utilisateur a déjà signalé ce message
        $dejaSignale = DB::table('signalements_messages')
            ->where('message_id', $messageId)
            ->where('utilisateur_id', $utilisateur->id)
            ->exists();
        
        if ($dejaSignale) {
            return $this->reponseErreur('Vous avez déjà signalé ce message', 400);
        }
        
        DB::table('signalements_messages')->insert([
            'message_id' => $messageId,
            'utilisateur_id' => $utilisateur->id,
            'raison' => $requete->raison,
            'categorie' => $requete->categorie,
            'statut' => 'en_attente',
            'created_at' => now(),
        ]);
        
        // Notifier les administrateurs
        event(new \App\Events\MessageSignale($message, $utilisateur));
        
        return $this->reponseSucces([
            'message' => 'Message signalé aux administrateurs',
        ]);
    }
    
    /**
     * Lister mes discussions
     */
    public function mesDiscussions(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        
        $discussions = DiscussionForum::with(['categorie', 'dernierMessage.utilisateur'])
            ->where('createur_id', $utilisateur->id)
            ->orderByDesc('dernier_message_at')
            ->paginate($requete->input('per_page', 20));
        
        return $this->reponseSucces([
            'discussions' => DiscussionRessource::collection($discussions),
            'meta' => [
                'total' => $discussions->total(),
                'par_page' => $discussions->perPage(),
            ],
        ]);
    }
    
    /**
     * Lister les discussions suivies
     */
    public function discussionsSuivies(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        
        $discussions = DiscussionForum::with(['categorie', 'dernierMessage.utilisateur', 'createur'])
            ->whereHas('suiveurs', function($query) use ($utilisateur) {
                $query->where('utilisateur_id', $utilisateur->id);
            })
            ->orderByDesc('dernier_message_at')
            ->paginate($requete->input('per_page', 20));
        
        return $this->reponseSucces([
            'discussions' => DiscussionRessource::collection($discussions),
            'meta' => [
                'total' => $discussions->total(),
                'par_page' => $discussions->perPage(),
            ],
        ]);
    }
    
    /**
     * Suivre une discussion
     */
    public function suivreDiscussion(Request $requete, $id): JsonResponse
    {
        $discussion = DiscussionForum::findOrFail($id);
        $utilisateur = $requete->user();
        
        // Vérifier si l'utilisateur suit déjà cette discussion
        $dejaSuivi = SuiviDiscussion::where('discussion_id', $id)
            ->where('utilisateur_id', $utilisateur->id)
            ->exists();
        
        if ($dejaSuivi) {
            return $this->reponseErreur('Vous suivez déjà cette discussion', 400);
        }
        
        SuiviDiscussion::create([
            'discussion_id' => $id,
            'utilisateur_id' => $utilisateur->id,
            'notifications_actives' => $requete->input('notifications', true),
        ]);
        
        return $this->reponseSucces([
            'message' => 'Discussion suivie',
            'notifications_actives' => $requete->input('notifications', true),
        ]);
    }
    
    /**
     * Ne plus suivre une discussion
     */
    public function nePlusSuivreDiscussion(Request $requete, $id): JsonResponse
    {
        $discussion = DiscussionForum::findOrFail($id);
        $utilisateur = $requete->user();
        
        $suivi = SuiviDiscussion::where('discussion_id', $id)
            ->where('utilisateur_id', $utilisateur->id)
            ->first();
        
        if (!$suivi) {
            return $this->reponseErreur('Vous ne suivez pas cette discussion', 400);
        }
        
        $suivi->delete();
        
        return $this->reponseSucces([
            'message' => 'Discussion non suivie',
        ]);
    }
    
    /**
     * Marquer une discussion comme résolue
     */
    public function marquerCommeResolu(Request $requete, $id): JsonResponse
    {
        $discussion = DiscussionForum::findOrFail($id);
        $utilisateur = $requete->user();
        
        // Vérifier les permissions
        if ($discussion->createur_id !== $utilisateur->id && !$utilisateur->est_administrateur) {
            return $this->reponseErreur('Seul l\'auteur ou un administrateur peut marquer comme résolu', 403);
        }
        
        $discussion->update([
            'est_resolu' => true,
            'resolu_a' => now(),
        ]);
        
        // Accorder des points au créateur
        $discussion->createur->ajouterPoints(20);
        
        // Accorder des points à la personne qui a fourni la solution
        if ($requete->has('solution_message_id')) {
            $solutionMessage = MessageForum::find($requete->solution_message_id);
            if ($solutionMessage && $solutionMessage->utilisateur_id !== $discussion->createur_id) {
                $solutionMessage->utilisateur->ajouterPoints(30);
            }
        }
        
        return $this->reponseSucces([
            'message' => 'Discussion marquée comme résolue',
            'points_auteur' => 20,
            'points_solution' => $requete->has('solution_message_id') ? 30 : 0,
        ]);
    }
    
    /**
     * Obtenir les statistiques du forum
     */
    public function statistiques(Request $requete): JsonResponse
    {
        $statistiques = [
            'total_discussions' => DiscussionForum::count(),
            'total_messages' => MessageForum::count(),
            'discussions_resolues' => DiscussionForum::where('est_resolu', true)->count(),
            'discussions_epinglees' => DiscussionForum::where('est_epingle', true)->count(),
            'utilisateurs_actifs' => DB::table('messages_forum')
                ->selectRaw('COUNT(DISTINCT utilisateur_id) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->value('count'),
            'messages_par_jour' => round(MessageForum::where('created_at', '>=', now()->subDays(30))
                ->count() / 30, 2),
            'categories_populaires' => CategorieForum::withCount('discussions')
                ->orderByDesc('discussions_count')
                ->limit(5)
                ->get(),
        ];
        
        return $this->reponseSucces([
            'statistiques' => $statistiques,
        ]);
    }
    
    // ==================== MÉTHODES PRIVÉES ====================
    
    /**
     * Vérifier si un utilisateur peut créer une discussion
     */
    private function peutCreerDiscussion($utilisateur): bool
    {
        // Limite : 5 discussions par jour
        $discussionsAujourdhui = DiscussionForum::where('createur_id', $utilisateur->id)
            ->whereDate('created_at', today())
            ->count();
        
        return $discussionsAujourdhui < 5 || $utilisateur->est_administrateur;
    }
    
    /**
     * Marquer une discussion comme vue
     */
    private function marquerCommeVue($discussionId): void
    {
        $utilisateur = Auth::user();
        
        if (!$utilisateur) {
            return;
        }
        
        DB::table('vues_discussions')->updateOrInsert(
            [
                'discussion_id' => $discussionId,
                'utilisateur_id' => $utilisateur->id,
            ],
            [
                'vu_a' => now(),
                'updated_at' => now(),
            ]
        );
    }
    
    /**
     * Notifier les suiveurs d'une discussion
     */
    private function notifierSuiveurs($discussion, $message, $auteur): void
    {
        $suiveurs = SuiviDiscussion::with('utilisateur')
            ->where('discussion_id', $discussion->id)
            ->where('notifications_actives', true)
            ->where('utilisateur_id', '!=', $auteur->id)
            ->get();
        
        foreach ($suiveurs as $suivi) {
            event(new \App\Events\NouveauMessageForum($discussion, $message, $suivi->utilisateur));
        }
    }
}