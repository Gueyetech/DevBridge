<?php

namespace App\Http\Resources\Api\V1\Projet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentaireTacheRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tache_id' => $this->tache_id,
            'contenu' => $this->contenu,
            'auteur' => new \App\Http\Resources\Api\V1\UtilisateurRessource($this->whenLoaded('auteur')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
