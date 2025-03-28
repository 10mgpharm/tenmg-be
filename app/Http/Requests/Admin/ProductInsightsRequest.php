<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductInsightsFilterEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ProductInsightsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        // check if user is authenticated.
        if(!$user){
            return false;
        }

        // check if user is an admin.
        return $user->hasRole('admin');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {

        $this->merge([
            'date_filter' => strtoupper($this->input('dateFilter', 'today')),
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
            'date_filter' => ['required', 'string', new Enum(ProductInsightsFilterEnum::class)],
        ];
    }
}
