<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic prompt model that can store:
 * - User-created prompts
 * - Fabric patterns
 * - Prompts from other sources
 */
class Prompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title',
        'description',
        'content',
        'category',
        'tags',
        'source_type', // 'manual', 'fabric', 'github', etc.
        'source_identifier', // specific source name (fabric, personal-repo, etc.)
        'source_url',
        'source_hash',
        'checksum',
        'is_active',
        'is_public',
        'created_by', // null for external sources
        'metadata',
        'synced_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'synced_at' => 'datetime',
    ];

    /**
     * Get prompt compositions
     */
    public function compositions()
    {
        return $this->hasMany(Composition::class);
    }

    /**
     * Get prompt renders (alias for backward compatibility)
     */
    public function renders()
    {
        return $this->compositions();
    }

    /**
     * Get prompt executions (alias for backward compatibility)
     */
    public function executions()
    {
        return $this->compositions();
    }

    /**
     * Scope for active prompts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for public prompts
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope by source type
     */
    public function scopeFromSource($query, string $sourceType)
    {
        return $query->where('source_type', $sourceType);
    }

    /**
     * Scope by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Check if prompt is user-created
     */
    protected function isManual(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->source_type === 'manual'
        );
    }

    /**
     * Check if prompt is from external source
     */
    protected function isExternal(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->source_type, ['fabric', 'github'])
        );
    }

    /**
     * Get estimated tokens
     */
    protected function estimatedTokens(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) ceil(strlen($this->content) / 4)
        );
    }
}
