<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CompanyUpdateRequest extends FormRequest
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
        $companyId = $this->route('id');

        return [
            'id' => 'required|integer',
            'name' => 'required|string|max:255',
            'type' => 'string|max:255',
            'settings' => 'string|max:255',
            'address' => 'required|string',
            'phone' => 'required|digits_between:7,12',
            'email' => [
                'required',
                'email',
                'max:255',
            ],
        ];

    }
}
