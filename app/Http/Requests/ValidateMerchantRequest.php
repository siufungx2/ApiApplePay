<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateMerchantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'validateUrl' => 'required',
            'origin' => 'required',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            // Capture origin from request header and assign in validation data
            'origin' => $this->header('Origin'),
        ]);
    }

}
