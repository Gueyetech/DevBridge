<?php

namespace App\Http\Requests\Api\V1\Mentor;

use Illuminate\Foundation\Http\FormRequest;

class RequeteDonnerFeedback extends FormRequest
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
            'etudiant_id' => 'required|exists:utilisateurs,id',
            'projet_id' => 'nullable|exists:projets,id',
            'tache_id' => 'nullable|exists:taches,id',
            'contenu' => 'required|string|min:10|max:5000',
            'type' => 'required|in:code,projet,general,comportement',
            'points_positifs' => 'nullable|array',
            'points_positifs.*' => 'string|max:500',
            'points_amelioration' => 'nullable|array',
            'points_amelioration.*' => 'string|max:500',
            'note_generale' => 'nullable|integer|min:1|max:10',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'etudiant_id.required' => 'L\'identifiant de l\'étudiant est requis.',
            'etudiant_id.exists' => 'L\'étudiant n\'existe pas.',
            'contenu.required' => 'Le contenu du feedback est requis.',
            'contenu.min' => 'Le feedback doit contenir au moins 10 caractères.',
            'contenu.max' => 'Le feedback ne peut pas dépasser 5000 caractères.',
            'type.required' => 'Le type de feedback est requis.',
            'type.in' => 'Le type de feedback n\'est pas valide.',
            'note_generale.min' => 'La note doit être au moins 1.',
            'note_generale.max' => 'La note ne peut pas dépasser 10.',
        ];
    }
}
