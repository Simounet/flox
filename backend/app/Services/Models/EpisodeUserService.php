<?php

namespace App\Services\Models;

use App\Models\Episode;
use App\Models\EpisodeUser;
use App\Models\Review;
use App\ValueObjects\EpisodeUserValueObject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

final class EpisodeUserService {
    public function __construct(
        private Episode $episode,
        private Review $review,
    ) {}

    /**
     * Set an episode as seen / unseen.
     */
    public function toggleSeen(int $id): bool
    {
      $episode = $this->episode->find($id);

      if($episode) {
        $episodeUserValueObject = new EpisodeUserValueObject(Auth::id(), $id);
        $isEpisodeSeen = EpisodeUser::isSeen($episodeUserValueObject);

        if(!$isEpisodeSeen) {
          return $this->markEpisodeAsSeen($episode, $episodeUserValueObject);
        }

        return EpisodeUser::where($episodeUserValueObject->get())->delete();
      }

      // @todo create Item if not in our database
      // Use case for an API (example Kodi) not creating the item before toggling episodes
      return false;
    }

    /**
     * Toggle all episodes of a season as seen / unseen.
     */
    public function toggleSeason(
      int $tmdbId,
      int $season,
      bool $seen
    ): Collection
    {
      $userId = Auth::id();
      $episodes = $this->episode->select('episodes.id', 'episodes.tmdb_id')->findSeason($tmdbId, $season)->get();

      if($seen) {
        $this->review->updateLastActivityAt($episodes[0]->tmdb_id);

        return $episodes->each(function($episode) use ($userId) {
          return EpisodeUser::updateOrCreate(
            (new EpisodeUserValueObject($userId, $episode->id))->get()
          );
        });
      }

      return $episodes->each(function($episode) use ($userId) {
        return EpisodeUser::where(
            (new EpisodeUserValueObject($userId, $episode->id))->get()
        )->delete();
      });

    }


    public function markEpisodeAsSeen(Episode $episode, EpisodeUserValueObject $episodeUserValueObject): bool
    {
        $this->review->updateLastActivityAt($episode->tmdb_id);
        return EpisodeUser::firstOrCreate($episodeUserValueObject->get())->wasRecentlyCreated;
    }
}
