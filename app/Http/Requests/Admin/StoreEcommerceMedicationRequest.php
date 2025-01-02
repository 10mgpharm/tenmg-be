<?php

namespace App\Http\Requests\Admin;

use App\Models\EcommerceMedicationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            ],
            'active' => [
                'sometimes',
                'boolean',
            ],
            // add array of variations to the payload
            'variations' => 'required|array|min:1',
            'variations.*.strength_value' => 'required|string',
            'variations.*.package' => 'required|string',
            'variations.*.presentation' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $name = $this->input('name');
                    $id = EcommerceMedicationType::where('name', $name)->value('id');

                    // Check if the variation presentation already exists for the medication type $this->input('name');
                    $exists = DB::table('ecommerce_medication_variations')
                        ->whereExists(function ($query) use ($value) {
                            $query->select(DB::raw(1))
                                ->from('ecommerce_presentations')
                                ->where('ecommerce_presentations.name', $value)
                                ->whereRaw('ecommerce_presentations.id = ecommerce_medication_variations.ecommerce_presentation_id');
                        })
                        ->where('ecommerce_medication_type_id', $id)
                        ->first();

                    if ($exists) {
                        $fail("The presentation '{$value}' already exists for the name '{$name}'.");
                    }
                },
            ],
            'variations.*.measurement' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $name = $this->input('name');
                    $id = EcommerceMedicationType::where('name', $name)->value('id');

                    // Check if the variation measurement already exists for the medication type $this->input('name');
                    $exists = DB::table('ecommerce_medication_variations')
                        ->whereExists(function ($query) use ($value) {
                            $query->select(DB::raw(1))
                                ->from('ecommerce_measurements')
                                ->where('ecommerce_measurements.name', $value)
                                ->whereRaw('ecommerce_measurements.id = ecommerce_medication_variations.ecommerce_measurement_id');
                        })
                        ->where('ecommerce_medication_type_id', $id)
                        ->first();

                    if ($exists) {
                        $fail("The measurement '{$value}' already exists for the name '{$name}'.");
                    }
                },
            ],
            'variations.*.weight' => [ 'nullable', 'sometimes', 'string',],
        ];
    }
}
