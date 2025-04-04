<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowBusinessSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        
        if($user->hasRole('admin') || $user->hasRole('operation') || $user->hasRole('support')){
            return true;
        }

        return (bool) $this->user()->ownerBusinessType;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }
}
