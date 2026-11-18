<?php

namespace App\Http\Requests;

use App\Models\Movement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Valida la creación (POST) y actualización (PUT) de movimientos.
 * La categoría se puede enviar por id (category_id) o por nombre (category),
 * que es lo que envía el formulario original del frontend.
 */
class MovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'description' => trim(strip_tags((string) $this->input('description'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(Movement::TYPES)],
            'category_id' => ['required_without:category', 'nullable', 'integer', 'exists:categories,id'],
            'category' => ['required_without:category_id', 'nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'min:1', 'max:80'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
            // Solo se permite forzar "cancelado"; los demás estados se calculan por fecha.
            'status' => ['nullable', Rule::in(Movement::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required_without' => 'Selecciona una categoría.',
            'category.required_without' => 'Selecciona una categoría.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                $category = $this->resolveCategory();
                if (! $category) {
                    $validator->errors()->add('category', 'La categoría seleccionada no existe.');
                } elseif ($category->type !== $this->input('type')) {
                    $validator->errors()->add('category', 'La categoría no corresponde al tipo de movimiento.');
                }
            },
        ];
    }

    private ?\App\Models\Category $resolvedCategory = null;

    public function resolveCategory(): ?\App\Models\Category
    {
        if ($this->resolvedCategory) {
            return $this->resolvedCategory;
        }

        $query = \App\Models\Category::query();

        return $this->resolvedCategory = $this->filled('category_id')
            ? $query->find($this->integer('category_id'))
            : $query->where('name', $this->input('category'))->where('type', $this->input('type'))->first();
    }

    /** Datos listos para guardar, con el estado calculado por la regla de negocio. */
    public function movementData(): array
    {
        $status = $this->input('status') === Movement::STATUS_CANCELLED
            ? Movement::STATUS_CANCELLED
            : Movement::statusForDate($this->input('date'));

        return [
            'type' => $this->input('type'),
            'category_id' => $this->resolveCategory()->id,
            'description' => $this->input('description'),
            'amount' => round((float) $this->input('amount'), 2),
            'date' => $this->input('date'),
            'status' => $status,
        ];
    }
}
