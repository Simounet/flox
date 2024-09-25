<?php

namespace Tests\Services\Api;

use App\Enums\StatusEnum;
use App\Services\Api\Kodi;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\Fixtures;

class KodiApiTest extends TestCase implements ApiTestInterface
{
  use Fixtures;

  const API_URI = 'api/kodi';

  private ApiTest $apiTest;

  public function setUp(): void
  {
    parent::setUp();

    $this->apiTest = new ApiTest('test');

    $this->apiTest->apiClass = Kodi::class;

    $this->apiTest->setUp();
  }

  /** @test */
  public function it_should_abort_the_request()
  {
    $this->apiTest->it_should_abort_the_request('kodi/abort.json');
  }

  /** @test */
  public function it_should_create_a_new_movie()
  {
    $this->apiTest->it_should_create_a_new_movie('kodi/movie.json');
  }

  /** @test */
  public function it_should_not_create_a_new_movie_if_it_exists()
  {
    $this->apiTest->it_should_not_create_a_new_movie_if_it_exists('kodi/movie.json');
  }

  /** @test */
  public function it_should_create_a_new_tv_show()
  {
    $this->apiTest->it_should_create_a_new_tv_show('kodi/tv.json');
  }

  /** @test */
  public function it_should_not_create_a_new_tv_show_if_it_exists()
  {
    $this->apiTest->it_should_not_create_a_new_tv_show_if_it_exists('kodi/tv.json');
  }

  /** @test */
  public function it_should_rate_a_movie()
  {
    // @info Not implemented
    $this->apiTest->it_should_rate_a_movie('kodi/movie_rating.json', 1);
  }

  /** @test */
  public function it_should_rate_a_tv_show()
  {
    // @info Not implemented
    $this->apiTest->it_should_rate_a_tv_show('kodi/tv_rating.json', 1);
  }

  /** @test */
  public function it_should_mark_an_episode_as_seen()
  {
    $this->apiTest->it_should_mark_an_episode_as_seen('kodi/episode_seen.json');
  }

  /** @test */
  public function it_should_updated_review_updated_at()
  {
    $this->apiTest->it_should_updated_review_updated_at('kodi/episode_seen.json');
  }

  /** @test */
  public function it_should_add_a_review_to_existing_item()
  {
    $this->apiTest->it_should_add_a_review_to_existing_item('kodi/episode_seen.json');
  }

  /** @test */
  public function add_a_movie_from_api() {
      $fixture = 'kodi/movie.json';
      $this->apiTest->add_a_movie_from_api($fixture, self::API_URI, $this->getPayload($this->apiFixtures($fixture)));
  }

  /** @test */
  public function mark_episode_seen_multiple_times_from_api() {
      $fixture = 'kodi/episode_seen.json';
      $this->apiTest->mark_episode_seen_multiple_times_from_api($fixture, self::API_URI, $this->getPayload($this->apiFixtures($fixture)));
  }

  protected function getPayload(array $payload): array
  {
      return [
        'token' => $this->apiTest->user->api_key,
      ] + $payload;
  }
}
