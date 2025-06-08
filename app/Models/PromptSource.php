<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromptSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'repository_url',
        'branch',
        'path_pattern',
        'file_pattern',
        'is_active',
        'auto_sync',
        'last_synced_at',
        'sync_status',
        'sync_error',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_sync' => 'boolean',
        'last_synced_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get prompts belonging to this source
     */
    public function prompts(): HasMany
    {
        return $this->hasMany(Prompt::class, 'source_identifier', 'name');
    }

    /**
     * Mark sync as started
     */
    public function markSyncStarted(): void
    {
        $this->update([
            'sync_status' => 'syncing',
            'sync_error' => null,
        ]);
    }

    /**
     * Mark sync as completed
     */
    public function markSyncCompleted(): void
    {
        $this->update([
            'sync_status' => 'completed',
            'last_synced_at' => now(),
            'sync_error' => null,
        ]);
    }

    /**
     * Mark sync as failed
     */
    public function markSyncFailed(string $error): void
    {
        $this->update([
            'sync_status' => 'failed',
            'sync_error' => $error,
        ]);
    }

    /**
     * Scope for active sources
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for auto-sync sources
     */
    public function scopeAutoSync($query)
    {
        return $query->where('auto_sync', true);
    }

    /**
     * Get display name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }
}
