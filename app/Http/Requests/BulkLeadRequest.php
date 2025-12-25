<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkLeadRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'lead_ids' => 'required|array|min:1|max:100',
            'lead_ids.*' => 'exists:leads,id',
            'action' => 'required|in:update_status,assign,delete',
            'status' => 'required_if:action,update_status|in:hot,warm,cold,converted',
            'assigned_to' => 'required_if:action,assign|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'lead_ids.required' => 'Please select at least one lead.',
            'lead_ids.min' => 'Please select at least one lead.',
            'lead_ids.max' => 'Cannot process more than 100 leads at once.',
            'lead_ids.*.exists' => 'One or more selected leads do not exist.',
            'action.required' => 'Action is required.',
            'action.in' => 'Invalid action selected.',
            'status.required_if' => 'Status is required for status update action.',
            'assigned_to.required_if' => 'Assignee is required for assignment action.',
            'assigned_to.exists' => 'Selected assignee does not exist.',
        ];
    }
}
