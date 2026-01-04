<?php

namespace App\Http\Requests\Api\V1\Administrateur;

use Illuminate\Foundation\Http\FormRequest;

class RequeteCreerParcours extends FormRequest
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
            'titre' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'technologie' => 'required|string|max:100',
            'difficulte' => 'required|in:debutant,intermediaire,avance',
            'duree_estimee_heures' => 'nullable|integer|min:1',
            'image_couverture' => 'nullable|string|max:500',
            'prerequis' => 'nullable|array',
            'prerequis.*' => 'string|max:255',
            'objectifs' => 'nullable|array',
            'objectifs.*' => 'string|max:255',
            'est_publie' => 'nullable|boolean',
            'ordre' => 'nullable|integer|min:0',
            'modules' => 'nullable|array',
            'modules.*.titre' => 'required|string|max:255',
            'modules.*.description' => 'nullable|string|max:1000',
            'modules.*.ordre' => 'nullable|integer|min:1',
            'modules.*.lecons' => 'nullable|array',
            'modules.*.lecons.*.titre' => 'required|string|max:255',
            'modules.*.lecons.*.contenu' => 'nullable|string',
            'modules.*.lecons.*.type' => 'nullable|in:texte,video,exercice,code',
            'modules.*.lecons.*.duree_estimee_minutes' => 'nullable|integer|min:1',
            'modules.*.lecons.*.ordre' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre du parcours est requis.',
            'description.required' => 'La description du parcours est requise.',
            'technologie.required' => 'La technologie est requise.',
            'difficulte.required' => 'Le niveau de difficulté est requis.',
            'difficulte.in' => 'Le niveau de difficulté n\'est pas valide.',
            'modules.*.titre.required' => 'Le titre du module est requis.',
            'modules.*.lecons.*.titre.required' => 'Le titre de la leçon est requis.',
        ];
    }
}
