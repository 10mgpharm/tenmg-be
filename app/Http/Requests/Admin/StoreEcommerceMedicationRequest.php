<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusEnum;
use App\Models\EcommerceMedicationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreEcommerceMedicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(EcommerceMedicationType::class)],
            'status' => [
                'sometimes',
                'string',
                new Enum(StatusEnum::class),
                function ($attribute, $value, $fail) {
                    if ($this->active && ! in_array($value, [StatusEnum::APPROVED->value, null])) {
                        $fail('The status must be "APPROVED" or null when active is true.');
                    }
                },
            ],
            'active' => [
                'sometimes',
                'boolean',
            ],
            // add array of variations to the payload
            'variations' => 'required|array|min:1',
            'variations.*' => [
                'required',
                'string',
                'min:1',
                function ($attribute, $value, $fail) {
                    $name = $this->input('name');

                    // Check if the variation already exists for the given name
                    $exists = DB::table('variations')
                        ->where('name', $value)
                        ->whereExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('ecommerce_medication_types')
                                ->whereRaw('ecommerce_medication_types.id = variations.ecommerce_medication_type_id')
                                ->where('ecommerce_medication_types.name', $this->input('name'));
                        })
                        ->exists();

                    if ($exists) {
                        $fail("The variation '{$value}' already exists for the name '{$name}'.");
                    }
                },
            ],
        ];
    }
}
