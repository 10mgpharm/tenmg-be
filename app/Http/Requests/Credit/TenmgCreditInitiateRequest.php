<?php

namespace App\Http\Requests\Credit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class TenmgCreditInitiateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Accept any Marq-style payload (non-empty object), preserving keys as-is.
     */
    public function rules(): array
    {
        return [
            '*' => 'sometimes',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Get the request content
        $content = $this->getContent();

        // Check if body is empty or just whitespace
        if (empty(trim($content))) {
            throw ValidationException::withMessages([
                'payload' => ['Request body cannot be empty. Please provide a valid JSON payload.'],
            ]);
        }

        // Decode JSON to check if it's valid and non-empty
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                'payload' => ['Request body must be valid JSON.'],
            ]);
        }

        // Check if decoded result is empty (null, empty array, etc.)
        if (empty($decoded) || ! is_array($decoded)) {
            throw ValidationException::withMessages([
                'payload' => ['Request body must be a non-empty JSON object.'],
            ]);
        }
    }
}
