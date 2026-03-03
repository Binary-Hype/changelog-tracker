<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'owner',
        'repo',
        'description',
        'is_active',
        'check_interval_minutes',
        'last_checked_at',
        'include_prereleases',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'include_prereleases' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }

    public function slackChannels(): BelongsToMany
    {
        return $this->belongsToMany(SlackChannel::class);
    }

    public function getGithubUrlAttribute(): string
    {
        return "https://github.com/{$this->owner}/{$this->repo}";
    }

    public function isDueForCheck(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (! $this->last_checked_at) {
            return true;
        }

        return $this->last_checked_at->addMinutes($this->check_interval_minutes)->isPast();
    }
}
