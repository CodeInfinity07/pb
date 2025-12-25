<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class StoreFollowupRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'lead_id' => 'required|exists:leads,id',
            'followup_date' => 'required|date|after_or_equal:today',
            'type' => 'required|in:call,email,meeting,whatsapp,other',
            'notes' => 'required|string|min:10|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'lead_id.required' => 'Lead is required.',
            'lead_id.exists' => 'Selected lead does not exist.',
            'followup_date.required' => 'Follow-up date is required.',
            'followup_date.after_or_equal' => 'Follow-up date cannot be in the past.',
            'type.required' => 'Follow-up type is required.',
            'type.in' => 'Invalid follow-up type selected.',
            'notes.required' => 'Notes are required.',
            'notes.min' => 'Notes must be at least 10 characters.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }

    public function prepareForValidation()
    {
        // Ensure the followup_date is properly formatted
        if ($this->followup_date) {
            try {
                $date = Carbon::parse($this->followup_date)->format('Y-m-d');
                $this->merge(['followup_date' => $date]);
            } catch (\Exception $e) {
                // Let validation handle the invalid date
            }
        }
    }
}

