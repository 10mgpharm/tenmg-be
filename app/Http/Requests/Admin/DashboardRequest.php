<?php

namespace App\Http\Requests\Admin;

use App\Enums\DashboardAnalyticsDateFilterEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class DashboardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        // First, check if the user is authenticated
        if (! $user) {
            return false;
        }

        return $user->hasRole('admin');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {

        $this->merge([
            'date_filter' => strtoupper($this->input('dateFilter', 'today')),
            'from_date' => $this->input('fromDate'),
            'to_date' => $this->input('toDate'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_filter' => ['required', 'string', new Enum(DashboardAnalyticsDateFilterEnum::class)],
            'from_date' => ['nullable', 'required_if:date_filter,CUSTOM', 'date', 'before_or_equal:to_date'],
            'to_date' => ['nullable', 'required_if:date_filter,CUSTOM', 'date', 'after_or_equal:from_date'],
        ];
    }
}
