<?php

  namespace App\Http\Controllers;

  use App\Services\Models\AlternativeTitleService;
  use App\Services\Models\EpisodeService;
  use App\Services\Models\EpisodeUserService;
  use App\Services\Models\ItemService;
  use Illuminate\Support\Facades\Auth;
  use Illuminate\Support\Facades\Request;
  use Symfony\Component\HttpFoundation\Response;

  class ItemController {

    private $itemService;
    private $episodeService;
    private $episodeUserService;

    public function __construct(
      ItemService $itemService,
      EpisodeService $episodeService,
      EpisodeUserService $episodeUserService,
    )
    {
      $this->itemService = $itemService;
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

      $item = Request::input('item');
      abort_if(!$item, 403);

      return $this->itemService->create($item, $user->id);
    }

    public function watchlist()
    {
      $item = $this->add();

      $item->userReview->update(['watchlist' => true]);

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
      if( ! $this->episodeUserService->toggleSeen($id)) {
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
  }
