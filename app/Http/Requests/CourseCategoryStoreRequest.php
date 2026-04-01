<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseCategoryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create course categories');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Category name is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        return;
    }
}
