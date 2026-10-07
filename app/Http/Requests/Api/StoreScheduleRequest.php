<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'scope' => ['required', 'in:company,area,device'],
            'area_id' => [Rule::requiredIf($this->input('scope') === 'area'), 'nullable', 'exists:areas,id'],
            'device_id' => [Rule::requiredIf($this->input('scope') === 'device'), 'nullable', 'exists:devices,id'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['nullable', 'date_format:H:i:s'],
            'weekdays' => ['required', 'regex:/^[0-6](,[0-6]){0,6}$/'],
            'is_active' => ['boolean'],
        ];
    }
}
