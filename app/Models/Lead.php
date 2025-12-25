<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'mobile',
        'whatsapp',
        'country',
        'source',
        'status',
        'interest',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'status' => 'string',
        'interest' => 'string',
    ];

    protected $appends = [
        'full_name',
        'formatted_mobile',
    ];

    // Relationships
    public function followups(): HasMany
    {
        return $this->hasMany(Followup::class)->orderBy('followup_date', 'desc');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderBy('created_at', 'desc');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getFormattedMobileAttribute(): string
    {
        return $this->mobile;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['hot', 'warm', 'cold']);
    }

    public function scopeHot($query)
    {
        return $query->where('status', 'hot');
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    public function scopeWithTodayFollowups($query)
    {
        return $query->whereHas('followups', function ($q) {
            $q->where('followup_date', today())
              ->where('completed', false);
        });
    }

    // Methods
    public function addFollowup(array $data): Followup
    {
        return $this->followups()->create($data);
    }

    public function assignTo(User $user, ?string $notes = null): Assignment
    {
        return $this->assignments()->create([
            'assigned_by' => auth()->id(),
            'assigned_to' => $user->id,
            'assigned_at' => now(),
            'notes' => $notes,
        ]);
    }

    public function logActivity(string $type, string $description, array $oldValues = [], array $newValues = []): void
    {
        $this->activities()->create([
            'user_id' => auth()->id(),
            'activity_type' => $type,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
