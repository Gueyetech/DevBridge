<?php

namespace App\Http\Resources\Api\V1\Mentor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MentoratRessource extends JsonResource
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
            'statut' => $this->statut,
            'message_demande' => $this->message_demande,
            'message_acceptation' => $this->message_acceptation,
            'message_refus' => $this->message_refus,
            'demande_a' => $this->demande_a?->toISOString(),
            'accepte_a' => $this->accepte_a?->toISOString(),
            'termine_a' => $this->termine_a?->toISOString(),
            'mentor' => new \App\Http\Resources\Api\V1\UtilisateurRessource($this->whenLoaded('mentor')),
            'etudiant' => new \App\Http\Resources\Api\V1\UtilisateurRessource($this->whenLoaded('etudiant')),
            'sessions' => SessionMentoratRessource::collection($this->whenLoaded('sessions')),
            'nombre_sessions' => $this->whenCounted('sessions'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
