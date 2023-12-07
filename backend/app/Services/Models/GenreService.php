<?php

  namespace App\Services\Models;

  use App\Models\Genre;
  use App\Services\TMDB;
  use Illuminate\Support\Facades\DB;

  class GenreService {

    private $genre;
    private $tmdb;

    /**
     * @param Genre $genre
     * @param TMDB $tmdb
     */
    public function __construct(Genre $genre, TMDB $tmdb)
    {
      $this->genre = $genre;
      $this->tmdb = $tmdb;
    }

    /**
     * Sync the pivot table genre_item.
     * 
     * @param $item
     * @param $ids
     */
    public function sync($item, $ids)
    {
      $item->genre()->sync($ids);
    }

    /**
     * Update the genres table.
     */
    public function updateGenreLists()
    {
      $genres = $this->tmdb->getGenreLists();

      DB::beginTransaction();

      foreach($genres as $mediaType) {
        foreach($mediaType->genres as $genre) {
          $this->genre->firstOrCreate(
            ['id' => $genre->id],
            ['name' => $genre->name]
          );
        }
      }

      DB::commit();
    }
  }
