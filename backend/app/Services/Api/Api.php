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

    $tmdbId = $this->getTmdbId();
    $found = $this->itemService->findByUser($user->id, $tmdbId);

    if (!$found) {
      $item = $this->item
        ->findByTitle($this->getTitle(), $this->getType())
        ->first();

      if(!$item) {
        $foundFromTmdb = $this->tmdb->search($this->getTitle(), $this->getType());

        if (!$foundFromTmdb) {
          return StatusEnum::NOT_FOUND;
        }

        // The first result is mostly the one we need.
        $firstResult = $foundFromTmdb[0];

        // Search again in our database with the TMDb ID.
        $found = $this->item->findByTmdbId($firstResult['tmdb_id'])->first();
      } else {
        $firstResult = $item->toArray();
      }

      $found = $this->itemService->create($firstResult, $user->id);
    }

    if ($this->shouldRateItem()) {
      Review::where([
        'user_id' => $user->id,
        'item_id' => $found->id
      ])
        ->update([
          'rating' => $this->getRating(),
        ]);
    }

    if ($this->shouldEpisodeMarkedAsSeen()) {
      $episode = $this->episode
        ->findByTmdbId($found->tmdb_id)
        ->findByEpisodeNumber($this->getEpisodeNumber())
        ->findBySeasonNumber($this->getSeasonNumber())
        ->first();

      if ($episode) {
        $episodeUser = EpisodeUser::firstOrCreate([
          'user_id' => $user->id,
          'episode_id' => $episode->id
        ]);
        if($episodeUser->wasRecentlyCreated === true) {
          Review::where([
            'user_id' => $user->id,
            'item_id' => $found->id
          ])->touch();
        }
      }
    }

    return StatusEnum::OK;
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
