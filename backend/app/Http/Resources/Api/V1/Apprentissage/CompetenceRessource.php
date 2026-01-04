<?php

namespace App\Http\Resources\Api\V1\Apprentissage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompetenceRessource extends JsonResource
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
            'categorie' => $this->categorie,
            'niveau' => $this->niveau,
            'icone' => $this->icone,
            'pivot' => $this->when($this->pivot, function () {
                return [
                    'niveau_maitrise' => $this->pivot->niveau_maitrise,
                    'valide_a' => $this->pivot->valide_a,
                    'methode_validation' => $this->pivot->methode_validation,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
