<?php

namespace App\Http\Resources\Api\V1\Apprentissage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parcours_id' => $this->parcours_id,
            'titre' => $this->titre,
            'description' => $this->description,
            'ordre' => $this->ordre,
            'lecons' => LeconRessource::collection($this->whenLoaded('lecons')),
            'quiz' => new QuizRessource($this->whenLoaded('quiz')),
            'nombre_lecons' => $this->whenCounted('lecons'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
