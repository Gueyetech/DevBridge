<?php

namespace App\Http\Requests\Api\V1\Etudiant;

use Illuminate\Foundation\Http\FormRequest;

class RequeteMiseAJourProfil extends FormRequest
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
            'prenom' => 'sometimes|string|max:255',
            'nom' => 'sometimes|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'github_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'portfolio_url' => 'nullable|url|max:255',
            'ville' => 'nullable|string|max:100',
            'pays' => 'nullable|string|max:100',
            'technologies' => 'nullable|array',
            'technologies.*' => 'string|max:50',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'prenom.max' => 'Le prénom ne peut pas dépasser 255 caractères.',
            'nom.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'bio.max' => 'La bio ne peut pas dépasser 1000 caractères.',
            'github_url.url' => 'L\'URL GitHub n\'est pas valide.',
            'linkedin_url.url' => 'L\'URL LinkedIn n\'est pas valide.',
            'portfolio_url.url' => 'L\'URL du portfolio n\'est pas valide.',
        ];
    }
}
