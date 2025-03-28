<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kra8\Snowflake\HasShortflakePrimary;

class Review extends Model
{
    use HasFactory;
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
    public function store(int $userId, int $itemId, array $reviewData)
    {
        return $this->updateOrCreate(
            ['user_id' => $userId, 'item_id' => $itemId],
            $reviewData
        );
    }

    public function updateLastActivityAt(int $tmdbId): int
    {
      // @TODO Episode Model should have a item_id column to avoid this Item pivot query
      $itemId = DB::table('items')->select('id')->where('tmdb_id', $tmdbId)->first()->id;
      return $this->where('item_id', $itemId)->touch();
    }
}
