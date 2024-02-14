<?php

  namespace App\Http\Controllers;

  use App\Models\Review;
  use App\Services\Models\AlternativeTitleService;
  use App\Services\Models\EpisodeService;
  use App\Services\Models\ItemService;
  use App\Services\Models\ReviewService;
  use Illuminate\Support\Facades\Auth;
  use Illuminate\Support\Facades\Request;
  use Symfony\Component\HttpFoundation\Response;

  class ItemController {

    private $itemService;
    private $episodeService;
    private $reviewService;

    public function __construct(ItemService $itemService, EpisodeService $episodeService, ReviewService $reviewService)
    {
      $this->itemService = $itemService;
      $this->episodeService = $episodeService;
      // @TODO to remove after rating switched
      $this->reviewService = $reviewService;
    }

    public function items($type, $orderBy, $sortDirection)
    {
      return $this->itemService->getWithPagination($type, $orderBy, $sortDirection);
    }

    public function episodes($tmdbId)
    {
      return $this->episodeService->getAllByTmdbId($tmdbId);
    }

    public function search()
    {
      return $this->itemService->search(Request::input('q'));
    }

    // @TODO to remove after rating switched
    public function changeRating(int $itemId): Response
    {
      $user = Auth::user();
      abort_if(!$user, 403);

      $this->itemService->changeRating($itemId, Request::input('rating'));
      $review = Review::select('id', 'rating')
        ->where('item_id', $itemId)
        ->where('user_id', $user->id)
        ->get()
        ->first();
      return $this->reviewService->changeRating($review->id, Request::input('rating'));
    }

    public function add()
    {
      $user = Auth::user();
      abort_if(!$user, 403);

      return $this->itemService->create(Request::input('item'), $user->id);
    }

    public function watchlist()
    {
      $item = $this->add();

      $item->update(['watchlist' => true]);

      return $item;
    }

    public function remove($itemId)
    {
      return $this->itemService->remove($itemId);
    }

    public function refresh($itemId)
    {
      $this->itemService->refresh($itemId);

      return response([], Response::HTTP_OK);
    }

    public function refreshAll()
    {
      if (isDemo()) {
        return response('Success', Response::HTTP_OK);
      }

      $this->itemService->refreshAll();

      return response([], Response::HTTP_OK);
    }

    public function updateAlternativeTitles(AlternativeTitleService $alternativeTitle)
    {
      $alternativeTitle->update();
    }

    public function toggleEpisode($id)
    {
      if( ! $this->episodeService->toggleSeen($id)) {
        return response('Server Error', Response::HTTP_INTERNAL_SERVER_ERROR);
      }

      return response('Success', Response::HTTP_OK);
    }

    public function toggleSeason()
    {
      $tmdbId = Request::input('tmdb_id');
      $season = Request::input('season');
      $seen = Request::input('seen');

      $this->episodeService->toggleSeason($tmdbId, $season, $seen);
    }
  }
