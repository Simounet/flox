<?php

namespace App\Services\Models;

use App\Models\Item;
use App\Models\Review;
use Symfony\Component\HttpFoundation\Response;

class ReviewService {

    public function getRating(int $rating): string
    {
        switch($rating) {
            case 1:
                return '👍';
            case 2:
                return '🤔';
            case 3:
                return '👎';
            default:
                return 'not rated';
        }
    }

    public function changeRating(
        int $reviewId,
        int $rating,
        int $userId
    ): Response
    {
      $review = Review::where([
        'user_id' => $userId,
        'id' => $reviewId
      ])->first();

      if(! $review) {
        return response('Not Found', Response::HTTP_NOT_FOUND);
      }

      // Update the parent relation only if we change rating from neutral.
      if($review->rating === 0) {
          $updatedReview = $review->update([
            'rating' => $rating,
            'watchlist' => false,
          ]);
      } else {
          $updatedReview = Review::withoutTimestamps(function () use($review, $rating) {
              return $review->update([
                'rating' => $rating,
                'watchlist' => false,
              ]);
          });
      }

      if($updatedReview === false) {
          return response('Review not updated: ' . $reviewId, Response::HTTP_INTERNAL_SERVER_ERROR);
      }

      return response('', Response::HTTP_OK);
    }

    public function create(
        Item $item,
        int $userId
    ): Review
    {
        $reviewModel = new Review();
        $storedReview = $reviewModel->store($userId, $item->id, '');
        return $storedReview;
    }
}
