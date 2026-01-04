<?php

namespace App\Http\Requests\Api\V1\Mentor;

use Illuminate\Foundation\Http\FormRequest;

class RequeteRevisionCode extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'commentaires' => 'required|array|min:1',
            'commentaires.*.ligne' => 'required|integer|min:1',
            'commentaires.*.fichier' => 'required|string|max:255',
            'commentaires.*.contenu' => 'required|string|max:2000',
            'commentaires.*.type' => 'nullable|in:suggestion,erreur,amelioration,question',
            'points_positifs' => 'nullable|array',
            'points_positifs.*' => 'string|max:500',
            'points_amelioration' => 'nullable|array',
            'points_amelioration.*' => 'string|max:500',
            'note_generale' => 'nullable|integer|min:1|max:10',
            'est_approuve' => 'required|boolean',
            'demande_modifications' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'commentaires.required' => 'Au moins un commentaire est requis.',
            'commentaires.min' => 'Au moins un commentaire est requis.',
            'commentaires.*.ligne.required' => 'Le numéro de ligne est requis.',
            'commentaires.*.fichier.required' => 'Le nom du fichier est requis.',
            'commentaires.*.contenu.required' => 'Le contenu du commentaire est requis.',
            'est_approuve.required' => 'Veuillez indiquer si le code est approuvé.',
        ];
    }
}
