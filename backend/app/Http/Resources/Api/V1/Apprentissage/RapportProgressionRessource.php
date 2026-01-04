<?php

namespace App\Http\Resources\Api\V1\Apprentissage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RapportProgressionRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'utilisateur_id' => $this->utilisateur_id,
            'type' => $this->type,
            'periode_debut' => $this->periode_debut?->toISOString(),
            'periode_fin' => $this->periode_fin?->toISOString(),
            'donnees' => $this->donnees,
            'genere_a' => $this->genere_a?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
