<?php

namespace Tests\Services\Api;

use App\Services\Api\Plex;
use Tests\TestCase;
use Tests\Traits\Fixtures;

class PlexApiTest extends TestCase implements ApiTestInterface
{
  use Fixtures;

  const API_URI = 'api/plex';

  private ApiTest $apiTest;

  public function setUp(): void
  {
    parent::setUp();

    $this->apiTest = new ApiTest('test');

    $this->apiTest->apiClass = Plex::class;

    $this->apiTest->setUp();
  }

  /** @test */
  public function it_should_abort_the_request()
  {
    $this->apiTest->it_should_abort_the_request('plex/abort.json');
  }

  /** @test */
  public function it_should_create_a_new_movie()
  {
    $this->apiTest->it_should_create_a_new_movie('plex/movie.json');
  }

  /** @test */
  public function it_should_not_create_a_new_movie_if_it_exists()
  {
    $this->apiTest->it_should_not_create_a_new_movie_if_it_exists('plex/movie.json');
  }

  /** @test */
  public function it_should_create_a_new_tv_show()
  {
    $this->apiTest->it_should_create_a_new_tv_show('plex/tv.json');
  }

  /** @test */
  public function it_should_not_create_a_new_tv_show_if_it_exists()
  {
    $this->apiTest->it_should_not_create_a_new_tv_show_if_it_exists('plex/tv.json');
  }

  /** @test */
  public function it_should_rate_a_movie()
  {
    $this->apiTest->it_should_rate_a_movie('plex/movie_rating.json', 2);
  }

  /** @test */
  public function it_should_rate_a_tv_show()
  {
    $this->apiTest->it_should_rate_a_tv_show('plex/tv_rating.json', 3);
  }

  /** @test */
  public function it_should_mark_an_episode_as_seen()
  {
    $this->apiTest->it_should_mark_an_episode_as_seen('plex/episode_seen.json');
  }

  /** @test */
  public function it_should_updated_review_updated_at()
  {
    $this->apiTest->it_should_updated_review_updated_at('plex/episode_seen.json');
  }

  /** @test */
  public function it_should_add_a_review_to_existing_item()
  {
    $this->apiTest->it_should_add_a_review_to_existing_item('plex/episode_seen.json');
  }

  /** @test */
  public function add_a_movie_from_api() {
    $fixture = 'plex/movie.json';
    $this->apiTest->add_a_movie_from_api($fixture, self::API_URI, $this->getPayload(json_encode($this->apiFixtures($fixture))));
  }

  /** @test */
  public function mark_episode_seen_multiple_times_from_api() {
    $fixture = 'plex/episode_seen.json';
    $this->apiTest->mark_episode_seen_multiple_times_from_api($fixture, self::API_URI, $this->getPayload(json_encode($this->apiFixtures($fixture))));
  }

  protected function getPayload(string $payload): array
  {
    return ['token' => $this->apiTest->user->api_key, 'payload' => $payload];
  }
}
