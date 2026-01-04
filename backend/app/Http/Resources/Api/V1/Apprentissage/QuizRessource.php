<?php

namespace App\Http\Resources\Api\V1\Apprentissage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'module_id' => $this->module_id,
            'titre' => $this->titre,
            'description' => $this->description,
            'duree_limite_minutes' => $this->duree_limite_minutes,
            'score_minimum_reussite' => $this->score_minimum_reussite,
            'score_maximum' => $this->score_maximum,
            'tentatives_maximum' => $this->tentatives_maximum,
            'est_actif' => $this->est_actif,
            'questions' => QuestionRessource::collection($this->whenLoaded('questions')),
            'nombre_questions' => $this->whenCounted('questions'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
