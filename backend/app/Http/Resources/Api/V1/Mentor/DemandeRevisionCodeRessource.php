<?php

namespace App\Http\Resources\Api\V1\Mentor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DemandeRevisionCodeRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'etudiant_id' => $this->etudiant_id,
            'projet_id' => $this->projet_id,
            'tache_id' => $this->tache_id,
            'titre' => $this->titre,
            'description' => $this->description,
            'statut' => $this->statut,
            'urgence' => $this->urgence,
            'technologies' => $this->technologies,
            'competences_ciblees' => $this->competences_ciblees,
            'etudiant' => new \App\Http\Resources\Api\V1\UtilisateurRessource($this->whenLoaded('etudiant')),
            'projet' => new \App\Http\Resources\Api\V1\Projet\ProjetRessource($this->whenLoaded('projet')),
            'tache' => new \App\Http\Resources\Api\V1\Projet\TacheRessource($this->whenLoaded('tache')),
            'revisions' => RevisionCodeRessource::collection($this->whenLoaded('revisions')),
            'fichiers' => $this->whenLoaded('fichiers'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
