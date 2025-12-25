<?php


namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'firstName' => 'required|string|max:255',
            'lastName' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:leads,email',
            'mobile' => 'required|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'source' => 'nullable|string|in:Facebook,WhatsApp,Website,YouTube,Referral',
            'interest' => 'nullable|in:Low,Medium,High',
            'notes' => 'required|string|min:10',
        ];
    }

    public function messages()
    {
        return [
            'firstName.required' => 'First name is required.',
            'email.unique' => 'A lead with this email already exists.',
            'mobile.required' => 'Mobile number is required.',
            'notes.required' => 'Notes are required.',
            'notes.min' => 'Notes must be at least 10 characters.',
        ];
    }

    public function prepareForValidation()
    {
        // Clean and format phone numbers
        if ($this->mobile) {
            $this->merge([
                'mobile' => $this->formatPhoneNumber($this->mobile)
            ]);
        }

        if ($this->whatsapp) {
            $this->merge([
                'whatsapp' => $this->formatPhoneNumber($this->whatsapp)
            ]);
        }
    }

    private function formatPhoneNumber($phone)
    {
        // Remove spaces and format consistently
        $phone = preg_replace('/\s+/', '', $phone);
        
        // Add +92 prefix if not present for Pakistani numbers
        if (!str_starts_with($phone, '+') && strlen($phone) == 11) {
            $phone = '+92-' . substr($phone, 1, 3) . '-' . substr($phone, 4);
        }
        
        return $phone;
    }
}
