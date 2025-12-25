<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Form extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'submit_button_text',
        'success_message',
        'standard_fields',
        'custom_fields',
        'is_active',
        'submissions_count',
        'slug',
        'created_by',
    ];

    protected $casts = [
        'standard_fields' => 'array',
        'custom_fields' => 'array',
        'is_active' => 'boolean',
        'submissions_count' => 'integer',
    ];

    // Relationships
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class)->orderBy('created_at', 'desc');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    // Methods
    public function generateSlug(): string
    {
        $slug = Str::slug($this->title);
        $count = static::where('slug', 'like', $slug.'%')->count();
        
        return $count > 0 ? $slug.'-'.($count + 1) : $slug;
    }

    public function getPublicUrlAttribute(): string
    {
        return url("/forms/{$this->slug}");
    }

    public function incrementSubmissions(): void
    {
        $this->increment('submissions_count');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($form) {
            if (empty($form->slug)) {
                $form->slug = $form->generateSlug();
            }
        });
    }
}

