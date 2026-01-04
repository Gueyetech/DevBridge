<?php

namespace App\Http\Requests\Api\V1\Commun;

use Illuminate\Foundation\Http\FormRequest;

class RequeteCreerMessage extends FormRequest
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
            'contenu' => 'required|string|min:1|max:10000',
            'message_parent_id' => 'nullable|exists:messages_forum,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'contenu.required' => 'Le contenu du message est requis.',
            'contenu.max' => 'Le message ne peut pas dépasser 10000 caractères.',
            'message_parent_id.exists' => 'Le message parent n\'existe pas.',
        ];
    }
}
