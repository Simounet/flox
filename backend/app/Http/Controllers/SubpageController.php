<?php

  namespace App\Http\Controllers;

  use App\Enums\MediaTypeEnum;
  use App\Services\IMDB;
  use App\Services\Subpage;

  class SubpageController {

    public function item(int $tmdbId, string $mediaType, Subpage $subpage)
    {
      return $subpage->item($tmdbId, MediaTypeEnum::from($mediaType));
    }

    public function imdbRating($id, IMDB $imdb)
    {
      return $imdb->parseRating($id);
    }
  }
