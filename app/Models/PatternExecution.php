<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Tracks pattern execution for analytics
 *
 * @property int $fabric_pattern_id
 * @property string $input_content User input content
 * @property string $output_content Generated output
 * @property array $metadata Execution metadata
 * @property int $tokens_used Estimated tokens consumed
 * @property float $execution_time_ms Execution time in milliseconds
 * @property string $client_info MCP client information
 */
class PatternExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'fabric_pattern_id',
        'input_content',
        'output_content',
        'metadata',
        'tokens_used',
        'execution_time_ms',
        'client_info',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tokens_used' => 'integer',
        'execution_time_ms' => 'float',
    ];

    /**
     * Get the pattern this execution belongs to
     */
    public function pattern()
    {
        return $this->belongsTo(FabricPattern::class, 'fabric_pattern_id');
    }

    /**
     * Scope for recent executions
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope by pattern
     */
    public function scopeForPattern($query, int $patternId)
    {
        return $query->where('fabric_pattern_id', $patternId);
    }
}
