<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Release extends Model
{
    /** @use HasFactory<\Database\Factories\ReleaseFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'github_id',
        'tag_name',
        'name',
        'body',
        'html_url',
        'published_at',
        'notified_at',
        'notification_attempts',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'github_id' => 'integer',
            'published_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isNotified(): bool
    {
        return $this->notified_at !== null;
    }

    public function isPendingNotification(): bool
    {
        return ! $this->isNotified() && $this->notification_attempts === 0;
    }

    public function isRetryable(): bool
    {
        $maxAttempts = config('changelog-tracker.notifications.max_attempts');
        $retryWindow = config('changelog-tracker.notifications.retry_window_hours');

        return ! $this->isNotified()
            && $this->notification_attempts < $maxAttempts
            && $this->created_at->greaterThan(now()->subHours($retryWindow));
    }
}
