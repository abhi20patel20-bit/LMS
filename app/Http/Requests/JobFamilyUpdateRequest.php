<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobFamilyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update job families');
    }

    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
            'mandatory_course_ids' => ['nullable', 'array'],
            'mandatory_course_ids.*' => ['integer', 'exists:courses,id'],
            'optional_course_ids' => ['nullable', 'array'],
            'optional_course_ids.*' => ['integer', 'exists:courses,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.exists'  => 'The selected company is invalid.',
            'name.required'      => 'Job family name cannot be empty.',
            'course_ids.*.exists' => 'The selected course is invalid.',
            'mandatory_course_ids.*.exists' => 'The selected mandatory course is invalid.',
            'optional_course_ids.*.exists' => 'The selected optional course is invalid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $user = auth()->user();

        if ($user->hasRole('company-admin')) {
            $this->merge([
                'company_id' => $user->company_id,
            ]);
        }
    }
}
