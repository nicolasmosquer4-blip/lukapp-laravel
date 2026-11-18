<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(strip_tags((string) $this->input('name'))),
            'phone' => $this->filled('phone') ? trim(strip_tags((string) $this->input('phone'))) : null,
            'occupation' => $this->filled('occupation') ? trim(strip_tags((string) $this->input('occupation'))) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'occupation' => ['nullable', 'string', 'max:100'],
        ];
    }
}
