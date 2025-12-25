<?php
// app/Models/SupportTicket.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'assigned_to',
        'subject',
        'description',
        'status',
        'priority',
        'category',
        'attachments',
        'last_reply_at',
        'last_reply_by',
    ];

    protected $casts = [
        'attachments' => 'array',
        'last_reply_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = static::generateTicketNumber();
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function lastReplyBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_reply_by');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class, 'ticket_id')->orderBy('created_at');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    // Accessors & Mutators
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'open' => 'bg-primary',
            'in_progress' => 'bg-warning',
            'pending_user' => 'bg-info',
            'resolved' => 'bg-success',
            'closed' => 'bg-secondary',
            default => 'bg-primary'
        };
    }

    public function getPriorityBadgeAttribute(): string
    {
        return match($this->priority) {
            'low' => 'bg-secondary',
            'medium' => 'bg-primary',
            'high' => 'bg-warning',
            'urgent' => 'bg-danger',
            default => 'bg-primary'
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'pending_user' => 'Pending User',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            default => 'Unknown'
        };
    }

    public function getPriorityTextAttribute(): string
    {
        return match($this->priority) {
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
            default => 'Unknown'
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === 'closed' || $this->status === 'resolved') {
            return false;
        }

        $hours = match($this->priority) {
            'urgent' => 2,
            'high' => 8,
            'medium' => 24,
            'low' => 72,
            default => 24
        };

        return $this->created_at->addHours($hours)->isPast();
    }

    // Methods
    public static function generateTicketNumber(): string
    {
        do {
            $number = 'TKT-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while (static::where('ticket_number', $number)->exists());

        return $number;
    }

    public function updateLastReply(User $user): void
    {
        $this->update([
            'last_reply_at' => now(),
            'last_reply_by' => $user->id,
        ]);
    }

    public function markAsResolved(): void
    {
        $this->update(['status' => 'resolved']);
    }

    public function markAsClosed(): void
    {
        $this->update(['status' => 'closed']);
    }

    public function assignTo(User $user): void
    {
        $this->update(['assigned_to' => $user->id]);
    }

    public function unassign(): void
    {
        $this->update(['assigned_to' => null]);
    }

    public static function getStatuses(): array
    {
        return [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'pending_user' => 'Pending User',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ];
    }

    public static function getPriorities(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];
    }

    public static function getCategories(): array
    {
        return [
            'technical' => 'Technical Support',
            'billing' => 'Billing & Payments',
            'account' => 'Account Issues',
            'feature' => 'Feature Request',
            'bug' => 'Bug Report',
            'general' => 'General Inquiry',
            'other' => 'Other',
        ];
    }
}