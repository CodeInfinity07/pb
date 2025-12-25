<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'attachments',
        'is_internal_note',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_internal_note' => 'boolean',
    ];

    // Relationships
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_internal_note', false);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal_note', true);
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::created(function ($reply) {
            // Update the ticket's last reply information
            $reply->ticket->updateLastReply($reply->user);
            
            // If it's a user reply and ticket was pending user, mark as open
            if (!$reply->user->hasStaffPrivileges() && $reply->ticket->status === 'pending_user') {
                $reply->ticket->update(['status' => 'open']);
            }
        });
    }
}
