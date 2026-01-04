<?php

namespace App\Http\Resources\Api\V1\Etudiant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableauDeBordRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'utilisateur' => new \App\Http\Resources\Api\V1\UtilisateurRessource($this->resource),
            'statistiques' => $this->statistiques ?? [],
            'parcours_actifs' => $this->parcours_actifs ?? [],
            'projets_actifs' => $this->projets_actifs ?? [],
            'badges_recents' => $this->badges_recents ?? [],
            'activite_recente' => $this->activite_recente ?? [],
        ];
    }
}
