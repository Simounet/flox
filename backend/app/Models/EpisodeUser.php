<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EpisodeUser extends Pivot
{
    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }

    public function scopeIsSeen(
        Builder $query,
        int $userId,
        int $episodeId
    ): bool
    {
        return $query->where([
            'user_id' => $userId,
            'episode_id' => $episodeId
        ])->count() > 0;
    }
}
