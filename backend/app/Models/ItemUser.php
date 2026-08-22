<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemUser extends Model
{
    use HasFactory;

    protected $table = 'item_user';

    protected $fillable = [
        'user_id',
        'item_id',
        'rating',
        'watchlist',
    ];

    protected $casts = [
      'watchlist' => 'boolean'
    ];

    protected $with = ['review'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function review(): HasMany
    {
        return $this->hasMany(Review::class, 'item_user_id', 'item_id');
    }

    public function store(int $userId, int $itemId, array $reviewData): self
    {
        return $this->updateOrCreate(
            ['user_id' => $userId, 'item_id' => $itemId],
            $reviewData
        );
    }
}
