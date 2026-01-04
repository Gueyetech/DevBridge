<?php

namespace App\Http\Requests\Api\V1\Administrateur;

use App\Enums\RoleUtilisateur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class RequeteCreerUtilisateur extends FormRequest
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
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:utilisateurs,email|max:255',
            'mot_de_passe' => ['required', 'confirmed', Password::defaults()],
            'role' => ['nullable', new Enum(RoleUtilisateur::class)],
            'est_actif' => 'nullable|boolean',
            'niveau' => 'nullable|in:debutant,intermediaire,avance',
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
            'prenom.required' => 'Le prénom est requis.',
            'nom.required' => 'Le nom est requis.',
            'email.required' => 'L\'email est requis.',
            'email.email' => 'L\'email n\'est pas valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'mot_de_passe.required' => 'Le mot de passe est requis.',
            'mot_de_passe.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}
