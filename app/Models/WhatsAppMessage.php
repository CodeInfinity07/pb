<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages';
    
    public $timestamps = false; // We're using custom created_at

    protected $fillable = [
        'message_id',
        'user_phone',
        'message_text',
        'message_type',
        'timestamp',
        'webhook_data',
        'created_at'
    ];

    protected $casts = [
        'webhook_data' => 'array',
        'timestamp' => 'integer',
        'created_at' => 'datetime'
    ];

    /**
     * Scope for incoming messages
     */
    public function scopeIncoming($query)
    {
        return $query->where('message_type', 'incoming');
    }

    /**
     * Scope for outgoing messages
     */
    public function scopeOutgoing($query)
    {
        return $query->where('message_type', 'outgoing');
    }

    /**
     * Scope for recent messages (within last 10 minutes)
     */
    public function scopeRecent($query, $minutes = 10)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Check if message contains verification code
     */
    public function containsVerificationCode($code)
    {
        return str_contains(strtolower($this->message_text), strtolower($code));
    }

    /**
     * Extract verification code from message text
     */
    public function extractVerificationCode()
    {
        // Look for 6-digit numbers in the message
        preg_match('/\b\d{6}\b/', $this->message_text, $matches);
        return $matches[0] ?? null;
    }
}