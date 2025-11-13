<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MeasurementFiltersRequest extends FormRequest
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
        $allNames = array_keys(config('measurements.types', []));
        $namesRule = $allNames ? 'in:' . implode(',', $allNames) : 'string';

        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'names' => ['nullable', 'array'],
            'names.*' => ['string', $namesRule],
            'interval' => ['nullable', 'in:hourly,daily,weekly'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:200'],
            'subscription_id' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toDto(): \App\DataTransferObjects\MeasurementFilterData
    {
        return \App\DataTransferObjects\MeasurementFilterData::fromArray($this->validated());
    }
}
