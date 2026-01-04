<?php

namespace App\Http\Requests\Api\V1\Etudiant;

use Illuminate\Foundation\Http\FormRequest;

class RequeteInscriptionParcours extends FormRequest
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
            'motivation' => 'nullable|string|max:500',
            'objectifs_personnels' => 'nullable|array',
            'objectifs_personnels.*' => 'string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'motivation.max' => 'La motivation ne peut pas dépasser 500 caractères.',
        ];
    }
}
