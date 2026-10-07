<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduleRequest extends FormRequest
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
        $scope = $this->input('scope', $this->route('schedule')?->scope);

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'scope' => ['sometimes', 'required', 'in:company,area,device'],
            'area_id' => [Rule::requiredIf($scope === 'area'), 'nullable', 'exists:areas,id'],
            'device_id' => [Rule::requiredIf($scope === 'device'), 'nullable', 'exists:devices,id'],
            'start_time' => ['sometimes', 'required', 'date_format:H:i:s'],
            'end_time' => ['nullable', 'date_format:H:i:s'],
            'weekdays' => ['sometimes', 'required', 'regex:/^[0-6](,[0-6]){0,6}$/'],
            'is_active' => ['boolean'],
        ];
    }
}
