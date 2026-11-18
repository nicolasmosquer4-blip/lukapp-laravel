<?php

namespace App\Http\Requests;

use App\Models\UserSetting;
use Illuminate\Foundation\Http\FormRequest;

/** Actualización parcial de preferencias: solo se modifican las claves enviadas. */
class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'theme' => ['sometimes', 'in:light,dark,auto'],
            'currency' => ['sometimes', 'in:COP,USD,EUR'],
            'language' => ['sometimes', 'in:es,en'],
        ];
        foreach (UserSetting::NOTIFICATION_KEYS as $key) {
            $rules[$key] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $sent = array_intersect(array_keys($this->all()), array_keys($this->rules()));
                if (empty($sent) && $validator->errors()->isEmpty()) {
                    $validator->errors()->add('settings', 'Envía al menos una preferencia para actualizar.');
                }
            },
        ];
    }
}
