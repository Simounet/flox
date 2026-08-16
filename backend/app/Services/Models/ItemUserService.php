<?php

namespace App\Services\Models;

use App\Jobs\ReviewSendActivities;
use App\Models\Item;
use App\Models\ItemUser;
use App\Services\Fediverse\Activity\Verbs;
use App\ValueObjects\RatingValueObject;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Response as IlluminateHttpResponse;

class ItemUserService {

    public function getRating(int $rating): string
    {
        return new RatingValueObject($rating);
    }

    public function changeRating(
        int $itemUserId,
        int $rating,
        int $currentUserId
    ): IlluminateHttpResponse
    {
      $itemUser = ItemUser::where([
        'user_id' => $currentUserId,
        'id' => $itemUserId
      ])->first();

      if(! $itemUser) {
        return response('Not Found', Response::HTTP_NOT_FOUND);
      }

      // Update the parent relation only if we change rating from neutral.
      if($itemUser->rating === 0) {
          $updatedItemUser = $itemUser->update([
            'rating' => $rating,
            'watchlist' => false,
          ]);
      } else {
          $updatedItemUser = ItemUser::withoutTimestamps(function () use($itemUser, $rating) {
              return $itemUser->update([
                'rating' => $rating,
                'watchlist' => false,
              ]);
          });
      }

      if($updatedItemUser === false) {
          return response('ItemUser not updated: ' . $itemUserId, Response::HTTP_INTERNAL_SERVER_ERROR);
      }

      return response('', Response::HTTP_OK);
    }

    public function create(
        Item $item,
        int $userId
    ): ItemUser
    {
        $itemUserModel = new ItemUser();
        $storedItemUser = $itemUserModel->store(
            $userId,
            $item->id,
            [
                'content' => '',
                'rating' => 0
            ],
        );
        return $storedItemUser;
    }

    public function remove(int $itemId, int $userId): bool
    {
        $itemUser = ItemUser::where([
            'item_id' => $itemId,
            'user_id' => $userId
        ])->with('user:id,username')->firstOrFail();
        // @TODO delete associated content (episodeUser)
        $review = $itemUser->review;
        $username = $itemUser->user->username;
        $itemUser->delete();

        if($review->count() > 0 && $review->content !== '') {
            ReviewSendActivities::dispatch(
                Verbs::DELETE,
                $review->id,
                $username
            );
        }
        return true;
    }

    public function updateLastActivityAt(int $tmdbId): int
    {
      $itemId = Item::select('id')->where('tmdb_id', $tmdbId)->first()->id;
      return ItemUser::where('item_id', $itemId)->touch();
    }
}
