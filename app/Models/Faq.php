<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'category',
        'status',
        'sort_order',
        'views',
        'is_featured',
        'tags',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_featured' => 'boolean',
        'views' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the user who created this FAQ
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this FAQ
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope: Get only active FAQs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Get FAQs by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Get featured FAQs
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'desc');
    }

    /**
     * Get the status badge class for UI
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'active' => 'bg-success',
            'inactive' => 'bg-secondary',
            default => 'bg-secondary'
        };
    }

    /**
     * Get the status text for UI
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            default => 'Unknown'
        };
    }

    /**
     * Get the category badge class for UI
     */
    public function getCategoryBadgeAttribute(): string
    {
        return match($this->category) {
            'technical' => 'bg-primary',
            'billing' => 'bg-warning',
            'account' => 'bg-info',
            'security' => 'bg-danger',
            'features' => 'bg-success',
            'general' => 'bg-secondary',
            default => 'bg-light text-dark'
        };
    }

    /**
     * Get the category text for UI
     */
    public function getCategoryTextAttribute(): string
    {
        return match($this->category) {
            'technical' => 'Technical',
            'billing' => 'Billing',
            'account' => 'Account',
            'security' => 'Security',
            'features' => 'Features',
            'general' => 'General',
            default => ucfirst($this->category)
        };
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('views');
    }

    /**
     * Get available categories
     */
    public static function getCategories(): array
    {
        return [
            'general' => 'General',
            'technical' => 'Technical',
            'billing' => 'Billing',
            'account' => 'Account',
            'security' => 'Security',
            'features' => 'Features',
            'investment' => 'Investment',
            'wallet' => 'Wallet',
            'verification' => 'Verification',
            'referral' => 'Referral',
        ];
    }

    /**
     * Search FAQs
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('question', 'LIKE', "%{$search}%")
              ->orWhere('answer', 'LIKE', "%{$search}%")
              ->orWhere('tags', 'LIKE', "%{$search}%");
        });
    }
}