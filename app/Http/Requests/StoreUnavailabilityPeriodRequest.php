<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação para criação/atualização de períodos de indisponibilidade.
 * Regras: data fim >= data início, formato de data válido.
 */
class StoreUnavailabilityPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.required' => 'A data inicial é obrigatória.',
            'start_date.date' => 'A data inicial deve ser uma data válida.',
            'start_date.date_format' => 'A data inicial deve estar no formato AAAA-MM-DD.',
            'start_date.after_or_equal' => 'A data inicial não pode ser anterior a hoje.',
            'end_date.required' => 'A data final é obrigatória.',
            'end_date.date' => 'A data final deve ser uma data válida.',
            'end_date.date_format' => 'A data final deve estar no formato AAAA-MM-DD.',
            'end_date.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
            'reason.max' => 'O motivo deve ter no máximo 500 caracteres.',
        ];
    }
}
