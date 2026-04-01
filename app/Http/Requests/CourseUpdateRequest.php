<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourseUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update courses');
    }

    public function rules(): array
    {
        return [
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:255'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'price' => [
                'nullable',
                'numeric',
                'between:0,999999.99'
            ],

            'settings' => [
                'nullable',
            ],

            'course_category_id' => [
                'nullable',
                'integer',
                'exists:course_categories,id',
            ],

            'status' => [
                'sometimes',
                'required',
                'string',
                'in:active,inactive,admin',
            ],

            'course_type' => [
                'sometimes',
                'required',
                'string',
                'in:online,in-person',
            ],

            'duration' => [
                'nullable',
                'integer',
                'min:0'
            ],

            'delivery_type' => [
                'sometimes',
                'required',
                'string',
                'in:self_paced,scheduled',
            ],

            'default_capacity' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'booking_required' => [
                'nullable',
                'boolean',
            ],

            'provider_ids' => [
                'nullable',
                'array'
            ],

            'provider_ids.*' => [
                'integer',
                'exists:providers,id'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'     => 'Course title cannot be empty.',

            'price.numeric'      => 'Price must be a valid number.',
            'price.between'      => 'Price must be between 0 and 999,999.99.',

            'course_category_id.exists' => 'The selected category is invalid.',

            'status.required'    => 'Status is required.',
            'status.in'          => 'Status must be active, inactive or admin.',

            'course_type.required' => 'Course type is required.',
            'course_type.in'       => 'Course type must be online or in-person.',

            'duration.integer'   => 'Duration must be a whole number of minutes.',
            'duration.min'       => 'Duration cannot be negative.',

            'provider_ids.*.exists' => 'The selected provider is invalid.',

        ];
    }

    protected function prepareForValidation()
    {
        return;
    }
}
