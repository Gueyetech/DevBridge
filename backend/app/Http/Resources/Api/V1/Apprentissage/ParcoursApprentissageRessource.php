<?php

namespace App\Http\Resources\Api\V1\Apprentissage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParcoursApprentissageRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'description' => $this->description,
            'technologie' => $this->technologie,
            'difficulte' => $this->difficulte,
            'duree_estimee_heures' => $this->duree_estimee_heures,
            'image_couverture' => $this->image_couverture,
            'prerequis' => $this->prerequis,
            'objectifs' => $this->objectifs,
            'est_publie' => $this->est_publie,
            'ordre' => $this->ordre,
            'modules' => ModuleRessource::collection($this->whenLoaded('modules')),
            'competences_liees' => CompetenceRessource::collection($this->whenLoaded('competencesLiees')),
            'createur' => new \App\Http\Resources\Api\V1\UtilisateurRessource($this->whenLoaded('createur')),
            'nombre_modules' => $this->whenCounted('modules'),
            'nombre_inscrits' => $this->whenCounted('utilisateursInscrits'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
