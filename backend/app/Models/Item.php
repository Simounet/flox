<?php

  namespace App\Models;

  use App\Enums\MediaTypeEnum;
  use App\Services\Storage;
  use Carbon\Carbon;
  use Illuminate\Database\Eloquent\Builder;
  use Illuminate\Database\Eloquent\Factories\HasFactory;
  use Illuminate\Database\Eloquent\Model;
  use Illuminate\Database\Eloquent\Relations\BelongsToMany;
  use Illuminate\Database\Eloquent\Relations\HasMany;
  use Illuminate\Database\Eloquent\Relations\HasManyThrough;
  use Illuminate\Database\Eloquent\Relations\HasOne;
  use Illuminate\Database\Query\JoinClause;
  use Illuminate\Support\Facades\Auth;

  class Item extends Model {

    use HasFactory;

    /**
     * Fallback date string for a item.
     */
    const FALLBACK_DATE = '1970-12-1';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
      'created_at' => 'datetime',
      'refreshed_at' => 'datetime',
      'updated_at' => 'datetime',
      'released_datetime' => 'datetime',
      'media_type' => MediaTypeEnum::class
    ];
    
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
      'startDate',
    ];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['genre', 'itemUser'];

    /**
     * Guard accessors from import.
     *
     * @var array
     */
    protected $guarded = ['startDate'];

    /**
     * Create the new movie / tv show.
     */
    // @TODO create ItemDataValueObject
    public function store(array $data): self
    {
      return $this->firstOrCreate([
        'tmdb_id' => $data['tmdb_id'],
        'media_type' => $data['media_type'],
      ], [
        'title' => $data['title'],
        'original_title' => $data['original_title'],
        'poster' => $data['poster'] ?? '',
        'released' => $data['released'],
        'released_datetime' => Carbon::parse($data['released']),
        'overview' => $data['overview'],
        'backdrop' => $data['backdrop'],
        'tmdb_rating' => $data['tmdb_rating'],
        'imdb_id' => $data['imdb_id'],
        'imdb_rating' => $data['imdb_rating'],
        'youtube_key' => $data['youtube_key'],
        'slug' => $data['slug'],
        'homepage' => $data['homepage'] ?? null,
      ]);
    }

    /**
     * Create a new empty movie / tv show (for FP).
     *
     * @param $data
     * @param $mediaType
     * @return Item
     */
    // @TODO create ItemDataValueObject
    // @TODO create MediaTypeValueObject
    public function storeEmpty(array $data, string $mediaType): self
    {
      return $this->create([
        'tmdb_id' => null,
        'fp_name' => $data['name'],
        'title' => $data['name'],
        'media_type' => $mediaType,
        'poster' => '',
        'released' => time(),
        'released_datetime' => now(),
        'overview' => '',
        'backdrop' => '',
        'tmdb_rating' => '',
        'imdb_id' => '',
        'imdb_rating' => '',
        'youtube_key' => '',
        'src' => $data['src'],
        'subtitles' => $data['subtitles'],
        'homepage' => null,
      ]);
    }

    /**
     * Accessor for formatted release date.
     */
    public function getStartDateAttribute(): string|null
    {
      if($this->released) {
        return Carbon::createFromTimestamp($this->released)->format('Y-m-d');
      }
      return null;
    }

    /**
     * Belongs to many genres.
     */
    public function genre(): BelongsToMany
    {
      return $this->belongsToMany(Genre::class);
    }

    /**
     * Belongs to many creditCasts.
     */
    public function creditCast(): HasMany
    {
      return $this->hasMany(CreditCast::class, 'tmdb_id', 'tmdb_id')->orderBy('order');
    }

    /**
     * Belongs to many creditCrews.
     */
    public function creditCrew(): HasMany
    {
      return $this->hasMany(CreditCrew::class, 'tmdb_id', 'tmdb_id');
    }

    /**
     * Can have many episodes.
     */
    public function episodes(): HasMany
    {
      return $this->hasMany(Episode::class, 'tmdb_id', 'tmdb_id');
    }

    public function itemUser(): HasOne
    {
      return $this->hasOne(ItemUser::class)
        ->where('user_id', Auth::id());
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(
            Review::class,
            ItemUser::class,
            'item_id', // Foreign key on item_users pointing to users
            'item_user_id', // Foreign key on reviews pointing to item_users
            'id', // Local key on users
            'id' // Local key on item_users
        );
    }

    /**
     * Can have many alternative titles.
     */
    public function alternativeTitles(): HasMany
    {
      return $this->hasMany(AlternativeTitle::class, 'tmdb_id', 'tmdb_id');
    }

    /**
     * The latest unseen episode.
     */
    public function latestEpisode(): HasOne
    {
      $episodeUserSeen = EpisodeUser::select('episode_id')->from('episode_user');

      return $this->hasOne(Episode::class, 'tmdb_id', 'tmdb_id')
        ->orderBy('season_number', 'asc')
        ->orderBy('episode_number', 'asc')
        ->whereNotIn('id', $episodeUserSeen)
        ->latest();
    }

    /**
     * Can have many episodes with a src (from FP).
     */
    public function episodesWithSrc(): HasMany
    {
      return $this->hasMany(Episode::class, 'tmdb_id', 'tmdb_id')->whereNotNull('src');
    }

    /**
     * Scope to find the result by a genre.
     */
    public function scopeFindByGenreId(Builder $query, int $genreId): Builder
    {
      return $query->orWhereHas('genre', function($query) use ($genreId) {
        $query->where('genre_id', $genreId);
      });
    }

    /**
     * Scope to find the result by a person.
     */
    public function scopeFindByPersonId(Builder $query, int $personId): Builder
    {
      return $query->orWhereHas('person', function($query) use ($personId) {
        $query->where('person_id', $personId);
      });
    }

    /**
     * Scope to find the result via tmdb_id.
     */
    public function scopeFindByTmdbId(Builder $query, int $tmdbId): Builder
    {
      return $query->where('tmdb_id', $tmdbId);
    }

    /**
     * Scope to find the result via tmdb_id and media_type.
     */
    // @TODO create MediaTypeValueObject
    public function scopeFindByTmdbIdStrict(Builder $query, int $tmdbId, string $mediaType): Builder
    {
      return $query->where('tmdb_id', $tmdbId)->where('media_type', $mediaType);
    }

    public function scopeFindByItemUserWatchlist(Builder $query, int $watchlistValue): Builder
    {
        return $query->join('item_user', function(JoinClause $join) use ($watchlistValue) {
          $join->on('items.id', '=', 'item_user.item_id')
            ->where('item_user.watchlist', '=', $watchlistValue);
        });
    }

    /**
     * Scope to find the result by year.
     */
    public function scopeFindByYear(Builder $query, int $year): Builder
    {
      return $query->whereYear('released_datetime', $year);
    }

    /**
     * Scope to find the result via fp_name.
     */
    // @TODO $item typing
    // @TODO create MediaTypeValueObject
    public function scopeFindByFPName(Builder $query, object $item, string $mediaType): Builder
    {
      return $query->where('media_type', $mediaType)
        ->where(function($query) use ($item) {
          return $query->where('fp_name', $item->name)->orWhere('fp_name', getFileName($item));
        });
    }

    /**
     * Scope to find the result via src.
     */
    public function scopeFindBySrc(Builder $query, string $src): Builder
    {
      return $query->where('src', $src);
    }

    /**
     * Scope to find the result via title.
     */
    // @TODO create MediaTypeValueObject
    public function scopeFindByTitle(Builder $query, string $title, string $mediaType = null): Builder
    {
      // Only necessarily if we search from file-parser.
      if($mediaType) {
        $query->where('media_type', $mediaType);
      }

      $title = strtolower($title);

      // Some database queries using case sensitive likes -> compare lower case
      return $query->where(function($query) use ($title) {
        return $query->whereRaw('lower(title) like ?', ["%$title%"])
          ->orWhereRaw('lower(original_title) like ?', ["%$title%"])
          ->orWhereHas('alternativeTitles', function($query) use ($title) {
            return $query->whereRaw('lower(title) like ?', ["%$title%"]);
          });
      });
    }

    /**
     * Scope to find the result via title without a like query.
     */
    // @TODO create MediaTypeValueObject
    public function scopeFindByTitleStrict(Builder $query, string $title, string $mediaType): Builder
    {
      return $query->where('media_type', $mediaType)
        ->where(function($query) use ($title) {
          $query->where('title', $title)
          ->orWhere('original_title', $title)
          ->orWhere('fp_name', $title)
          ->orWhereHas('alternativeTitles', function($query) use ($title) {
            $query->where('title', $title);
          });
        });
    }

    public function getPoster(): array
    {
      return [
        'url' => (new Storage)->getPosterUrl($this->poster),
        'title' => $this->title
      ];
    }
  }
