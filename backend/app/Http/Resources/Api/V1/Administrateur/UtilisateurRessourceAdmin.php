<?php

namespace App\Http\Resources\Api\V1\Administrateur;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UtilisateurRessourceAdmin extends JsonResource
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
            'email_verifie_a' => $this->email_verified_at?->toISOString(),
            'derniere_connexion' => $this->derniere_connexion?->toISOString(),
            'profil' => $this->whenLoaded('profil', function () {
                return [
                    'id' => $this->profil->id,
                    'bio' => $this->profil->bio,
                    'github_url' => $this->profil->github_url,
                    'linkedin_url' => $this->profil->linkedin_url,
                    'portfolio_url' => $this->profil->portfolio_url,
                    'ville' => $this->profil->ville,
                    'pays' => $this->profil->pays,
                    'niveau' => $this->profil->niveau,
                    'technologies' => $this->profil->technologies,
                    'est_disponible_mentorat' => $this->profil->est_disponible_mentorat,
                ];
            }),
            'statistiques' => $this->when($this->relationLoaded('parcoursInscrits'), function () {
                return [
                    'parcours_inscrits' => $this->parcoursInscrits->count(),
                    'projets' => $this->projets->count(),
                    'badges' => $this->badges->count(),
                    'competences' => $this->competences->count(),
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
