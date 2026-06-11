<?php

  namespace Tests\Traits;

  use App\Models\Review;
  use App\Services\IMDB;
  use App\Services\Models\ItemService;
  use App\Services\Models\ReviewService;
  use App\Services\Storage;
  use App\Services\TMDB;
  use Illuminate\Support\Facades\Http;
  use Mockery;

  trait Mocks {

    public function createGuzzleMock()
    {
      $fixtures = func_get_args();
      $sequence = Http::fakeSequence();

      foreach($fixtures as $fixture) {
        $sequence->push($fixture, 200, ['X-RateLimit-Remaining' => 40]);
      }
    }

    public function createStorageDownloadsMock()
    {
      $mock = $this->mock(Storage::class);
      $mock->shouldReceive('downloadImages')->andReturn(null);
    }

    public function createRefreshAllMock()
    {
      $mock = $this->mock(ItemService::class);
      $mock->shouldReceive('refreshAll')->andReturn(null);
    }

    public function createTmdbEpisodeMock()
    {
      // Mock this to avoid unknown requests to TMDb (get seasons and then get episodes for each season)
      $mock = $this->mock(TMDB::class);
      $mock->shouldReceive('tvEpisodes')->andReturn(json_decode($this->tmdbFixtures('tv/episodes')));
    }

    private function createImdbRatingMock()
    {
      $mock = $this->mock(IMDB::class);
      $mock->shouldReceive('parseRating')->andReturn(json_decode($this->imdbFixtures('rating.txt')));
    }

    private function createReviewServiceMock()
    {
      $mock = $this->mock(ReviewService::class);
      $mock->shouldReceive('create')->andReturn(new Review());
    }

    public function mock($class, $mock = null)
    {
      $mock = Mockery::mock(app($class))->makePartial();

      $this->app->instance($class, $mock);

      return $mock;
    }
  }
