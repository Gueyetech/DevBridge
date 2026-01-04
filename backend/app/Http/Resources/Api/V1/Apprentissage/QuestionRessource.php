<?php

namespace App\Http\Resources\Api\V1\Apprentissage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionRessource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'question' => $this->question,
            'type' => $this->type,
            'options' => $this->options,
            'points' => $this->points,
            'ordre' => $this->ordre,
            'explication' => $this->explication,
            // Ne pas exposer la réponse correcte côté client pendant le quiz
            'reponse_correcte' => $this->when(
                $request->routeIs('admin.*') || $request->routeIs('mentor.*'),
                $this->reponse_correcte
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
