<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'lead_id',
        'form_data',
        'ip_address',
        'user_agent',
        'referrer',
        'status',
    ];

    protected $casts = [
        'form_data' => 'array',
    ];

    // Relationships
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    // Scopes
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    // Methods
    public function convertToLead(): ?Lead
    {
        if ($this->lead_id) {
            return $this->lead;
        }

        $formData = $this->form_data;
        
        $lead = Lead::create([
            'first_name' => $formData['firstName'] ?? $formData['first_name'] ?? 'Unknown',
            'last_name' => $formData['lastName'] ?? $formData['last_name'] ?? '',
            'email' => $formData['email'] ?? null,
            'mobile' => $formData['mobile'] ?? $formData['phone'] ?? '',
            'whatsapp' => $formData['whatsapp'] ?? null,
            'country' => $formData['country'] ?? null,
            'source' => 'Website Form',
            'status' => 'cold',
            'interest' => $formData['interest'] ?? 'Medium',
            'notes' => $formData['notes'] ?? 'Lead created from form submission: ' . $this->form->title,
        ]);

        $this->update([
            'lead_id' => $lead->id,
            'status' => 'converted',
        ]);

        return $lead;
    }
}
