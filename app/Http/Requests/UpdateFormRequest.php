<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFormRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        $formId = $this->route('form') ? $this->route('form')->id : $this->route()->parameter('id');
        
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('forms')->ignore($formId)
            ],
            'description' => 'nullable|string|max:1000',
            'submit_button_text' => 'nullable|string|max:100',
            'success_message' => 'nullable|string|max:255',
            'standard_fields' => 'nullable|array',
            'custom_fields' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Form title is required.',
            'title.unique' => 'A form with this title already exists.',
            'title.max' => 'Form title cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
        ];
    }
}