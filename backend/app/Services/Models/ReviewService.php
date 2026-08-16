<?php

namespace App\Services\Models;

use App\Models\Item;
use App\Models\Review;
use App\ValueObjects\RatingValueObject;

class ReviewService {
    public function getRating(int $rating): string
    {
        return new RatingValueObject($rating);
    }

    public function changeRating(
        int $reviewId,
        int $rating
    ): void
    {
      // @TODO
    }

    public function create(
        Item $item,
        int $userId
    ): Review
    {
        $reviewModel = new Review();
        $storedReview = $reviewModel->store(
            $userId,
            $item->id,
            [
                'content' => '',
                'rating' => 0
            ],
        );
        return $storedReview;
    }

    public function count(
        int $itemId,
        int $userId
    ): int
    {
        return Review::where([
            'user_id' => $userId,
            'item_id' => $itemId
        ])->count();
    }
}
