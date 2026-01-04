<?php

namespace App\Http\Resources\Api\V1\Mentor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RevisionCodeRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'demande_id' => $this->demande_id,
            'mentor_id' => $this->mentor_id,
            'statut' => $this->statut,
            'commentaires' => $this->commentaires,
            'points_positifs' => $this->points_positifs,
            'points_amelioration' => $this->points_amelioration,
            'note_generale' => $this->note_generale,
            'accepte_a' => $this->accepte_a?->toISOString(),
            'refuse_a' => $this->refuse_a?->toISOString(),
            'termine_a' => $this->termine_a?->toISOString(),
            'mentor' => new \App\Http\Resources\Api\V1\UtilisateurRessource($this->whenLoaded('mentor')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
