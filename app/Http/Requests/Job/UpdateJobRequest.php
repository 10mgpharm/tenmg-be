<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('status')) {
            $this->merge([
                'status' => strtoupper($this->input('status')),
            ]);
        }

        if ($this->has('location_type')) {
            $this->merge([
                'location_type' => strtoupper($this->input('location_type')),
            ]);
        }
    }

    public function rules(): array
    {
        $jobId = $this->route('job')?->id;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('job_listings', 'slug')->ignore($jobId),
            ],
            'department' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'array'],
            'employment_type.*' => ['string', 'max:50'],
            'mission' => ['nullable', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'requirements' => ['nullable', 'array'],
            'requirements.*' => ['string'],
            'compensation' => ['nullable', 'string'],
            'flexibility' => ['nullable', 'string'],
            'how_to_apply' => ['nullable', 'string'],
            'apply_url' => ['nullable', 'url', 'max:2048'],
            'location_type' => ['nullable', 'in:REMOTE,HYBRID,ONSITE'],
            'about_company' => ['nullable', 'string'],
            'status' => ['nullable', 'in:PUBLISHED,DRAFT,CLOSED'],
        ];
    }
}
