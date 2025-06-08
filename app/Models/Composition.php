<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Tracks composition of prompts with user input
 */
class Composition extends Model
{
    use HasFactory;

    protected $fillable = [
        'prompt_id',
        'input_content',
        'composed_content',
        'metadata',
        'tokens_used',
        'compose_time_ms',
        'client_info',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tokens_used' => 'integer',
        'compose_time_ms' => 'float',
    ];

    /**
     * Get the prompt this composition belongs to
     */
    public function prompt()
    {
        return $this->belongsTo(Prompt::class);
    }

    /**
     * Scope for recent compositions
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope by prompt
     */
    public function scopeForPrompt($query, int $promptId)
    {
        return $query->where('prompt_id', $promptId);
    }
}
