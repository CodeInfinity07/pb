<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        $leadId = $this->route('lead') ? $this->route('lead')->id : $this->route()->parameter('id');
        
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('leads')->ignore($leadId)
            ],
            'mobile' => 'required|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'source' => 'nullable|string|in:Facebook,WhatsApp,Website,YouTube,Referral',
            'status' => 'required|in:hot,warm,cold,converted',
            'interest' => 'nullable|in:Low,Medium,High',
            'notes' => 'required|string|min:10',
        ];
    }

    public function messages()
    {
        return [
            'first_name.required' => 'First name is required.',
            'email.unique' => 'A lead with this email already exists.',
            'mobile.required' => 'Mobile number is required.',
            'status.required' => 'Status is required.',
            'status.in' => 'Invalid status selected.',
            'notes.required' => 'Notes are required.',
            'notes.min' => 'Notes must be at least 10 characters.',
        ];
    }
}
