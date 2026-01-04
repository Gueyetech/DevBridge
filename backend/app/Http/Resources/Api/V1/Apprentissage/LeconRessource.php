<?php

namespace App\Http\Resources\Api\V1\Apprentissage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeconRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'module_id' => $this->module_id,
            'titre' => $this->titre,
            'contenu' => $this->contenu,
            'type' => $this->type,
            'duree_estimee_minutes' => $this->duree_estimee_minutes,
            'ordre' => $this->ordre,
            'ressources' => $this->ressources,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
