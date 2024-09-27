<?php

namespace App\Services\Api;

class Kodi extends Api
{

  /**
   * @inheritDoc
   */
  protected function abortRequest()
  {
    return !in_array($this->data['mediaType'], ['tv', 'movie']);
  }

  /**
   * @inheritDoc
   */
  protected function getType()
  {
    $type = $this->data['mediaType'];

    return in_array($type, ['tv']) ? 'tv' : 'movie';
  }

  /**
   * @inheritDoc
   */
  protected function getTitle()
  {
    // @todo not implemented in Flox addon for Kodi yet
    return null;
  }

  /**
   * @inheritDoc
   */
  protected function shouldRateItem()
  {
    // @todo not implemented in Flox addon for Kodi yet
    return false;
  }

  /**
   * @inheritDoc
   */
  protected function getRating()
  {
    // @todo not implemented in Flox addon for Kodi yet
    return 0;
  }

  /**
   * @inheritDoc
   */
  protected function shouldEpisodeMarkedAsSeen()
  {
    return $this->data['mediaType'] === 'tv';
  }

  /**
   * @inheritDoc
   */
  protected function getEpisodeNumber()
  {
    return $this->data['episodeNumber'] ?? null;
  }

  /**
   * @inheritDoc
   */
  protected function getSeasonNumber()
  {
    return $this->data['seasonNumber'] ?? null;
  }

  protected function getTmdbId(): int|false
  {
    return $this->data['ids']['tmdbId'] ?? false;
  }
}
