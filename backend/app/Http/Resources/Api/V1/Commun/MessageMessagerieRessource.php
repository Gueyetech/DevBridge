<?php

namespace App\Http\Resources\Api\V1\Commun;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageMessagerieRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'contenu' => $this->contenu,
            'type' => $this->type,
            'expediteur' => new \App\Http\Resources\Api\V1\UtilisateurRessource($this->whenLoaded('expediteur')),
            'fichiers' => $this->whenLoaded('fichiers'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
