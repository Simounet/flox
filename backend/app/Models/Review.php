<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Kra8\Snowflake\HasShortflakePrimary;

class Review extends Model
{
    use HasFactory;
    use HasShortflakePrimary;

    protected $table = 'reviews';

    protected $fillable = [
        'item_user_id',
        'rating',
        'content'
    ];

    protected $with = ['user:users.id,users.username'];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function itemUser(): BelongsTo
    {
        return $this->belongsTo(ItemUser::class, 'item_user_id');
    }

    public function user()
    {
        return $this->hasOneThrough(
            User::class,
            ItemUser::class,
            'id', // item_user primary key
            'id', // user primary key
            'item_user_id',
            'user_id'
        )->select([
            'users.id as user_id',
            'users.username',
            'item_user.id as item_user_id',
        ]);
    }

    public function item()
    {
        return $this->hasOneThrough(
            Item::class,
            ItemUser::class,
            'item_id',
            'id',
            'item_user_id',
            'item_id'
        );
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
