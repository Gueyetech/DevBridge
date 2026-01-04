<?php

namespace App\Http\Resources\Api\V1\Commun;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategorieForumRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'slug' => $this->slug,
            'icone' => $this->icone,
            'couleur' => $this->couleur,
            'ordre' => $this->ordre,
            'est_actif' => $this->est_actif,
            'nombre_discussions' => $this->whenCounted('discussions'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
