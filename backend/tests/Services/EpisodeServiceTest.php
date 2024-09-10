<?php

  namespace Tests\Services;

  use App\Models\EpisodeUser;
  use App\Models\Review;
  use App\ValueObjects\EpisodeUserValueObject;
  use Illuminate\Foundation\Testing\RefreshDatabase;
  use Tests\TestCase;
  use App\Models\Episode;
  use App\Models\Item;
  use App\Services\Models\EpisodeService;
  use Tests\Traits\Factories;
  use Tests\Traits\Fixtures;
  use Tests\Traits\Mocks;

  class EpisodeServiceTest extends TestCase {

    use RefreshDatabase;
    use Factories;
    use Fixtures;
    use Mocks;

    private $episode;
    private $episodeUser;
    private $episodeService;
    private $item;
    private Review $review;

    public function setUp(): void
    {
      parent::setUp();

      $this->episode = app(Episode::class);
      $this->episodeUser = app(EpisodeUser::class);
      $this->episodeService = app(EpisodeService::class);
      $this->item = app(Item::class);
      $this->review = app(Review::class);
    }

    /** @test */
    public function it_should_create_episodes()
    {
      $tv = $this->getTv();

      $this->createTmdbEpisodeMock();
      $episodeService = app(EpisodeService::class);

      $episodes1 = $this->episode->all();
      $episodeService->create($tv);
      $episodes2 = $this->episode->all();

      $this->assertCount(0, $episodes1);
      $this->assertCount(4, $episodes2);
    }

    /** @test */
    public function it_should_create_episodes_if_new_from_tmdb_are_available()
    {
      $this->createTv();

      $this->createTmdbEpisodeMock();
      $episodeService = app(EpisodeService::class);

      $this->episode->destroy(1);

      $item = $this->item->first();
      $episodes = $this->episode->all();
      $episodeService->create($item);
      $updatedEpisodes = $this->episode->all();

      $this->assertCount(3, $episodes);
      $this->assertCount(4, $updatedEpisodes);
    }

    /** @test */
    public function it_should_update_fields_on_refresh()
    {
      $this->createTv();

      $this->createTmdbEpisodeMock();
      $episodeService = app(EpisodeService::class);

      $this->episode->first()->update(['name' => 'UPDATE ME']);

      $item = $this->item->first();
      $episode = $this->episode->first();
      $episodeService->create($item);
      $updatedEpisode = $this->episode->first();

      $this->assertEquals('UPDATE ME', $episode->name);
      $this->assertEquals('name', $updatedEpisode->name);
    }

    /** @test */
    public function it_should_set_a_episode_as_seen_or_unseen()
    {
      $episodeId = 1;
      $user = $this->createUser();
      $this->actingAs($user);
      $this->createTv();
      $episodeUserValueObject = new EpisodeUserValueObject($user->id, $episodeId);

      $isEpisodeSeen1 = $this->episodeUser->isSeen($episodeUserValueObject);
      $this->episodeService->toggleSeen($user->id, 1);
      $isEpisodeSeen2 = $this->episodeUser->isSeen($episodeUserValueObject);
      $this->episodeService->toggleSeen($user->id, 1);
      $isEpisodeSeen3 = $this->episodeUser->isSeen($episodeUserValueObject);

      $this->assertEquals(0, $isEpisodeSeen1);
      $this->assertEquals(1, $isEpisodeSeen2);
      $this->assertEquals(0, $isEpisodeSeen3);
    }

    /** @test */
    public function it_should_set_all_episodes_of_a_season_as_seen_or_unseen()
    {
      $user = $this->createUser();
      $this->actingAs($user);
      $this->createTv();

      $season = 1;
      $tmdbId = 1399;

      $episodes1 = $this->episode->select('id')->where('season_number', $season)->pluck('id');
      $this->episodeService->toggleSeason($tmdbId, $season, 1);
      $episodes2 = $this->episode->select('id')->where('season_number', $season)->pluck('id');
      $this->episodeService->toggleSeason($tmdbId, $season, 0);
      $episodes3 = $this->episode->select('id')->where('season_number', $season)->pluck('id');

      $episodes1->each(function($episodeId) use ($user) {
        $this->assertEquals(0, EpisodeUser::isSeen(new EpisodeUserValueObject($user->id, $episodeId)));
      });
      $episodes2->each(function($episodeId) use ($user) {
        $this->assertEquals(0, EpisodeUser::isSeen(new EpisodeUserValueObject($user->id, $episodeId)));
      });
      $episodes3->each(function($episodeId) use ($user) {
        $this->assertEquals(0, EpisodeUser::isSeen(new EpisodeUserValueObject($user->id, $episodeId)));
      });
    }

    /** @test */
    public function it_should_remove_episodes()
    {
      $this->createTv();

      $episodes1 = $this->episode->findByTmdbId(1399)->get();
      $this->episodeService->remove(1399);
      $episodes2 = $this->episode->findByTmdbId(1399)->get();

      $this->assertNotNull($episodes1);
      $this->assertCount(0, $episodes2);
    }

    /** @test */
    public function it_should_update_items_from_one_episode()
    {
      $user = $this->createUser();
      $this->actingAs($user);
      $this->createTv();
      $this->createReview();

      $review = $this->review->first();
      sleep(1);
      $this->episodeService->toggleSeen($user->id, 1);
      $reviewUpdated = $this->review->first();

      $this->assertNotEquals($reviewUpdated->updated_at, $review->updated_at);
    }

    /** @test */
    public function it_should_update_items_only_on_seen_from_one_episode()
    {
      $user = $this->createUser();
      $this->actingAs($user);
      $this->createTv();
      $this->createReview();

      $this->episodeService->toggleSeen($user->id, 1);
      $review = $this->review->first();
      sleep(1);
      $this->episodeService->toggleSeen($user->id, 1);
      $reviewUpdated = $this->review->first();

      $this->assertEquals($reviewUpdated->updated_at, $review->updated_at);
    }

    /** @test */
    public function it_should_update_items_from_all_episodes()
    {
      $user = $this->createUser();
      $this->actingAs($user);
      $this->createTv();
      $this->createReview();

      $review = $this->review->first();
      sleep(1);
      $this->episodeService->toggleSeason(1399, 1, 1);
      $reviewUpdated = $this->review->first();

      $this->assertNotEquals($reviewUpdated->updated_at, $review->updated_at);
    }

    /** @test */
    public function it_should_update_items_only_on_seen_from_all_episodes()
    {
      $user = $this->createUser();
      $this->actingAs($user);
      $this->createTv();
      $this->createReview();

      $review = $this->review->first();
      sleep(1);
      $this->episodeService->toggleSeason(1399, 1, 0);
      $reviewUpdated = $this->review->first();

      $this->assertEquals($reviewUpdated->updated_at, $review->updated_at);
    }

    /** @test */
    public function it_should_toggle_season_even_with_some_episodes_already_watched(): void
    {
      $user = $this->createUser();
      $this->actingAs($user);
      $tv = $this->createTv();
      $this->createReview();

      $this->episodeService->toggleSeen($user->id, 1);
      $this->episodeService->toggleSeason($tv['item']->tmdb_id, 1, true);

      $episodesSeenCount = EpisodeUser::count();
      $this->assertEquals(2, $episodesSeenCount);
    }
  }
