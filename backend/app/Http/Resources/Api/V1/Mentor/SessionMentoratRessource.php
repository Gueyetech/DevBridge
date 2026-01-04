<?php

namespace App\Http\Resources\Api\V1\Mentor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionMentoratRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mentorat_id' => $this->mentorat_id,
            'titre' => $this->titre,
            'description' => $this->description,
            'date_debut' => $this->date_debut?->toISOString(),
            'date_fin' => $this->date_fin?->toISOString(),
            'statut' => $this->statut,
            'lien_visioconference' => $this->lien_visioconference,
            'notes' => $this->notes,
            'mentorat' => new MentoratRessource($this->whenLoaded('mentorat')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
