<?php

namespace App\Http\Requests\Api\V1\Administrateur;

use App\Enums\RoleUtilisateur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class RequeteMettreAJourUtilisateur extends FormRequest
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
        $utilisateurId = $this->route('id');

        return [
            'prenom' => 'sometimes|string|max:255',
            'nom' => 'sometimes|string|max:255',
            'email' => "sometimes|email|unique:utilisateurs,email,{$utilisateurId}|max:255",
            'mot_de_passe' => ['sometimes', 'confirmed', Password::defaults()],
            'role' => ['sometimes', new Enum(RoleUtilisateur::class)],
            'est_actif' => 'sometimes|boolean',
            'profil' => 'sometimes|array',
            'profil.niveau' => 'sometimes|in:debutant,intermediaire,avance',
            'profil.bio' => 'nullable|string|max:1000',
            'profil.technologies' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.email' => 'L\'email n\'est pas valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'mot_de_passe.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}
