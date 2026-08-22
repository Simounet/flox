<?php

  namespace App\Models;

  use Illuminate\Database\Eloquent\Builder;
  use Illuminate\Database\Eloquent\Model;

  class AlternativeTitle extends Model {

    /**
     * No timestamps needed.
     * 
     * @var bool 
     */
    public $timestamps = false;

    /**
     * Don't auto-apply mass assignment protection.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Store all alternative titles for tv shows and movies.
     */
    public function store(array $titles, int $tmdbId): void
    {
      foreach($titles as $title) {
        $this->firstOrCreate([
          'title' => $title->title,
          'tmdb_id' => $tmdbId,
          'country' => $title->iso_3166_1,
        ]);
      }
    }

    /*
     * Scope to find the result via tmdb_id.
     */
    public function scopeFindByTmdbId(Builder $query, int $tmdbId): Builder
    {
      return $query->where('tmdb_id', $tmdbId);
    }
  }
