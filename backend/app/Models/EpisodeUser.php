<?php

declare(strict_types=1);

namespace App\Models;

use App\ValueObjects\EpisodeUserValueObject;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Table(key: 'episode_id', keyType: 'integer', incrementing: false)]
class EpisodeUser extends Pivot
{
    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    public function scopeIsSeen(
        Builder $query,
        EpisodeUserValueObject $episodeUserValueObject
    ): bool
    {
        return $query->where($episodeUserValueObject->get())->count() > 0;
    }
}
