<?php

namespace App\Http\Resources\Api\V1\Commun;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'type' => $this->type,
            'dernier_message_at' => $this->dernier_message_at?->toISOString(),
            'createur' => new \App\Http\Resources\Api\V1\UtilisateurRessource($this->whenLoaded('createur')),
            'participants' => ParticipantConversationRessource::collection($this->whenLoaded('participants')),
            'dernier_message' => new MessageMessagerieRessource($this->whenLoaded('dernierMessage')),
            'messages' => MessageMessagerieRessource::collection($this->whenLoaded('messages')),
            'messages_non_lus' => $this->messages_non_lus ?? 0,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
