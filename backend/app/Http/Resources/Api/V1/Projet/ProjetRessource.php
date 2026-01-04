<?php

namespace App\Http\Resources\Api\V1\Projet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjetRessource extends JsonResource
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
            'technologies' => $this->technologies,
            'difficulte' => $this->difficulte,
            'statut' => $this->statut,
            'date_debut' => $this->date_debut?->toISOString(),
            'date_fin_prevue' => $this->date_fin_prevue?->toISOString(),
            'date_fin_reelle' => $this->date_fin_reelle?->toISOString(),
            'nombre_maximum_participants' => $this->nombre_maximum_participants,
            'est_public' => $this->est_public,
            'url_depot' => $this->url_depot,
            'createur' => new \App\Http\Resources\Api\V1\UtilisateurRessource($this->whenLoaded('createur')),
            'membres' => \App\Http\Resources\Api\V1\UtilisateurRessource::collection($this->whenLoaded('membres')),
            'taches' => TacheRessource::collection($this->whenLoaded('taches')),
            'nombre_membres' => $this->whenCounted('membres'),
            'nombre_taches' => $this->whenCounted('taches'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
