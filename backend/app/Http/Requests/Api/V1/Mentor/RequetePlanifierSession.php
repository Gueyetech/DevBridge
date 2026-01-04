<?php

namespace App\Http\Requests\Api\V1\Mentor;

use Illuminate\Foundation\Http\FormRequest;

class RequetePlanifierSession extends FormRequest
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
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'date_debut' => 'required|date|after:now',
            'date_fin' => 'required|date|after:date_debut',
            'lien_visioconference' => 'nullable|url|max:500',
            'notes_preparation' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre de la session est requis.',
            'titre.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'date_debut.required' => 'La date de début est requise.',
            'date_debut.after' => 'La date de début doit être dans le futur.',
            'date_fin.required' => 'La date de fin est requise.',
            'date_fin.after' => 'La date de fin doit être après la date de début.',
            'lien_visioconference.url' => 'Le lien de visioconférence n\'est pas valide.',
        ];
    }
}
