<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitorLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preferred_locale' => ['required', 'string', 'in:pt,en,es'],
            'origin' => ['required', 'string', 'in:manual,browser,gps,ip'],
            'country_code' => ['nullable', 'string', 'max:2'],
            'terms_accepted' => ['sometimes', 'boolean'],
            'privacy_accepted' => ['sometimes', 'boolean'],
            'terms_version' => ['nullable', 'string', 'max:50'],
            'privacy_version' => ['nullable', 'string', 'max:50'],
        ];
    }
}
