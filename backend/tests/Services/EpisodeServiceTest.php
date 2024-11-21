<?php

  namespace Tests\Services;

  use Illuminate\Foundation\Testing\DatabaseTransactions;
  use Tests\TestCase;
  use App\Models\Episode;
  use App\Models\Item;
  use App\Services\Models\EpisodeService;
  use Tests\Traits\Factories;
  use Tests\Traits\Fixtures;
  use Tests\Traits\Mocks;

  class EpisodeServiceTest extends TestCase {

    use DatabaseTransactions;
    use Factories;
    use Fixtures;
    use Mocks;

    private $episode;
    private $episodeService;
    private $item;

    public function setUp(): void
    {
      parent::setUp();

      $this->episode = app(Episode::class);
      $this->episodeService = app(EpisodeService::class);
      $this->item = app(Item::class);
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
    public function it_should_remove_episodes()
    {
      $this->createTv();

      $episodes1 = $this->episode->findByTmdbId(1399)->get();
      $this->episodeService->remove(1399);
      $episodes2 = $this->episode->findByTmdbId(1399)->get();

      $this->assertNotNull($episodes1);
      $this->assertCount(0, $episodes2);
    }
  }
