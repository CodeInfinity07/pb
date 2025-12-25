<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreFormRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255|unique:forms,title',
            'description' => 'nullable|string|max:1000',
            'submit_button_text' => 'nullable|string|max:100',
            'success_message' => 'nullable|string|max:255',
            'standard_fields' => 'nullable|array',
            'standard_fields.first_name' => 'nullable|boolean',
            'standard_fields.last_name' => 'nullable|boolean',
            'standard_fields.email' => 'nullable|boolean',
            'standard_fields.mobile' => 'nullable|boolean',
            'standard_fields.country' => 'nullable|boolean',
            'standard_fields.whatsapp' => 'nullable|boolean',
            'custom_fields' => 'nullable|array',
            'custom_fields.*.label' => 'required_with:custom_fields|string|max:255',
            'custom_fields.*.type' => 'required_with:custom_fields|in:text,email,tel,textarea,select',
            'custom_fields.*.required' => 'nullable|boolean',
            'custom_fields.*.options' => 'required_if:custom_fields.*.type,select|array',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Form title is required.',
            'title.unique' => 'A form with this title already exists.',
            'title.max' => 'Form title cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'submit_button_text.max' => 'Submit button text cannot exceed 100 characters.',
            'success_message.max' => 'Success message cannot exceed 255 characters.',
            'custom_fields.*.label.required_with' => 'Custom field label is required.',
            'custom_fields.*.type.required_with' => 'Custom field type is required.',
            'custom_fields.*.type.in' => 'Invalid custom field type.',
            'custom_fields.*.options.required_if' => 'Options are required for dropdown fields.',
        ];
    }

    public function prepareForValidation()
    {
        // Ensure at least one standard field is enabled
        $standardFields = $this->standard_fields ?? [];
        $hasEnabledField = collect($standardFields)->contains(true);
        
        if (!$hasEnabledField) {
            $this->merge([
                'standard_fields' => array_merge($standardFields, ['first_name' => true, 'mobile' => true])
            ]);
        }

        // Set default values if not provided
        $this->merge([
            'submit_button_text' => $this->submit_button_text ?: 'Submit Application',
            'success_message' => $this->success_message ?: 'Thank you! We\'ll contact you soon.',
        ]);
    }
}

