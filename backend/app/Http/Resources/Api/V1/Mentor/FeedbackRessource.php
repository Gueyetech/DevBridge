<?php

namespace App\Http\Resources\Api\V1\Mentor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeedbackRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mentor_id' => $this->mentor_id,
            'etudiant_id' => $this->etudiant_id,
            'projet_id' => $this->projet_id,
            'tache_id' => $this->tache_id,
            'type' => $this->type,
            'contenu' => $this->contenu,
            'points_positifs' => $this->points_positifs,
            'points_amelioration' => $this->points_amelioration,
            'note_generale' => $this->note_generale,
            'est_lu' => $this->est_lu,
            'lu_a' => $this->lu_a?->toISOString(),
            'mentor' => new \App\Http\Resources\Api\V1\UtilisateurRessource($this->whenLoaded('mentor')),
            'etudiant' => new \App\Http\Resources\Api\V1\UtilisateurRessource($this->whenLoaded('etudiant')),
            'projet' => new \App\Http\Resources\Api\V1\Projet\ProjetRessource($this->whenLoaded('projet')),
            'tache' => new \App\Http\Resources\Api\V1\Projet\TacheRessource($this->whenLoaded('tache')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
