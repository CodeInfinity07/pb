<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Followup extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'followup_date',
        'type',
        'notes',
        'completed',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'followup_date' => 'date',
        'completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('completed', false);
    }

    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    public function scopeDueToday($query)
    {
        return $query->where('followup_date', today());
    }

    public function scopeOverdue($query)
    {
        return $query->where('followup_date', '<', today())
                    ->where('completed', false);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('followup_date', '>', today())
                    ->where('completed', false);
    }

    // Methods
    public function markAsCompleted(): bool
    {
        return $this->update([
            'completed' => true,
            'completed_at' => now(),
        ]);
    }

    public function getTypeIconAttribute(): string
    {
        $icons = [
            'call' => '📞',
            'email' => '📧',
            'meeting' => '🤝',
            'whatsapp' => '💬',
            'other' => '📝'
        ];

        return $icons[$this->type] ?? '📝';
    }
}
