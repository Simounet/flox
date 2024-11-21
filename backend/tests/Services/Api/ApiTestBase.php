<?php

namespace Tests\Services\Api;

use App\Enums\StatusEnum;
use App\Models\EpisodeUser;
use App\Models\Item;
use App\Models\Review;
use App\Models\User;
use App\ValueObjects\EpisodeUserValueObject;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\Factories;
use Tests\Traits\Fixtures;
use Tests\Traits\Mocks;

class ApiTestBase extends TestCase
{
  use DatabaseTransactions;
  use Mocks;
  use Factories;
  use Fixtures;

  public $apiClass;
  public User $user;

  public function setUp(): void
  {
    parent::setUp();

    $this->user = $this->createUser(['api_key' => Str::random(24)]);

    $this->createStorageDownloadsMock();
    $this->createImdbRatingMock();
  }

  public function it_should_abort_the_request($fixture)
  {
    $api = app($this->apiClass);
    $this->assertEquals(StatusEnum::UNAUTHORIZED, $api->handle($this->apiFixtures($fixture)));
  }

  public function it_should_create_a_new_movie($fixture)
  {
    $this->be($this->user);
    $this->createGuzzleMock(
      $this->tmdbFixtures('movie/details'),
      $this->tmdbFixtures('movie/alternative_titles')
    );

    $api = app($this->apiClass);

    $itemsBefore = Item::all();

    $api->handle($this->apiFixtures($fixture));

    $itemsAfter = Item::all();

    $this->assertCount(0, $itemsBefore);
    $this->assertCount(1, $itemsAfter);
  }

  public function it_should_not_create_a_new_movie_if_it_exists($fixture)
  {
    $this->be($this->user);
    $this->createMovie();

    $api = app($this->apiClass);

    $itemsBefore = Item::all();

    $api->handle($this->apiFixtures($fixture));

    $itemsAfter = Item::all();

    $this->assertCount(1, $itemsBefore);
    $this->assertCount(1, $itemsAfter);
  }

  public function it_should_create_a_new_tv_show($fixture)
  {
    $this->be($this->user);
    $this->createGuzzleMock(
      $this->tmdbFixtures('tv/details'),
      $this->tmdbFixtures('tv/alternative_titles')
    );

    $this->createTmdbEpisodeMock();

    $api = app($this->apiClass);

    $itemsBefore = Item::all();

    $api->handle($this->apiFixtures($fixture));

    $itemsAfter = Item::all();

    $this->assertCount(0, $itemsBefore);
    $this->assertCount(1, $itemsAfter);
  }

  public function it_should_not_create_a_new_tv_show_if_it_exists($fixture)
  {
    $this->be($this->user);
    $this->createTv();

    $api = app($this->apiClass);

    $itemsBefore = Item::all();

    $api->handle($this->apiFixtures($fixture));

    $itemsAfter = Item::all();

    $this->assertCount(1, $itemsBefore);
    $this->assertCount(1, $itemsAfter);
  }

  public function it_should_rate_a_movie($fixture, $shouldHaveRating)
  {
    $this->be($this->user);
    $this->createMovie();
    $this->createReview();

    $api = app($this->apiClass);

    $movieBefore = Review::first();

    $api->handle($this->apiFixtures($fixture));

    $movieAfter = Review::first();

    $this->assertEquals(1, $movieBefore->rating);
    $this->assertEquals($shouldHaveRating, $movieAfter->rating);
  }

  public function it_should_rate_a_tv_show($fixture, $shouldHaveRating)
  {
    $this->be($this->user);
    $this->createTv();
    $this->createReview();

    $api = app($this->apiClass);

    $tvBefore = Review::first();

    $api->handle($this->apiFixtures($fixture));

    $tvAfter = Review::first();

    $this->assertEquals(1, $tvBefore->rating);
    $this->assertEquals($shouldHaveRating, $tvAfter->rating);
  }

  public function it_should_mark_an_episode_as_seen($fixture)
  {
    $this->be($this->user);
    $this->createTv();
    $userId = 1;
    $episodeId = 2;

    $api = app($this->apiClass);

    $seenEpisodesBefore = EpisodeUser::isSeen(new EpisodeUserValueObject($userId, $episodeId));

    $api->handle($this->apiFixtures($fixture));

    $seenEpisodesAfter = EpisodeUser::isSeen(new EpisodeUserValueObject($userId, $episodeId));

    $this->assertFalse($seenEpisodesBefore);
    $this->assertTrue($seenEpisodesAfter);
  }

  public function it_should_updated_review_updated_at($fixture)
  {
    $this->be($this->user);
    $this->createTv();
    $this->createReview();

    $api = app($this->apiClass);

    $updatedAt = Review::first()->updated_at;

    // sleep for 1 second so that Carbon::now() returns a different date
    sleep(1);

    $api->handle($this->apiFixtures($fixture));

    $updatedAtUpdated = Review::first()->updated_at;

    $this->assertNotEquals($updatedAt, $updatedAtUpdated);
  }

  public function it_should_add_a_review_to_existing_item($fixture)
  {
    $this->be($this->user);
    $this->createTv();
    $review = $this->createReview();

    $this->assertEquals(1, Review::count());
    $review->delete();
    $this->assertEquals(0, Review::count());
    $this->assertEquals(1, Item::count());

    $api = app($this->apiClass);
    $api->handle($this->apiFixtures($fixture));
    $this->assertEquals(1, Review::count());
  }

  public function add_a_movie_from_api(string $fixture, string $apiUri, array $data)
  {
    $this->createGuzzleMock(
      $this->tmdbFixtures('movie/details'),
      $this->tmdbFixtures('movie/alternative_titles')
    );

    $this->assertEquals(0, Review::count());

    $response = $this->postJson($apiUri, $data);
    $response->assertStatus(200);
    $this->assertEquals(1, Review::count());
  }

  public function mark_episode_seen_multiple_times_from_api(string $fixture, string $apiUri, array $data)
  {
    $this->createGuzzleMock(
      $this->tmdbFixtures('tv/details'),
      $this->tmdbFixtures('tv/alternative_titles')
    );
    $this->createTmdbEpisodeMock();

    $this->assertEquals(0, Review::count());

    $response = $this->postJson($apiUri, $data);
    $response->assertStatus(200);
    $this->assertEquals(1, Review::count());

    $response = $this->postJson($apiUri, $data);
    $response->assertStatus(200);
    $this->assertEquals(1, Review::count());
  }
}
