<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDeviceRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'area_id' => ['sometimes', 'required', 'exists:areas,id'],
            'device_type_id' => ['nullable', 'exists:device_types,id'],
            'device_model_id' => ['nullable', 'exists:device_models,id'],
            'status' => ['sometimes', 'required', 'in:on,off,offline,maintenance'],
        ];
    }
}
