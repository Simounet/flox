<?php

namespace App;

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
    ];

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
            ]
        );
    }
}
