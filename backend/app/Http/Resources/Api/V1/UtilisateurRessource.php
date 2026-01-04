<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UtilisateurRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prenom' => $this->prenom,
            'nom' => $this->nom,
            'nom_complet' => $this->nom_complet,
            'email' => $this->email,
            'role' => $this->role,
            'niveau' => $this->niveau,
            'points' => $this->points,
            'avatar' => $this->avatar,
            'est_actif' => $this->est_actif,
            'est_etudiant' => $this->est_etudiant,
            'est_mentor' => $this->est_mentor,
            'est_administrateur' => $this->est_administrateur,
            'profil' => $this->whenLoaded('profil', function () {
                return [
                    'bio' => $this->profil->bio,
                    'github_url' => $this->profil->github_url,
                    'linkedin_url' => $this->profil->linkedin_url,
                    'portfolio_url' => $this->profil->portfolio_url,
                    'ville' => $this->profil->ville,
                    'pays' => $this->profil->pays,
                    'technologies' => $this->profil->technologies,
                    'est_disponible_mentorat' => $this->profil->est_disponible_mentorat,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
