<?php

namespace App\Http\Resources\Api\V1\Projet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TacheRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'projet_id' => $this->projet_id,
            'titre' => $this->titre,
            'description' => $this->description,
            'statut' => $this->statut,
            'priorite' => $this->priorite,
            'date_echeance' => $this->date_echeance?->toISOString(),
            'assignee' => new \App\Http\Resources\Api\V1\UtilisateurRessource($this->whenLoaded('assignee')),
            'commentaires' => CommentaireTacheRessource::collection($this->whenLoaded('commentaires')),
            'nombre_commentaires' => $this->whenCounted('commentaires'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
