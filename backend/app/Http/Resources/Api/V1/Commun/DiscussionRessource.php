<?php

namespace App\Http\Resources\Api\V1\Commun;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscussionRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'contenu' => $this->contenu,
            'categorie_id' => $this->categorie_id,
            'tags' => $this->tags,
            'est_resolu' => $this->est_resolu,
            'est_epingle' => $this->est_epingle,
            'dernier_message_at' => $this->dernier_message_at?->toISOString(),
            'createur' => new \App\Http\Resources\Api\V1\UtilisateurRessource($this->whenLoaded('createur')),
            'categorie' => new CategorieForumRessource($this->whenLoaded('categorie')),
            'dernier_message' => new MessageForumRessource($this->whenLoaded('dernierMessage')),
            'messages' => MessageForumRessource::collection($this->whenLoaded('messages')),
            'nombre_messages' => $this->whenCounted('messages'),
            'nombre_likes' => $this->whenCounted('likes'),
            'nombre_vues' => $this->whenCounted('vues'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
