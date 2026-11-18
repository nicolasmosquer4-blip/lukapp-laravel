<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Crear (POST) y editar (PUT) metas. Al editar, el ahorro ("saved") no se modifica:
 * solo cambia mediante abonos/retiros (misma regla que metaModel.js).
 */
class GoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(strip_tags((string) $this->input('name'))),
            'icon' => $this->filled('icon') ? trim(strip_tags((string) $this->input('icon'))) : '🎯',
            'deadline' => $this->filled('deadline') ? $this->input('deadline') : null,
        ]);
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('post');

        return [
            'name' => ['required', 'string', 'min:1', 'max:70'],
            'icon' => ['nullable', 'string', 'max:16'],
            'target' => ['required', 'numeric', 'gt:0', 'max:9999999999999'],
            'saved' => $isCreate ? ['nullable', 'numeric', 'min:0', 'max:9999999999999'] : ['prohibited'],
            'deadline' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return ['saved.prohibited' => 'El ahorro solo puede modificarse mediante abonos o retiros.'];
    }
}
