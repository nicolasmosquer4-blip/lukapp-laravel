<?php

namespace App\Http\Requests;

use App\Models\GoalContribution;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoalContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->input('type', GoalContribution::TYPE_DEPOSIT),
            'comment' => $this->filled('comment') ? trim(strip_tags((string) $this->input('comment'))) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([GoalContribution::TYPE_DEPOSIT, GoalContribution::TYPE_WITHDRAW])],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999999'],
            'comment' => ['nullable', 'string', 'max:50'],
        ];
    }
}
