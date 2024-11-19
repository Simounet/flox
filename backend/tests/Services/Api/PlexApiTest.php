<?php

namespace Tests\Services\Api;

use App\Enums\StatusEnum;
use App\Services\Api\Plex;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\Factories;
use Tests\Traits\Fixtures;

class PlexApiTest extends TestCase implements ApiTestInterface
{
  use Factories;
  use Fixtures;

  const API_URI = 'api/plex';

  private ApiTestBase $apiTestBase;

  public function setUp(): void
  {
    parent::setUp();

    $this->apiTestBase = new ApiTestBase('test');

    $this->apiTestBase->apiClass = Plex::class;

    $this->apiTestBase->setUp();
  }

  /** @test */
  public function token_needs_to_be_provided()
  {
    $response = $this->postJson('/api/plex');

    $response->assertJson(['message' => 'No token provided']);
    $response->assertStatus(Response::HTTP_UNAUTHORIZED);
  }

  /** @test */
  public function valid_token_needs_to_be_provided()
  {
    $mock = $this->mock(Plex::class);
    $mock->shouldReceive('handle')->once()->andReturn(StatusEnum::OK);
    $user = $this->createUser(['api_key' => Str::random(24)]);

    $responseBefore = $this->postJson('api/plex', ['token' => 'not-valid']);
    $responseAfter = $this->postJson('api/plex', ['token' => $user->api_key, 'payload' => '[]']);

    $responseBefore->assertJson(['message' => 'No valid token provided']);
    $responseBefore->assertStatus(Response::HTTP_UNAUTHORIZED);

    $responseAfter->assertSuccessful();
  }

  /** @test */
  public function it_should_abort_the_request()
  {
    $this->apiTestBase->it_should_abort_the_request('plex/abort.json');
  }

  /** @test */
  public function it_should_create_a_new_movie()
  {
    $this->apiTestBase->it_should_create_a_new_movie('plex/movie.json');
  }

  /** @test */
  public function it_should_not_create_a_new_movie_if_it_exists()
  {
    $this->apiTestBase->it_should_not_create_a_new_movie_if_it_exists('plex/movie.json');
  }

  /** @test */
  public function it_should_create_a_new_tv_show()
  {
    $this->apiTestBase->it_should_create_a_new_tv_show('plex/tv.json');
  }

  /** @test */
  public function it_should_not_create_a_new_tv_show_if_it_exists()
  {
    $this->apiTestBase->it_should_not_create_a_new_tv_show_if_it_exists('plex/tv.json');
  }

  /** @test */
  public function it_should_rate_a_movie()
  {
    $this->apiTestBase->it_should_rate_a_movie('plex/movie_rating.json', 2);
  }

  /** @test */
  public function it_should_rate_a_tv_show()
  {
    $this->apiTestBase->it_should_rate_a_tv_show('plex/tv_rating.json', 3);
  }

  /** @test */
  public function it_should_mark_an_episode_as_seen()
  {
    $this->apiTestBase->it_should_mark_an_episode_as_seen('plex/episode_seen.json');
  }

  /** @test */
  public function it_should_updated_review_updated_at()
  {
    $this->apiTestBase->it_should_updated_review_updated_at('plex/episode_seen.json');
  }

  /** @test */
  public function it_should_add_a_review_to_existing_item()
  {
    $this->apiTestBase->it_should_add_a_review_to_existing_item('plex/episode_seen.json');
  }

  /** @test */
  public function add_a_movie_from_api() {
    $fixture = 'plex/movie.json';
    $this->apiTestBase->add_a_movie_from_api($fixture, self::API_URI, $this->getPayload(json_encode($this->apiFixtures($fixture))));
  }

  /** @test */
  public function mark_episode_seen_multiple_times_from_api() {
    $fixture = 'plex/episode_seen.json';
    $this->apiTestBase->mark_episode_seen_multiple_times_from_api($fixture, self::API_URI, $this->getPayload(json_encode($this->apiFixtures($fixture))));
  }

  protected function getPayload(string $payload): array
  {
    return ['token' => $this->apiTestBase->user->api_key, 'payload' => $payload];
  }
}
