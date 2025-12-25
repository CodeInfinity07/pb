<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'subject',
        'body',
        'variables',
        'is_active',
        'is_system',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user who created the template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the template
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for system templates
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get template by slug
     */
    public static function getBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)->where('is_active', true)->first();
    }

    /**
     * Render template with data
     */
    public function render(array $data): array
    {
        $subject = $this->replaceVariables($this->subject, $data);
        $body = $this->replaceVariables($this->body, $data);

        return [
            'subject' => $subject,
            'body' => $body
        ];
    }

    /**
     * Replace variables in text
     */
    private function replaceVariables(string $text, array $data): string
    {
        // Add default data
        $defaultData = [
            'platform_name' => config('app.name'),
            'login_url' => route('login'),
            'dashboard_url' => route('dashboard'),
            'support_url' => route('support.index'),
            'date' => now()->format('F j, Y'),
            'time' => now()->format('g:i A')
        ];
 
        $data = array_merge($defaultData, $data);

        foreach ($data as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }

    /**
     * Get available categories
     */
    public static function getCategories(): array
    {
        return [
            'transaction' => 'Transactions',
            'investment' => 'Investments',
            'kyc' => 'KYC Verification',
            'referral' => 'Referrals',
            'support' => 'Support',
            'account' => 'Account',
            'system' => 'System'
        ];
    }

    /**
     * Get category badge class
     */
    public function getCategoryBadgeAttribute(): string
    {
        return match($this->category) {
            'transaction' => 'bg-primary',
            'investment' => 'bg-success',
            'kyc' => 'bg-warning',
            'referral' => 'bg-info',
            'support' => 'bg-secondary',
            'account' => 'bg-danger',
            'system' => 'bg-dark',
            default => 'bg-secondary'
        };
    }

    /**
     * Get formatted category name
     */
    public function getFormattedCategoryAttribute(): string
    {
        return self::getCategories()[$this->category] ?? ucfirst($this->category);
    }
}
