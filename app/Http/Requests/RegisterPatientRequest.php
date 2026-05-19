<?php

namespace App\Http\Requests;

use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;

class RegisterPatientRequest extends FormRequest
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
     * Double validation: backend security layer for patient registration.
     * 
     * Sanitização de entrada aplicada via SanitizeInput middleware.
     * CPF validado estruturalmente com algoritmo de dígitos verificadores.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'cpf' => ['required', 'string', new Cpf], // Validação estrutural do CPF
            'date_of_birth' => ['required', 'date', 'before:today'],
            'main_complaint' => ['nullable', 'string', 'max:1000'],
            'street' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'size:2'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'clinical_history' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Prepare the data for validation (sanitize before validation).
     * Remove máscaras de CPF e telefone antes da validação.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->has('email') ? strtolower(trim($this->email)) : null,
            'cpf' => $this->has('cpf') ? preg_replace('/\D/', '', $this->cpf) : null,
        ]);
    }

    /**
     * Custom error messages in Portuguese.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'A confirmação de senha não confere.',
            'cpf.required' => 'O CPF é obrigatório.',
            'date_of_birth.required' => 'A data de nascimento é obrigatória.',
            'date_of_birth.before' => 'A data de nascimento deve ser anterior a hoje.',
        ];
    }
}
