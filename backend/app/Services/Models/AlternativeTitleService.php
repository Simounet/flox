<?php

  namespace App\Services\Models;

  use App\Models\AlternativeTitle;
  use App\Models\Item;
  use App\Services\TMDB;

  class AlternativeTitleService {

    private $alternativeTitle;
    private $item;
    private $tmdb;

    /**
     * @param AlternativeTitle $alternativeTitle
     * @param Item  $item
     * @param TMDB  $tmdb
     */
    public function __construct(AlternativeTitle $alternativeTitle, Item $item, TMDB $tmdb)
    {
      $this->alternativeTitle = $alternativeTitle;
      $this->item = $item;
      $this->tmdb = $tmdb;
    }

    /**
     * @param $item
     */
    public function create($item)
    {
      $titles = $this->tmdb->getAlternativeTitles($item);

      $this->alternativeTitle->store($titles, $item->tmdb_id);
    }

    /**
     * Remove all titles by tmdb_id.
     *
     * @param $tmdbId
     */
    public function remove($tmdbId)
    {
      $this->alternativeTitle->where('tmdb_id', $tmdbId)->delete();
    }

    /**
     * Update alternative titles for all tv shows and movies.
     * For old versions of flox (<= 1.2.2) or to keep all alternative titles up to date.
     */
    public function update()
    {
      increaseTimeLimit();

      $items = $this->item->all();

      $items->each(function($item) {
        $this->create($item);
      });
    }
  }
