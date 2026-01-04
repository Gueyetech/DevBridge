<?php

namespace App\Http\Resources\Api\V1\Commun;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageForumRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'discussion_id' => $this->discussion_id,
            'contenu' => $this->contenu,
            'est_premier_message' => $this->est_premier_message,
            'est_solution' => $this->est_solution,
            'utilisateur' => new \App\Http\Resources\Api\V1\UtilisateurRessource($this->whenLoaded('utilisateur')),
            'likes' => $this->whenLoaded('likes'),
            'nombre_likes' => $this->whenCounted('likes'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
