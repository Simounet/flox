<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kra8\Snowflake\HasShortflakePrimary;

class Comment extends Model
{
    use HasFactory;
    use HasShortflakePrimary;

    protected $table = 'comments';

    protected $fillable = [
        'profile_id',
        'review_id',
        'content',
        'source_url',
        'language',
        'sensitive',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function store(int $profileId, int $reviewId, array $commentData): self
    {
        return $this->updateOrCreate(
            ['profile_id' => $profileId, 'review_id' => $reviewId],
            $commentData
        );
    }
}
