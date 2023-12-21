<?php

  namespace Tests\Traits;

  use App\Models\Episode;
  use App\Models\Item;
  use App\Models\Review;
  use App\Models\Setting;
  use App\Models\User;

  trait Factories {

    public function createUser($custom = [])
    {
      return factory(User::class)->create($custom);
    }

    public function createSetting()
    {
      return factory(Setting::class)->create();
    }

    public function createMovie($custom = [])
    {
      $data = [
        'title' => 'Warcraft: The Beginning',
        'original_title' => 'Warcraft',
        'tmdb_id' => 68735,
        'media_type' => 'movie',
        'poster' => '',
        'backdrop' => '',
      ];

      return factory(Item::class)->create(array_merge($data, $custom));
    }

    public function createReview(array $custom = []): Review
    {
        $item = $this->createMovie();

        $data = [
            'user_id' => 1,
            'item_id' => $item->id,
            'rating' => 1
        ];

        return factory(Review::class)->create(array_merge($data, $custom));
    }

    public function createTv($custom = [], $withEpisodes = true)
    {
      $data = [
        'title' => 'Game of Thrones',
        'original_title' => 'Game of Thrones',
        'tmdb_id' => 1399,
        'media_type' => 'tv',
        'poster' => '',
        'backdrop' => '',
      ];

      factory(Item::class)->create(array_merge($data, $custom));

      if($withEpisodes) {
        foreach([1, 2] as $season) {
          foreach([1, 2] as $episode) {
            factory(Episode::class)->create([
              'tmdb_id' => 1399,
              'season_number' => $season,
              'episode_number' => $episode,
            ]);
          }
        }
      }
    }

    public function getMovie($custom = [])
    {
      $data = [
        'title' => 'Warcraft',
        'tmdb_id' => 68735,
      ];

      return factory(Item::class)->states('movie')->make(array_merge($data, $custom));
    }

    public function getTv($custom = [])
    {
      $data = [
        'title' => 'Game of Thrones',
        'tmdb_id' => 1399,
      ];

      return factory(Item::class)->states('tv')->make(array_merge($data, $custom));
    }
  }
