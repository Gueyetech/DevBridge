<?php

namespace App\Http\Controllers\Api\V1\Commun;

use App\Http\Controllers\Api\V1\ControleurApiBase;
use App\Http\Requests\Api\V1\Commun\RequeteEnvoyerMessage;
use App\Http\Resources\Api\V1\Commun\ConversationRessource;
use App\Http\Resources\Api\V1\Commun\MessageMessagerieRessource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ParticipantConversation;
use App\Models\Utilisateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ControleurMessagerie extends ControleurApiBase
{
    /**
     * Lister les conversations de l'utilisateur
     */
    public function conversations(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        
        $query = Conversation::with([
            'participants.utilisateur.profil',
            'dernierMessage.expediteur.profil',
            'messages' => function($query) use ($utilisateur) {
                $query->whereDoesntHave('lectures', function($q) use ($utilisateur) {
                    $q->where('utilisateur_id', $utilisateur->id);
                });
            },
        ])
        ->whereHas('participants', function($query) use ($utilisateur) {
            $query->where('utilisateur_id', $utilisateur->id);
        });
        
        // Filtres
        if ($requete->has('non_lues')) {
            $query->whereHas('messages', function($q) use ($utilisateur) {
                $q->whereDoesntHave('lectures', function($query) use ($utilisateur) {
                    $query->where('utilisateur_id', $utilisateur->id);
                });
            });
        }
        
        // Tri
        $conversations = $query->orderByDesc('dernier_message_at')
            ->paginate($requete->input('per_page', 20));
        
        // Compter les messages non lus par conversation
        foreach ($conversations as $conversation) {
            $conversation->messages_non_lus = $conversation->messages
                ->where('expediteur_id', '!=', $utilisateur->id)
                ->count();
        }
        
        return $this->reponseSucces([
            'conversations' => ConversationRessource::collection($conversations),
            'meta' => [
                'total' => $conversations->total(),
                'par_page' => $conversations->perPage(),
                'page_courante' => $conversations->currentPage(),
            ],
        ]);
    }
    
    /**
     * Créer une nouvelle conversation
     */
    public function creerConversation(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        
        $requete->validate([
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:utilisateurs,id',
            'titre' => 'nullable|string|max:255',
            'message_initial' => 'required|string',
        ]);
        
        // Ajouter l'utilisateur courant aux participants
        $participants = array_unique(array_merge($requete->participants, [$utilisateur->id]));
        
        // Vérifier si une conversation existe déjà avec ces participants
        $conversationExistante = $this->trouverConversationExistante($participants);
        
        if ($conversationExistante) {
            return $this->reponseSucces([
                'message' => 'Conversation existante trouvée',
                'conversation' => new ConversationRessource($conversationExistante),
            ]);
        }
        
        DB::beginTransaction();
        
        try {
            // Créer la conversation
            $conversation = Conversation::create([
                'titre' => $requete->titre ?? 'Conversation avec ' . count($participants) . ' personnes',
                'type' => count($participants) > 2 ? 'groupe' : 'privee',
                'createur_id' => $utilisateur->id,
                'dernier_message_at' => now(),
            ]);
            
            // Ajouter les participants
            foreach ($participants as $participantId) {
                ParticipantConversation::create([
                    'conversation_id' => $conversation->id,
                    'utilisateur_id' => $participantId,
                    'rejoint_a' => now(),
                ]);
            }
            
            // Créer le message initial
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'expediteur_id' => $utilisateur->id,
                'contenu' => $requete->message_initial,
                'type' => 'texte',
            ]);
            
            // Marquer le message comme lu pour l'expéditeur
            DB::table('lectures_messages')->insert([
                'message_id' => $message->id,
                'utilisateur_id' => $utilisateur->id,
                'lu_a' => now(),
            ]);
            
            DB::commit();
            
            // Notifier les autres participants
            $autresParticipants = array_diff($participants, [$utilisateur->id]);
            foreach ($autresParticipants as $participantId) {
                event(new \App\Events\NouvelleConversation($conversation, $participantId));
            }
            
            return $this->reponseSucces([
                'message' => 'Conversation créée',
                'conversation' => new ConversationRessource($conversation->load(['participants', 'messages'])),
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->reponseErreur('Erreur lors de la création de la conversation', 500);
        }
    }
    
    /**
     * Afficher une conversation spécifique
     */
    public function conversationDetail(Request $requete, $id): JsonResponse
    {
        $utilisateur = $requete->user();
        
        $conversation = Conversation::with([
            'participants.utilisateur.profil',
            'createur.profil',
        ])->findOrFail($id);
        
        // Vérifier que l'utilisateur fait partie de la conversation
        if (!$conversation->participants->contains('utilisateur_id', $utilisateur->id)) {
            return $this->reponseErreur('Vous n\'avez pas accès à cette conversation', 403);
        }
        
        // Marquer tous les messages comme lus
        $this->marquerMessagesCommeLus($conversation->id, $utilisateur->id);
        
        return $this->reponseSucces([
            'conversation' => new ConversationRessource($conversation),
        ]);
    }
    
    /**
     * Lister les messages d'une conversation
     */
    public function messages(Request $requete, $conversationId): JsonResponse
    {
        $utilisateur = $requete->user();
        
        // Vérifier l'accès
        $conversation = Conversation::findOrFail($conversationId);
        if (!$conversation->participants()->where('utilisateur_id', $utilisateur->id)->exists()) {
            return $this->reponseErreur('Accès non autorisé', 403);
        }
        
        $query = Message::with(['expediteur.profil', 'fichiers'])
            ->where('conversation_id', $conversationId);
        
        // Filtres
        if ($requete->has('type')) {
            $query->where('type', $requete->type);
        }
        
        if ($requete->has('depuis')) {
            $query->where('created_at', '>=', $requete->depuis);
        }
        
        if ($requete->has('avant')) {
            $query->where('created_at', '<=', $requete->avant);
        }
        
        // Pagination avec curseur
        if ($requete->has('cursor')) {
            $query->where('id', '<', $requete->cursor);
        }
        
        $messages = $query->orderByDesc('created_at')
            ->limit($requete->input('limit', 50))
            ->get()
            ->reverse()
            ->values();
        
        // Marquer comme lus
        $this->marquerMessagesCommeLus($conversationId, $utilisateur->id);
        
        return $this->reponseSucces([
            'messages' => MessageMessagerieRessource::collection($messages),
            'has_more' => $messages->count() >= $requete->input('limit', 50),
            'cursor' => $messages->isNotEmpty() ? $messages->first()->id : null,
        ]);
    }
    
    /**
     * Envoyer un message
     */
    public function envoyerMessage(RequeteEnvoyerMessage $requete, $conversationId): JsonResponse
    {
        $utilisateur = $requete->user();
        $conversation = Conversation::findOrFail($conversationId);
        
        // Vérifier que l'utilisateur fait partie de la conversation
        if (!$conversation->participants()->where('utilisateur_id', $utilisateur->id)->exists()) {
            return $this->reponseErreur('Vous n\'avez pas accès à cette conversation', 403);
        }
        
        // Vérifier si la conversation n'est pas archivée
        $participant = $conversation->participants()->where('utilisateur_id', $utilisateur->id)->first();
        if ($participant->pivot->est_archive) {
            return $this->reponseErreur('Vous avez archivé cette conversation', 400);
        }
        
        $donnees = $requete->validated();
        
        DB::beginTransaction();
        
        try {
            // Créer le message
            $message = Message::create([
                'conversation_id' => $conversationId,
                'expediteur_id' => $utilisateur->id,
                'contenu' => $donnees['contenu'],
                'type' => $donnees['type'] ?? 'texte',
                'parent_id' => $donnees['parent_id'] ?? null,
                'est_modifie' => false,
                'est_supprime' => false,
            ]);
            
            // Gérer les pièces jointes
            if ($requete->hasFile('fichiers')) {
                foreach ($requete->file('fichiers') as $fichier) {
                    $chemin = $fichier->store('messagerie/fichiers', 'public');
                    
                    DB::table('fichiers_messages')->insert([
                        'message_id' => $message->id,
                        'nom' => $fichier->getClientOriginalName(),
                        'chemin' => $chemin,
                        'type' => $fichier->getMimeType(),
                        'taille' => $fichier->getSize(),
                        'created_at' => now(),
                    ]);
                }
            }
            
            // Mettre à jour la conversation
            $conversation->update([
                'dernier_message_at' => now(),
                'dernier_message_id' => $message->id,
            ]);
            
            // Marquer le message comme lu pour l'expéditeur
            DB::table('lectures_messages')->insert([
                'message_id' => $message->id,
                'utilisateur_id' => $utilisateur->id,
                'lu_a' => now(),
            ]);
            
            DB::commit();
            
            // Notifier les autres participants
            $autresParticipants = $conversation->participants()
                ->where('utilisateur_id', '!=', $utilisateur->id)
                ->where('est_archive', false)
                ->pluck('utilisateur_id');
            
            foreach ($autresParticipants as $participantId) {
                event(new \App\Events\NouveauMessage($conversation, $message, $participantId));
            }
            
            return $this->reponseSucces([
                'message' => 'Message envoyé',
                'message' => new MessageMessagerieRessource($message->load('expediteur.profil')),
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->reponseErreur('Erreur lors de l\'envoi du message', 500);
        }
    }
    
    /**
     * Marquer un message comme lu
     */
    public function marquerCommeLu(Request $requete, $conversationId, $messageId = null): JsonResponse
    {
        $utilisateur = $requete->user();
        
        if ($messageId) {
            // Marquer un message spécifique comme lu
            $message = Message::findOrFail($messageId);
            
            DB::table('lectures_messages')->updateOrInsert(
                [
                    'message_id' => $messageId,
                    'utilisateur_id' => $utilisateur->id,
                ],
                [
                    'lu_a' => now(),
                    'updated_at' => now(),
                ]
            );
        } else {
            // Marquer tous les messages de la conversation comme lus
            $this->marquerMessagesCommeLus($conversationId, $utilisateur->id);
        }
        
        return $this->reponseSucces([
            'message' => 'Message(s) marqué(s) comme lu(s)',
        ]);
    }
    
    /**
     * Modifier un message
     */
    public function modifierMessage(Request $requete, $conversationId, $messageId): JsonResponse
    {
        $message = Message::findOrFail($messageId);
        $utilisateur = $requete->user();
        
        // Vérifier que l'utilisateur est l'expéditeur
        if ($message->expediteur_id !== $utilisateur->id) {
            return $this->reponseErreur('Vous ne pouvez modifier que vos propres messages', 403);
        }
        
        // Vérifier le délai (15 minutes)
        if ($message->created_at->diffInMinutes(now()) > 15) {
            return $this->reponseErreur('Le délai de modification est dépassé (15 minutes)', 400);
        }
        
        $requete->validate([
            'contenu' => 'required|string',
        ]);
        
        $message->update([
            'contenu' => $requete->contenu,
            'est_modifie' => true,
            'modifie_a' => now(),
        ]);
        
        return $this->reponseSucces([
            'message' => 'Message modifié',
            'message' => new MessageMessagerieRessource($message->fresh()),
        ]);
    }
    
    /**
     * Supprimer un message
     */
    public function supprimerMessage(Request $requete, $conversationId, $messageId): JsonResponse
    {
        $message = Message::findOrFail($messageId);
        $utilisateur = $requete->user();
        
        // Vérifier que l'utilisateur est l'expéditeur ou un administrateur
        if ($message->expediteur_id !== $utilisateur->id && !$utilisateur->est_administrateur) {
            return $this->reponseErreur('Vous ne pouvez supprimer que vos propres messages', 403);
        }
        
        // Pour l'expéditeur : masquer le message
        if ($message->expediteur_id === $utilisateur->id) {
            $message->update([
                'est_supprime_expediteur' => true,
                'supprime_a' => now(),
            ]);
        } else {
            // Pour les administrateurs : suppression complète
            $message->update([
                'est_supprime' => true,
                'supprime_par' => $utilisateur->id,
                'supprime_a' => now(),
            ]);
        }
        
        return $this->reponseSucces([
            'message' => 'Message supprimé',
        ]);
    }
    
    /**
     * Ajouter des participants à une conversation
     */
    public function ajouterParticipants(Request $requete, $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $utilisateur = $requete->user();
        
        // Vérifier que l'utilisateur est le créateur ou un administrateur
        if ($conversation->createur_id !== $utilisateur->id && !$utilisateur->est_administrateur) {
            return $this->reponseErreur('Vous ne pouvez pas ajouter des participants à cette conversation', 403);
        }
        
        $requete->validate([
            'participants' => 'required|array|min:1',
            'participants.*' => 'exists:utilisateurs,id',
        ]);
        
        $nouveauxParticipants = [];
        
        foreach ($requete->participants as $participantId) {
            // Vérifier si le participant n'est pas déjà dans la conversation
            if (!$conversation->participants()->where('utilisateur_id', $participantId)->exists()) {
                ParticipantConversation::create([
                    'conversation_id' => $conversationId,
                    'utilisateur_id' => $participantId,
                    'rejoint_a' => now(),
                ]);
                
                $nouveauxParticipants[] = $participantId;
                
                // Notifier le nouveau participant
                event(new \App\Events\AjouteConversation($conversation, $participantId));
            }
        }
        
        return $this->reponseSucces([
            'message' => count($nouveauxParticipants) . ' participant(s) ajouté(s)',
            'participants_ajoutes' => $nouveauxParticipants,
        ]);
    }
    
    /**
     * Quitter une conversation
     */
    public function quitterConversation(Request $requete, $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $utilisateur = $requete->user();
        
        // Le créateur ne peut pas quitter, il doit d'abord transférer la propriété
        if ($conversation->createur_id === $utilisateur->id) {
            return $this->reponseErreur('Le créateur ne peut pas quitter la conversation. Transférez d\'abord la propriété.', 400);
        }
        
        $participant = $conversation->participants()->where('utilisateur_id', $utilisateur->id)->first();
        
        if ($participant) {
            $participant->delete();
        }
        
        return $this->reponseSucces([
            'message' => 'Vous avez quitté la conversation',
        ]);
    }
    
    /**
     * Archiver une conversation
     */
    public function archiverConversation(Request $requete, $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $utilisateur = $requete->user();
        
        $participant = $conversation->participants()->where('utilisateur_id', $utilisateur->id)->first();
        
        if ($participant) {
            $participant->pivot->update([
                'est_archive' => true,
                'archive_a' => now(),
            ]);
        }
        
        return $this->reponseSucces([
            'message' => 'Conversation archivée',
        ]);
    }
    
    /**
     * Désarchiver une conversation
     */
    public function desarchiverConversation(Request $requete, $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $utilisateur = $requete->user();
        
        $participant = $conversation->participants()->where('utilisateur_id', $utilisateur->id)->first();
        
        if ($participant) {
            $participant->pivot->update([
                'est_archive' => false,
                'archive_a' => null,
            ]);
        }
        
        return $this->reponseSucces([
            'message' => 'Conversation désarchivée',
        ]);
    }
    
    /**
     * Obtenir les notifications de messagerie
     */
    public function notifications(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        
        $notifications = DB::table('notifications_messagerie')
            ->where('utilisateur_id', $utilisateur->id)
            ->where('est_lu', false)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'conversation_id' => $notification->conversation_id,
                    'message_id' => $notification->message_id,
                    'contenu' => $notification->contenu,
                    'expediteur_id' => $notification->expediteur_id,
                    'created_at' => $notification->created_at,
                ];
            });
        
        $nombreNonLus = $notifications->count();
        
        return $this->reponseSucces([
            'notifications' => $notifications,
            'nombre_non_lus' => $nombreNonLus,
        ]);
    }
    
    /**
     * Marquer toutes les notifications comme lues
     */
    public function marquerToutesNotificationsLues(Request $requete): JsonResponse
    {
        $utilisateur = $requete->user();
        
        DB::table('notifications_messagerie')
            ->where('utilisateur_id', $utilisateur->id)
            ->where('est_lu', false)
            ->update([
                'est_lu' => true,
                'lu_a' => now(),
            ]);
        
        return $this->reponseSucces([
            'message' => 'Toutes les notifications marquées comme lues',
        ]);
    }
    
    // ==================== MÉTHODES PRIVÉES ====================
    
    /**
     * Trouver une conversation existante avec les mêmes participants
     */
    private function trouverConversationExistante(array $participants): ?Conversation
    {
        // Pour les conversations privées (2 participants)
        if (count($participants) === 2) {
            return Conversation::where('type', 'privee')
                ->whereHas('participants', function($query) use ($participants) {
                    $query->whereIn('utilisateur_id', $participants);
                }, '=', count($participants))
                ->first();
        }
        
        return null;
    }
    
    /**
     * Marquer tous les messages d'une conversation comme lus
     */
    private function marquerMessagesCommeLus($conversationId, $utilisateurId): void
    {
        $messagesNonLus = Message::where('conversation_id', $conversationId)
            ->where('expediteur_id', '!=', $utilisateurId)
            ->whereDoesntHave('lectures', function($query) use ($utilisateurId) {
                $query->where('utilisateur_id', $utilisateurId);
            })
            ->pluck('id');
        
        if ($messagesNonLus->isNotEmpty()) {
            $lectures = $messagesNonLus->map(function($messageId) use ($utilisateurId) {
                return [
                    'message_id' => $messageId,
                    'utilisateur_id' => $utilisateurId,
                    'lu_a' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();
            
            DB::table('lectures_messages')->insert($lectures);
        }
    }
}
