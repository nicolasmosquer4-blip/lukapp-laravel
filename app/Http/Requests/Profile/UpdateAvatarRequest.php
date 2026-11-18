<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Imagen real (se verifica el contenido, no solo la extensión), máx. 5 MB
        return [
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120', 'dimensions:max_width=4000,max_height=4000'],
        ];
    }

    public function messages(): array
    {
        return ['avatar.max' => 'La imagen no debe superar los 5 MB.'];
    }
}
