<?php

declare(strict_types=1);

namespace App\Models;

use App\ValueObjects\EpisodeUserValueObject;
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
        EpisodeUserValueObject $episodeUserValueObject
    ): bool
    {
        return $query->where($episodeUserValueObject->get())->count() > 0;
    }
}
