<?php

namespace App\Http\Resources\Api\V1\Apprentissage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificatRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'utilisateur_id' => $this->utilisateur_id,
            'competence_id' => $this->competence_id,
            'parcours_id' => $this->parcours_id,
            'type' => $this->type,
            'code_verification' => $this->code_verification,
            'date_emission' => $this->date_emission?->toISOString(),
            'nombre_telechargements' => $this->nombre_telechargements,
            'competence' => new CompetenceRessource($this->whenLoaded('competence')),
            'parcours' => new ParcoursApprentissageRessource($this->whenLoaded('parcours')),
            'valide_par' => new \App\Http\Resources\Api\V1\UtilisateurRessource($this->whenLoaded('validePar')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
