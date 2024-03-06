<?php

  namespace App\Models;

  use App\Services\Storage;
  use Carbon\Carbon;
  use Illuminate\Database\Eloquent\Builder;
  use Illuminate\Database\Eloquent\Model;
  use Illuminate\Database\Query\JoinClause;
  use Illuminate\Support\Facades\Auth;

  class Item extends Model {

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
    protected $with = ['genre', 'review', 'userReview'];

    /**
     * Guard accessors from import.
     *
     * @var array
     */
    protected $guarded = ['startDate'];

    /**
     * Create the new movie / tv show.
     *
     * @param $data
     * @return Item
     */
    public function store($data)
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
    public function storeEmpty($data, $mediaType)
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
    public function getStartDateAttribute()
    {
      if($this->released) {
        return Carbon::createFromTimestamp($this->released)->format('Y-m-d');
      }
    }

    /**
     * Belongs to many genres.
     */
    public function genre()
    {
      return $this->belongsToMany(Genre::class);
    }

    /**
     * Belongs to many creditCasts.
     */
    public function creditCast()
    {
      return $this->hasMany(CreditCast::class, 'tmdb_id', 'tmdb_id')->orderBy('order');
    }

    /**
     * Belongs to many creditCrews.
     */
    public function creditCrew()
    {
      return $this->hasMany(CreditCrew::class, 'tmdb_id', 'tmdb_id');
    }

    /**
     * Can have many episodes.
     */
    public function episodes()
    {
      return $this->hasMany(Episode::class, 'tmdb_id', 'tmdb_id');
    }

    /**
     * Belongs to many reviews.
     */
    public function review()
    {
      return $this->hasMany(Review::class);
    }

    public function userReview()
    {
      return $this->hasOne(Review::class)
        ->where('user_id', Auth::id());
    }

    /**
     * Can have many alternative titles.
     */
    public function alternativeTitles()
    {
      return $this->hasMany(AlternativeTitle::class, 'tmdb_id', 'tmdb_id');
    }

    /**
     * The latest unseen episode.
     */
    public function latestEpisode()
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
    public function episodesWithSrc()
    {
      return $this->hasMany(Episode::class, 'tmdb_id', 'tmdb_id')->whereNotNull('src');
    }

    /**
     * Scope to find the result by a genre.
     */
    public function scopeFindByGenreId($query, $genreId)
    {
      return $query->orWhereHas('genre', function($query) use ($genreId) {
        $query->where('genre_id', $genreId);
      });
    }

    /**
     * Scope to find the result by a person.
     */
    public function scopeFindByPersonId($query, $personId)
    {
      return $query->orWhereHas('person', function($query) use ($personId) {
        $query->where('person_id', $personId);
      });
    }

    /**
     * Scope to find the result via tmdb_id.
     */
    public function scopeFindByTmdbId($query, $tmdbId)
    {
      return $query->where('tmdb_id', $tmdbId);
    }

    /**
     * Scope to find the result via tmdb_id and media_type.
     */
    public function scopeFindByTmdbIdStrict($query, $tmdbId, $mediaType)
    {
      return $query->where('tmdb_id', $tmdbId)->where('media_type', $mediaType);
    }

    public function scopeFindByReviewWatchlist(Builder $query, int $watchlistValue): Builder
    {
        return $query->join('reviews', function(JoinClause $join) use ($watchlistValue) {
          $join->on('items.id', '=', 'reviews.item_id')
            ->where('reviews.watchlist', '=', $watchlistValue);
        });
    }

    /**
     * Scope to find the result by year.
     */
    public function scopeFindByYear($query, $year)
    {
      return $query->whereYear('released_datetime', $year);
    }

    /**
     * Scope to find the result via fp_name.
     */
    public function scopeFindByFPName($query, $item, $mediaType)
    {
      return $query->where('media_type', $mediaType)
        ->where(function($query) use ($item) {
          return $query->where('fp_name', $item->name)->orWhere('fp_name', getFileName($item));
        });
    }

    /**
     * Scope to find the result via src.
     */
    public function scopeFindBySrc($query, $src)
    {
      return $query->where('src', $src);
    }

    /**
     * Scope to find the result via title.
     */
    public function scopeFindByTitle($query, $title, $mediaType = null)
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
    public function scopeFindByTitleStrict($query, $title, $mediaType)
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
