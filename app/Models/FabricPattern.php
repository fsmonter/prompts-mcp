<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents a Fabric AI pattern/prompt
 *
 * @property string $name Pattern name/slug
 * @property string $title Human-readable title
 * @property string $description Pattern description
 * @property string $content Pattern content/prompt
 * @property array $metadata Pattern metadata (author, tags, etc.)
 * @property string $category Pattern category
 * @property bool $is_active Whether pattern is active
 * @property string $source_url Original pattern URL
 * @property string $source_hash Content hash for change detection
 * @property \Carbon\Carbon $synced_at Last sync timestamp
 */
class FabricPattern extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title',
        'description',
        'content',
        'metadata',
        'category',
        'is_active',
        'source_url',
        'source_hash',
        'synced_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'synced_at' => 'datetime',
    ];

    /**
     * Get the pattern's execution count
     */
    public function executions()
    {
        return $this->hasMany(PatternExecution::class);
    }

    /**
     * Scope for active patterns only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for patterns by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get the pattern's MCP tool name
     */
    protected function mcpToolName(): Attribute
    {
        return Attribute::make(
            get: fn () => "fabric_pattern_{$this->name}"
        );
    }

    /**
     * Get the pattern's tags from metadata
     */
    protected function tags(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->metadata['tags'] ?? []
        );
    }

    /**
     * Get estimated tokens for the pattern
     */
    protected function estimatedTokens(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) ceil(strlen($this->content) / 4) // Rough estimation
        );
    }
}
