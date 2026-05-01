<?php

namespace App\Models;

use Database\Factories\SlackChannelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SlackChannel extends Model
{
    /** @use HasFactory<SlackChannelFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'webhook_url',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'webhook_url' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }
}
