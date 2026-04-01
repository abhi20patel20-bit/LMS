<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class DepartmentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create departments');
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:255'],
            'slug'              => ['required', 'string', 'max:255', 'unique:departments,slug'],
            'custom_domain'     => ['nullable', 'string', 'max:255'],
            'subscription_type' => ['required', 'string', 'max:50'],
            'settings'          => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'Department name is required.',
            'slug.required'       => 'Slug is required.',
            'slug.unique'         => 'Slug must be unique.',
            'subscription_type.required' => 'Subscription type is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->slug && $this->name) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}
