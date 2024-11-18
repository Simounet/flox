<?php

  namespace Tests\Traits;

  use App\Models\Episode;
  use App\Models\Item;
  use App\Models\Review;
  use App\Models\Setting;
  use App\Models\User;

  trait Factories {

    public function createUser(array $custom = []): User
    {
      $user = User::factory()->create($custom);
      $this->createSetting(['user_id' => $user->id]);
      return $user;
    }

    public function createSetting(array $custom): Setting
    {
      return Setting::factory()->create($custom);
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

      return Item::factory()->create(array_merge($data, $custom));
    }

    public function createReview(array $custom = []): Review
    {
        return Review::factory()->create($custom);
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

      $item = Item::factory()->create(array_merge($data, $custom));

      $episodes = [];
      if($withEpisodes) {
        foreach([1, 2] as $season) {
          foreach([1, 2] as $episode) {
            $episodes[] = Episode::factory()->create([
              'tmdb_id' => 1399,
              'season_number' => $season,
              'episode_number' => $episode,
            ]);
          }
        }
      }

      return [
        'item' => $item,
        'episodes' => $episodes
      ];
    }

    public function getMovie($custom = [])
    {
      $data = [
        'title' => 'Warcraft',
        'tmdb_id' => 68735,
      ];

      return Item::factory()->movie()->make(array_merge($data, $custom));
    }

    public function getTv($custom = [])
    {
      $data = [
        'title' => 'Game of Thrones',
        'tmdb_id' => 1399,
      ];

      return Item::factory()->tv()->make(array_merge($data, $custom));
    }
  }
