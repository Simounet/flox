<?php

  namespace App\Models;

  use Carbon\Carbon;
  use Illuminate\Database\Eloquent\Builder;
  use Illuminate\Database\Eloquent\Factories\HasFactory;
  use Illuminate\Database\Eloquent\Model;
  use Illuminate\Database\Eloquent\Relations\BelongsTo;
  use Illuminate\Database\Eloquent\Relations\BelongsToMany;
  use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function users(): BelongsToMany
    {
      return $this->belongsToMany(User::class);
    }

    public function episodesUsers(): HasMany
    {
      return $this->hasMany(EpisodeUser::class);
    }

    public function getSeenAttribute(): bool
    {
      return $this->episodesUsers()->where('user_id', Auth::id())->count() > 0;
    }

    /**
     * Accessor for formatted release date.
     */
    public function getStartDateAttribute(): string|null
    {
      if($this->release_episode) {
        return Carbon::createFromTimestamp($this->release_episode)->format('Y-m-d');
      }
      return null;
    }

    /**
     * Belongs to an item.
     */
    public function item(): BelongsTo
    {
      return $this->belongsTo(Item::class, 'tmdb_id', 'tmdb_id');
    }

    /**
     * Belongs to an item (simpler query).
     */
    public function calendarItem(): BelongsTo
    {
      return $this->belongsTo(Item::class, 'tmdb_id', 'tmdb_id')
        ->with(['itemUser' => function($e) {
          $e->select('item_id', 'watchlist');
        }])
        ->without(['review', 'user'])
        ->select(['tmdb_id', 'title', 'id']);
    }

    /**
     * Scope to find the result via tmdb_id.
     */
    public function scopeFindByTmdbId(Builder $query, int $tmdbId): Builder
    {
      return $query->where('tmdb_id', $tmdbId);
    }

    /**
     * Scope to find the result via episode_number.
     */
    public function scopeFindByEpisodeNumber(Builder $query, int $number): Builder
    {
      return $query->where('episode_number', $number);
    }

    /**
     * Scope to find the result via season_number.
     */
    public function scopeFindBySeasonNumber(Builder $query, int $number): Builder
    {
      return $query->where('season_number', $number);
    }

    /**
     * Scope to find the result via src.
     */
    public function scopeFindBySrc(Builder $query, string $src): Builder
    {
      return $query->where('src', $src);
    }

    /**
     * Scope to find the result via fp_name.
     */
    // @TODO define $item ValueObject
    public function scopeFindByFPName(Builder $query, object $item): Builder
    {
      return $query->where('fp_name', $item->name)->orWhere('fp_name', getFileName($item));
    }

    /**
     * Scope to find a specific episode.
     */
    // @TODO $episode typing
    public function scopeFindSpecificEpisode(Builder $query, ?int $tmdbId, object $episode): Builder
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
    public function scopeFindSeason(Builder $query, int $tmdbId, int $season): Builder
    {
      return $query->where('tmdb_id', $tmdbId)
        ->where('season_number', $season);
    }
  }
