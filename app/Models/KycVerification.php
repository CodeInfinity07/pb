<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class KycVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'attempt_id',
        'status',
        'decision',
        'decision_score',
        'verified_first_name',
        'verified_last_name',
        'verified_date_of_birth',
        'verified_gender',
        'verified_id_number',
        'document_type',
        'document_country',
        'document_number',
        'document_valid_until',
        'document_verified',
        'face_verified',
        'liveness_check',
        'verified_at',
        'rejection_reason',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'verified_date_of_birth' => 'date',
            'document_valid_until' => 'date',
            'verified_at' => 'datetime',
            'decision_score' => 'decimal:2',
            'document_verified' => 'boolean',
            'face_verified' => 'boolean',
            'liveness_check' => 'boolean',
            'raw_data' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns the KYC verification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the full verified name.
     */
    public function getVerifiedFullNameAttribute(): string
    {
        return trim($this->verified_first_name . ' ' . $this->verified_last_name);
    }

    /**
     * Get the document type display name.
     */
    public function getDocumentTypeDisplayAttribute(): string
    {
        $types = [
            'id_card' => 'ID Card',
            'passport' => 'Passport',
            'driving_license' => 'Driving License',
        ];

        return $types[$this->document_type] ?? ucfirst(str_replace('_', ' ', $this->document_type ?? ''));
    }

    /**
     * Get verification status badge class for UI.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'success' => match($this->decision) {
                'approved' => 'bg-success',
                'declined' => 'bg-danger',
                'resubmission_requested' => 'bg-warning',
                default => 'bg-info'
            },
            'failed' => 'bg-danger',
            'pending' => 'bg-warning',
            default => 'bg-secondary'
        };
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if verification is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'success' && $this->decision === 'approved';
    }

    /**
     * Check if verification is declined.
     */
    public function isDeclined(): bool
    {
        return $this->status === 'success' && $this->decision === 'declined';
    }

    /**
     * Check if verification is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if all core checks passed.
     */
    public function allChecksPassed(): bool
    {
        return $this->document_verified && $this->face_verified && $this->liveness_check;
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Create verification record from Veriff webhook data.
     */
    public static function createFromWebhook(array $webhookData, int $userId): self
    {
        $verification = new self();
        
        // Basic info
        $verification->user_id = $userId;
        $verification->session_id = $webhookData['sessionId'] ?? null;
        $verification->attempt_id = $webhookData['attemptId'] ?? null;
        $verification->status = $webhookData['status'] ?? 'pending';
        
        // Verification time
        if (isset($webhookData['time'])) {
            $verification->verified_at = Carbon::parse($webhookData['time']);
        }
        
        // Process verification data
        if (isset($webhookData['data']['verification'])) {
            $data = $webhookData['data']['verification'];
            
            $verification->decision = $data['decision'] ?? null;
            $verification->decision_score = $data['decisionScore'] ?? null;
            
            // Person data
            if (isset($data['person'])) {
                $person = $data['person'];
                $verification->verified_first_name = $person['firstName']['value'] ?? null;
                $verification->verified_last_name = $person['lastName']['value'] ?? null;
                $verification->verified_id_number = $person['idNumber']['value'] ?? null;
                
                if (isset($person['dateOfBirth']['value'])) {
                    $verification->verified_date_of_birth = Carbon::parse($person['dateOfBirth']['value']);
                }
                
                $verification->verified_gender = $person['gender']['value'] ?? null;
            }
            
            // Document data
            if (isset($data['document'])) {
                $document = $data['document'];
                $verification->document_type = $document['type']['value'] ?? null;
                $verification->document_country = $document['country']['value'] ?? null;
                $verification->document_number = $document['number']['value'] ?? null;
                
                if (isset($document['validUntil']['value'])) {
                    $verification->document_valid_until = Carbon::parse($document['validUntil']['value']);
                }
            }
            
            // Process key insights
            if (isset($data['insights'])) {
                $verification->document_verified = self::checkInsight($data['insights'], ['documentAccepted', 'documentRecognised']);
                $verification->face_verified = self::checkInsight($data['insights'], ['faceSimilarToPortrait']);
                $verification->liveness_check = self::checkInsight($data['insights'], ['faceLiveness']);
            }
        }
        
        // Store complete webhook data
        $verification->raw_data = $webhookData;
        
        $verification->save();
        
        return $verification;
    }

    /**
     * Helper method to check if specific insights passed.
     */
    private static function checkInsight(array $insights, array $checkLabels): bool
    {
        foreach ($insights as $insight) {
            if (in_array($insight['label'], $checkLabels)) {
                if ($insight['result'] !== 'yes') {
                    return false;
                }
            }
        }
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for approved verifications.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'success')->where('decision', 'approved');
    }

    /**
     * Scope for pending verifications.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for declined verifications.
     */
    public function scopeDeclined($query)
    {
        return $query->where('decision', 'declined');
    }
}