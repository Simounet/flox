<?php

namespace App\Services\Api;

use App\Enums\MediaTypeEnum;
use App\Enums\StatusEnum;
use App\Models\Episode;
use App\Models\Item;
use App\Models\ItemUser;
use App\Services\Models\EpisodeUserService;
use App\Services\Models\ItemService;
use App\Services\TMDB;
use App\ValueObjects\EpisodeUserValueObject;
use Illuminate\Support\Facades\Auth;

abstract class Api
{

  /**
   * @var array
   */
  protected $data = [];

  public function __construct(
    private Item $item,
    private TMDB $tmdb,
    private ItemService $itemService,
    private EpisodeUserService $episodeUserService,
    private Episode $episode)
  {}

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

    $itemUser = ItemUser::firstOrCreate([
      'user_id' => $user->id,
      'item_id' => $item->id
    ], ['rating' => 0]);

    if ($this->shouldRateItem()) {
      $itemUser
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
        $episodeUserValueObject = new EpisodeUserValueObject(Auth::id(), $episode->id);
        $this->episodeUserService->markEpisodeAsSeen($episode, $episodeUserValueObject);
      }
    }

    return StatusEnum::OK;
  }

  private function createItemWithTmdb(): ?Item
  {
    $tmdbId = $this->getTmdbId();
    if(!$tmdbId) {
      $foundFromTmdb = $this->tmdb->search($this->getTitle(), MediaTypeEnum::from($this->getType()));
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
    return $this->itemService->createItemInfoIfNotExists($firstResult['tmdb_id'], MediaTypeEnum::from($firstResult['media_type']));
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
   */
  abstract protected function getType(): string;

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
