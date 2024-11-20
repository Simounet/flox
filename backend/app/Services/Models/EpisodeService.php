<?php

  namespace App\Services\Models;

  use App\Models\Episode;
  use App\Models\EpisodeUser;
  use App\Models\Item;
  use App\Services\TMDB;
  use App\Models\Setting;
  use Carbon\Carbon;

  class EpisodeService {

    private $episode;
    private $tmdb;

    /**
     * @param Episode $episode
     * @param TMDB  $tmdb
     * @param Item  $item
     */
    public function __construct(Episode $episode, TMDB $tmdb)
    {
      $this->episode = $episode;
      $this->tmdb = $tmdb;
    }

    /**
     * @param $item
     */
    public function create($item)
    {
      if($item->media_type == 'tv') {
        $seasons = $this->tmdb->tvEpisodes($item->tmdb_id);

        foreach($seasons as $season) {
          $seasonAirDate = isset($season->air_date) ? ($season->air_date ?: Item::FALLBACK_DATE) : Item::FALLBACK_DATE;
          $releaseSeason = Carbon::createFromFormat('Y-m-d', $seasonAirDate);

          foreach($season->episodes as $episode) {
            $episodeAirDate = isset($episode->air_date) ? ($episode->air_date ?: Item::FALLBACK_DATE) : Item::FALLBACK_DATE;
            $releaseEpisode = Carbon::createFromFormat('Y-m-d', $episodeAirDate);

            $this->episode->updateOrCreate(
              [
                'season_number' => $episode->season_number,
                'episode_number' => $episode->episode_number,
                'tmdb_id' => $item->tmdb_id,
              ],
              [
                'season_tmdb_id' => $season->id,
                'episode_tmdb_id' => $episode->id,
                'release_episode' => $releaseEpisode->getTimestamp() >= 0 ? $releaseEpisode->getTimestamp() : 0,
                'release_season' => $releaseSeason->getTimestamp() >= 0 ? $releaseSeason->getTimestamp() : 0,
                'name' => substr($episode->name, 0, 255),
              ]
            );
          }
        }
      }
    }

    /**
     * Remove all episodes by tmdb_id.
     *
     * @param $tmdbId
     */
    public function remove($tmdbId)
    {
      $this->episode->where('tmdb_id', $tmdbId)->delete();
    }

    /**
     * Get all episodes of a tv show grouped by seasons,
     * the data for the next unseen episode, which will be used in the modal as an indicator,
     * and the setting option to check if spoiler protection is enabled.
     */
    public function getAllByTmdbId(int $userId, int $tmdbId): array
    {
      Carbon::setLocale(config('app.locale'));

      $episodes = $this->episode->findByTmdbId($tmdbId)->oldest('episode_number')->get()->groupBy('season_number');
      $episodeUserSeen = EpisodeUser::select('episode_id')->from('episode_user');
      $nextEpisode = $this->episode->findByTmdbId($tmdbId)->whereNotIn('id', $episodeUserSeen)->oldest('season_number')->oldest('episode_number')->first();

      return [
        'episodes' => $episodes,
        'next_episode' => $nextEpisode,
        'spoiler' => Setting::where('user_id', $userId)->first()->episode_spoiler_protection,
      ];
    }

    /**
     * See if we can find a episode by src or tmdb_id.
     * Or we search a specific episode in our database.
     *
     * @param      $type
     * @param      $value
     * @param null $episode
     * @return \Illuminate\Support\Collection
     */
    public function findBy($type, $value, $episode = null)
    {
      switch($type) {
        case 'src':
          return $this->episode->findBySrc($value)->first();
        case 'fp_name':
          return $this->episode->findByFPName($value)->first();
        case 'tmdb_id':
          return $this->episode->findByTmdbId($value)->first();
        case 'episode':
          return $this->episode->findSpecificEpisode($value, $episode)->first();
      }

      return null;
    }
  }
