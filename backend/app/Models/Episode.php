<?php

  namespace App\Models;

  use Carbon\Carbon;
  use Illuminate\Database\Eloquent\Factories\HasFactory;
  use Illuminate\Database\Eloquent\Model;
  use Illuminate\Support\Facades\Auth;

  class Episode extends Model {
    use HasFactory;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
      'seen',
      'startDate',
    ];

    /**
     * Guard accessors from import.
     *
     * @var array
     */
    protected $guarded = [
      'seen',
      'startDate',
    ];

    public function users() {
      return $this->belongsToMany(User::class);
    }

    public function episodesUsers()
    {
      return $this->hasMany(EpisodeUser::class);
    }

    public function getSeenAttribute()
    {
      return $this->episodesUsers()->where('user_id', Auth::id())->count() > 0;
    }

    /**
     * Accessor for formatted release date.
     */
    public function getStartDateAttribute()
    {
      if($this->release_episode) {
        return Carbon::createFromTimestamp($this->release_episode)->format('Y-m-d');
      }
    }

    /**
     * Belongs to an item.
     */
    public function item()
    {
      return $this->belongsTo(Item::class, 'tmdb_id', 'tmdb_id');
    }

    /**
     * Belongs to an item (simpler query).
     */
    public function calendarItem()
    {
      return $this->belongsTo(Item::class, 'tmdb_id', 'tmdb_id')
        ->with(['userReview' => function($e) {
          $e->select('item_id', 'watchlist');
        }])
        ->without(['review', 'user'])
        ->select(['tmdb_id', 'title', 'id']);
    }

    /**
     * Scope to find the result via tmdb_id.
     */
    public function scopeFindByTmdbId($query, $tmdbId)
    {
      return $query->where('tmdb_id', $tmdbId);
    }

    /**
     * Scope to find the result via episode_number.
     */
    public function scopeFindByEpisodeNumber($query, $number)
    {
      return $query->where('episode_number', $number);
    }

    /**
     * Scope to find the result via season_number.
     */
    public function scopeFindBySeasonNumber($query, $number)
    {
      return $query->where('season_number', $number);
    }

    /**
     * Scope to find the result via src.
     */
    public function scopeFindBySrc($query, $src)
    {
      return $query->where('src', $src);
    }

    /**
     * Scope to find the result via fp_name.
     */
    public function scopeFindByFPName($query, $item)
    {
      return $query->where('fp_name', $item->name)->orWhere('fp_name', getFileName($item));
    }

    /**
     * Scope to find a specific episode.
     */
    public function scopeFindSpecificEpisode($query, $tmdbId, $episode)
    {
      $season = $episode->changed->season_number ?? $episode->season_number;
      $episode = $episode->changed->episode_number ?? $episode->episode_number;

      return $query->where('tmdb_id', $tmdbId)
        ->where('season_number', $season)
        ->where('episode_number', $episode);
    }

    /**
     * Scope to find a complete season.
     */
    public function scopeFindSeason($query, $tmdbId, $season)
    {
      return $query->where('tmdb_id', $tmdbId)
        ->where('season_number', $season);
    }
  }
