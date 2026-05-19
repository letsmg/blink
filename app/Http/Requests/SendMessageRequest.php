<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
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
            'recipient_id' => ['required', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
        ];
    }

    /**
     * Custom error messages in Portuguese.
     */
    public function messages(): array
    {
        return [
            'recipient_id.required' => 'O destinatário é obrigatório.',
            'recipient_id.exists' => 'O destinatário informado não existe.',
            'subject.required' => 'O assunto é obrigatório.',
            'body.required' => 'A mensagem é obrigatória.',
        ];
    }
}
