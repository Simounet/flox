<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kra8\Snowflake\HasShortflakePrimary;

class Review extends Model
{
    use HasShortflakePrimary;

    protected $table = 'reviews';

    protected $fillable = [
        'user_id',
        'item_id',
        'content',
        'rating',
        'watchlist',
    ];

    protected $casts = [
      'watchlist' => 'boolean'
    ];

    protected $with = ['user'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create the new person.
     *
     * @return Review
     */
    public function store(int $userId, int $itemId, string $content)
    {
        return $this->updateOrCreate(
            ['user_id' => $userId, 'item_id' => $itemId],
            [
                'content' => $content,
                'rating' => 0,
            ]
        );
    }
}
