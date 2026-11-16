<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The assessing professional's details: profile form and Quick Assessment step 1.
 */
class CaregiverRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'profession_id' => ['required', 'integer', Rule::exists('professions', 'id')->where('is_active', true)],
            'country_code' => ['required', 'string', 'size:2', Rule::exists('countries', 'code')->where('is_active', true)],
            'institution' => ['required', 'string', 'max:40', 'regex:/^(local:\d+|ror:0[a-z0-9]{6}[0-9]{2}|custom)$/'],
            'institution_custom_name' => ['nullable', 'required_if:institution,custom', 'string', 'min:3', 'max:255'],
            'professional_title' => ['nullable', 'string', 'max:100'],
            'organization' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'profession_id.required' => 'Please select your profession.',
            'country_code.required' => 'Please select your country.',
            'institution.required' => 'Please choose an institution from the list or add a new one.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'profession_id' => 'profession',
            'country_code' => 'country',
            'institution_custom_name' => 'institution name',
        ];
    }
}
