<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'lead_id' => [
                'required',
                'exists:leads,id',
                // Ensure lead is not already assigned to the same user
                Rule::unique('assignments')->where(function ($query) {
                    return $query->where('lead_id', $this->lead_id)
                                 ->where('assigned_to', $this->assigned_to)
                                 ->where('status', 'active');
                })
            ],
            'assigned_to' => [
                'required',
                'exists:users,id',
                'different:' . auth()->id() // Cannot assign to yourself
            ],
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'lead_id.required' => 'Lead is required.',
            'lead_id.exists' => 'Selected lead does not exist.',
            'lead_id.unique' => 'This lead is already assigned to the selected user.',
            'assigned_to.required' => 'Assignee is required.',
            'assigned_to.exists' => 'Selected user does not exist.',
            'assigned_to.different' => 'You cannot assign a lead to yourself.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }
}
