<?php

  namespace App\Http\Controllers;

  use App\Services\Models\AlternativeTitleService;
  use App\Services\Models\EpisodeService;
  use App\Services\Models\EpisodeUserService;
  use App\Services\Models\ItemService;
  use App\Services\Models\ItemUserService;
  use Illuminate\Support\Facades\Auth;
  use Illuminate\Support\Facades\Request;
  use Symfony\Component\HttpFoundation\Response;

  class ItemController {

    private $itemService;
    private $itemUserService;
    private $episodeService;
    private $episodeUserService;

    public function __construct(
      ItemService $itemService,
      ItemUserService $itemUserService,
      EpisodeService $episodeService,
      EpisodeUserService $episodeUserService,
    )
    {
      $this->itemService = $itemService;
      $this->itemUserService = $itemUserService;
      $this->episodeService = $episodeService;
      $this->episodeUserService = $episodeUserService;
    }

    public function items($type, $orderBy, $sortDirection)
    {
      return $this->itemService->getWithPagination($type, $orderBy, $sortDirection);
    }

    public function episodes($tmdbId)
    {
      return $this->episodeService->getAllByTmdbId(Auth::id(), $tmdbId);
    }

    public function search()
    {
      return $this->itemService->search(Request::input('q'));
    }

    public function add()
    {
      $user = Auth::user();
      abort_if(!$user, 403);

      $tmdbId = Request::input('tmdb_id');
      $mediaTypeStr = Request::input('media_type');
      abort_if(!$tmdbId || !$mediaTypeStr, 403);

      return $this->itemService->create($tmdbId, $mediaTypeStr, $user->id);
    }

    public function delete(int $itemId)
    {
        if(!Auth::check()) {
          return response('Bad request.', Response::HTTP_BAD_REQUEST);
        }

        $userId = Auth::id();
        if(!$this->itemUserService->remove($itemId, $userId)) {
          return response('Bad request.', Response::HTTP_BAD_REQUEST);
        }

        return response('Success', Response::HTTP_OK);
    }

    public function watchlist()
    {
      $item = $this->add();

      $item->itemUser->update(['watchlist' => true]);

      return $item;
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
      $seen = Request::input('seen');
      if(is_null($seen)) {
        return response('Bad request: missing seen state', Response::HTTP_BAD_REQUEST);
      }

      if( ! $this->episodeUserService->toggleSeen($id, $seen)) {
        return response('Server Error', Response::HTTP_INTERNAL_SERVER_ERROR);
      }

      return response('Success', Response::HTTP_OK);
    }

    public function toggleSeason()
    {
      $tmdbId = Request::input('tmdb_id');
      $season = Request::input('season');
      $seen = Request::input('seen');

      $this->episodeUserService->toggleSeason($tmdbId, $season, $seen);
    }

    public function changeRating(int $itemUserId): Response
    {
      $user = Auth::user();
      abort_if(!$user, 403);

      return $this->itemUserService->changeRating(
        $itemUserId,
        Request::input('rating'),
        $user->id
      );
    }
  }
