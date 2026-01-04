<?php

namespace App\Http\Requests\Api\V1\Commun;

use Illuminate\Foundation\Http\FormRequest;

class RequeteEnvoyerMessage extends FormRequest
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
            'contenu' => 'required|string|min:1|max:5000',
            'type' => 'nullable|in:texte,image,fichier,code',
            'fichiers' => 'nullable|array|max:5',
            'fichiers.*' => 'file|max:10240', // 10 MB max
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'contenu.required' => 'Le contenu du message est requis.',
            'contenu.max' => 'Le message ne peut pas dépasser 5000 caractères.',
            'type.in' => 'Le type de message n\'est pas valide.',
            'fichiers.max' => 'Vous ne pouvez pas envoyer plus de 5 fichiers.',
            'fichiers.*.max' => 'Chaque fichier ne peut pas dépasser 10 Mo.',
        ];
    }
}
