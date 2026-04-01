<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobRoleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create job roles');
    }

    public function rules(): array
    {
        return [
            'job_family_id' => ['nullable', 'integer', 'exists:job_families,id'],
            'name' => ['required', 'string', 'max:255'],
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
            'name.required'      => 'Job role name is required.',
            'course_ids.*.exists' => 'The selected course is invalid.',
            'mandatory_course_ids.*.exists' => 'The selected mandatory course is invalid.',
            'optional_course_ids.*.exists' => 'The selected optional course is invalid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        return;
    }
}
