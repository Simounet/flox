<?php

namespace App\Services\Api;

use App\Enums\StatusEnum;
use App\Models\Episode;
use App\Models\EpisodeUser;
use App\Models\Item;
use App\Models\Review;
use App\Services\Models\ItemService;
use App\Services\TMDB;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

abstract class Api
{

  /**
   * @var array
   */
  protected $data = [];

  /**
   * @var Item
   */
  private $item;

  /**
   * @var TMDB
   */
  private $tmdb;

  /**
   * @var ItemService
   */
  private $itemService;

  /**
   * @var Episode
   */
  private $episode;

  public function __construct(Item $item, TMDB $tmdb, ItemService $itemService, Episode $episode)
  {
    $this->item = $item;
    $this->tmdb = $tmdb;
    $this->itemService = $itemService;
    $this->episode = $episode;
  }

  public function handle(array $data): StatusEnum
  {
    logInfo('api data:', $data);

    $user = Auth::user();
    if(!$user) {
      return StatusEnum::UNAUTHORIZED;
    }

    $this->data = $data;

    if ($this->abortRequest()) {
      return StatusEnum::NOT_IMPLEMENTED;
    }

    $item = $this->getItem();
    if(!$item) {
      $item = $this->createItemWithTmdb();
      if (!$item) {
        return StatusEnum::NOT_FOUND;
      }
    }

    $review = Review::firstOrCreate([
      'user_id' => $user->id,
      'item_id' => $item->id
    ], ['rating' => 0]);

    if ($this->shouldRateItem()) {
      $review
        ->update([
          'rating' => $this->getRating(),
        ]);
    }

    if ($this->shouldEpisodeMarkedAsSeen()) {
      $episode = $this->episode
        ->findByTmdbId($item->tmdb_id)
        ->findByEpisodeNumber($this->getEpisodeNumber())
        ->findBySeasonNumber($this->getSeasonNumber())
        ->first();

      if ($episode) {
        $episodeUser = EpisodeUser::firstOrCreate([
          'user_id' => $user->id,
          'episode_id' => $episode->id
        ]);
        if($episodeUser->wasRecentlyCreated === true) {
          $review->touch();
        }
      }
    }

    return StatusEnum::OK;
  }

  private function createItemWithTmdb(): ?Item
  {
    $tmdbId = $this->getTmdbId();
    if(!$tmdbId) {
      $foundFromTmdb = $this->tmdb->search($this->getTitle(), $this->getType());
      if (!$foundFromTmdb) {
        return null;
      }

      // The first result is mostly the one we need.
      $firstResult = $foundFromTmdb[0];

      $item = $this->item->findByTmdbId($firstResult['tmdb_id'])->first();
      if($item) {
          return $item;
      }
    } else {
      $firstResult = [
        'tmdb_id' => $tmdbId,
        'media_type' => $this->getType()
      ];
    }
    return $this->itemService->createItemInfoIfNotExists($firstResult);
  }

  private function getItem(): ?Item
  {
    $foundItemByTmdbId = $this->getItemByTmdbId();
    if($foundItemByTmdbId) {
      return $foundItemByTmdbId;
    }

    $foundByTitle = $this->getItemByTitle();
    if($foundByTitle) {
      return $foundByTitle;
    }

    return null;
  }

  private function getItemByTmdbId(): ?Item
  {
    $tmdbId = $this->getTmdbId();
    if(!$tmdbId) {
      return null;
    }
    return $this->item->findByTmdbId($tmdbId)->first();
  }


  private function getItemByTitle(): ?Item
  {
    $title = $this->getTitle();
    if(!$title) {
      return null;
    }

    $item = $this->item
      ->findByTitle($title, $this->getType())
      ->first();

    return $item;
  }

  /**
   * Abort the complete request if it's not a movie or episode.
   *
   * @return bool
   */
  abstract protected function abortRequest();

  /**
   * Is it a movie or tv show? Should return 'tv' or 'movie'.
   *
   * @return string
   */
  abstract protected function getType();

  /**
   * Title for the item (name of the movie or tv show).
   *
   * @return string
   */
  abstract protected function getTitle();

  /**
   * Rating for flox in a 3-Point system.
   *
   * 1 = Good.
   * 2 = Medium.
   * 3 = Bad.
   *
   * @return int
   */
  abstract protected function getRating();

  /**
   * Check if rating is requested.
   *
   * @return bool
   */
  abstract protected function shouldRateItem();

  /**
   * Check if seen episode is requested.
   *
   * @return bool
   */
  abstract protected function shouldEpisodeMarkedAsSeen();

  /**
   * Number of the episode.
   *
   * @return null|int
   */
  abstract protected function getEpisodeNumber();

  /**
   * Number of the season.
   *
   * @return null|int
   */
  abstract protected function getSeasonNumber();

  abstract protected function getTmdbId(): int|false;
}
