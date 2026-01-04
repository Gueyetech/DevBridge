<?php

namespace App\Http\Requests\Api\V1\Administrateur;

use Illuminate\Foundation\Http\FormRequest;

class RequeteMettreAJourParcours extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->est_administrateur;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'titre' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:5000',
            'technologie' => 'sometimes|string|max:100',
            'difficulte' => 'sometimes|in:debutant,intermediaire,avance',
            'duree_estimee_heures' => 'nullable|integer|min:1',
            'image_couverture' => 'nullable|string|max:500',
            'prerequis' => 'nullable|array',
            'prerequis.*' => 'string|max:255',
            'objectifs' => 'nullable|array',
            'objectifs.*' => 'string|max:255',
            'est_publie' => 'nullable|boolean',
            'ordre' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'difficulte.in' => 'Le niveau de difficulté n\'est pas valide.',
        ];
    }
}
