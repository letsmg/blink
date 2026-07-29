<?php

namespace App\Http\Requests;

use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;

class RegisterPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para registro de paciente.
     * Suporta tanto o formato legado (name) quanto o novo com PII (first_name/last_name/display_name).
     */
    public function rules(): array
    {
        return [
            'name'             => ['required_without:first_name', 'string', 'max:255'],
            'first_name'       => ['nullable', 'string', 'max:255'],
            'last_name'        => ['nullable', 'string', 'max:255'],
            'display_name'     => ['nullable', 'string', 'max:255'],
            'email'            => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'cpf'              => ['required', 'string', new Cpf],
            'date_of_birth'    => ['required', 'date', 'before:today'],
            'main_complaint'   => ['nullable', 'string', 'max:1000'],
            // Endereço (será criptografado)
            'street'           => ['nullable', 'string', 'max:255'],
            'neighborhood'     => ['nullable', 'string', 'max:255'],
            'city'             => ['nullable', 'string', 'max:255'],
            'state'            => ['nullable', 'string', 'size:2'],
            'zip_code'         => ['nullable', 'string', 'max:10'],
            // Telefones (serão criptografados)
            'phone1'           => ['nullable', 'string', 'max:20'],
            'phone2'           => ['nullable', 'string', 'max:20'],
            'clinical_history' => ['nullable', 'string', 'max:5000'],
            'visitor_uuid'     => ['nullable', 'string', 'size:36'],
            'terms_accepted'   => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->has('email') ? strtolower(trim($this->email)) : null,
            'cpf'   => $this->has('cpf') ? preg_replace('/\D/', '', $this->cpf) : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'name.required'             => 'O nome é obrigatório.',
            'email.required'            => 'O e-mail é obrigatório.',
            'email.email'               => 'Informe um e-mail válido.',
            'email.unique'              => 'Este e-mail já está cadastrado.',
            'password.required'         => 'A senha é obrigatória.',
            'password.min'              => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed'        => 'A confirmação de senha não confere.',
            'cpf.required'              => 'O CPF é obrigatório.',
            'date_of_birth.required'    => 'A data de nascimento é obrigatória.',
            'date_of_birth.before'      => 'A data de nascimento deve ser anterior a hoje.',
        ];
    }
}