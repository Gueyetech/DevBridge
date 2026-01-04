<?php

namespace App\Http\Requests\Api\V1\Etudiant;

use Illuminate\Foundation\Http\FormRequest;

class RequeteSoumissionQuiz extends FormRequest
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
            'reponses' => 'required|array',
            'reponses.*.question_id' => 'required|exists:questions,id',
            'reponses.*.reponse' => 'required',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reponses.required' => 'Les réponses sont requises.',
            'reponses.array' => 'Les réponses doivent être un tableau.',
            'reponses.*.question_id.required' => 'L\'identifiant de la question est requis.',
            'reponses.*.question_id.exists' => 'La question n\'existe pas.',
            'reponses.*.reponse.required' => 'La réponse est requise.',
        ];
    }
}
