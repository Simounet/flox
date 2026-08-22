<?php

  namespace App\Models;

  use Illuminate\Database\Eloquent\Builder;
  use Illuminate\Database\Eloquent\Model;

  class Genre extends Model {

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
     * Scope to find the genre by name.
     */
    public function scopeFindByName(Builder $query, string $genre): Builder
    {
      return $query->where('name', $genre);
    }
  }
