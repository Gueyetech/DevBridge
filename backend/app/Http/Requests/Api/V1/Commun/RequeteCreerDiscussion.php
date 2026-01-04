<?php

namespace App\Http\Requests\Api\V1\Commun;

use Illuminate\Foundation\Http\FormRequest;

class RequeteCreerDiscussion extends FormRequest
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
            'contenu' => 'required|string|min:10|max:10000',
            'categorie_id' => 'required|exists:categories_forum,id',
            'tags' => 'nullable|array|max:5',
            'tags.*' => 'string|max:50',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'titre.required' => 'Le titre de la discussion est requis.',
            'titre.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'contenu.required' => 'Le contenu de la discussion est requis.',
            'contenu.min' => 'Le contenu doit contenir au moins 10 caractères.',
            'contenu.max' => 'Le contenu ne peut pas dépasser 10000 caractères.',
            'categorie_id.required' => 'La catégorie est requise.',
            'categorie_id.exists' => 'La catégorie n\'existe pas.',
            'tags.max' => 'Vous ne pouvez pas ajouter plus de 5 tags.',
        ];
    }
}
