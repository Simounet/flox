<?php

namespace Tests\Services;

use App\Models\Episode;
use App\Models\EpisodeUser;
use App\Models\Review;
use App\Services\Models\EpisodeUserService;
use App\ValueObjects\EpisodeUserValueObject;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\Factories;
use Tests\Traits\Fixtures;
use Tests\Traits\Mocks;

class EpisodeUserServiceTest extends TestCase
{

    use DatabaseTransactions;
    use Factories;
    use Fixtures;
    use Mocks;

    private Episode $episode;
    private EpisodeUser $episodeUser;
    private EpisodeUserService $episodeUserService;
    private Review $review;

    public function setUp(): void
    {
        parent::setUp();

        $this->episode = app(Episode::class);
        $this->episodeUser = app(EpisodeUser::class);
        $this->episodeUserService = app(EpisodeUserService::class);
        $this->review = app(Review::class);
    }

    /** @test */
    public function it_should_set_a_episode_as_seen_or_unseen()
    {
        $episodeId = 1;
        $user = $this->createUser();
        $this->actingAs($user);
        $tv = $this->createTv();
        $episodeUserValueObject = new EpisodeUserValueObject($user->id, $episodeId);

        $isEpisodeSeen1 = $this->episodeUser->isSeen($episodeUserValueObject);
        $this->episodeUserService->toggleSeen($tv['episodes'][0]->id);
        $isEpisodeSeen2 = $this->episodeUser->isSeen($episodeUserValueObject);
        $this->episodeUserService->toggleSeen($tv['episodes'][0]->id);
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
        $tv = $this->createTv();

        $season = $tv['episodes'][0]->season_number;
        $tmdbId = $tv['item']->tmdb_id;

        $episodes1 = $this->episode->select('id')->where('season_number', $season)->pluck('id');
        $this->episodeUserService->toggleSeason($tmdbId, $season, 1);
        $episodes2 = $this->episode->select('id')->where('season_number', $season)->pluck('id');
        $this->episodeUserService->toggleSeason($tmdbId, $season, 0);
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
    public function it_should_update_items_from_one_episode()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $tv = $this->createTv();
        $review = $this->createReview([
            'user_id' => $user->id,
            'item_id' => $tv['item']->id
        ]);

        $review = $this->review->where(['id' => $review->id])->first();
        sleep(1);
        $this->episodeUserService->toggleSeen($tv['episodes'][0]->id);
        $reviewUpdated = $this->review->where(['id' => $review->id])->first();

        $this->assertNotEquals($reviewUpdated->updated_at, $review->updated_at);
    }

    /** @test */
    public function it_should_update_items_only_on_seen_from_one_episode()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $tv = $this->createTv();
        $initialReview = $this->createReview([
            'user_id' => $user->id,
            'item_id' => $tv['item']->id
        ]);

        $this->episodeUserService->toggleSeen($tv['episodes'][0]->id);
        $reviewWithEpisodeSeen1= $this->review->where(['id' => $initialReview->id])->first();
        sleep(1);
        $this->episodeUserService->toggleSeen($tv['episodes'][0]->id);
        $reviewWithEpisodeSeen2= $this->review->where(['id' => $initialReview->id])->first();

        $this->assertEquals($reviewWithEpisodeSeen1->updated_at, $reviewWithEpisodeSeen2->updated_at);
    }

    /** @test */
    public function it_should_update_items_from_all_episodes()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $tv = $this->createTv();
        $initialReview = $this->createReview([
            'user_id' => $user->id,
            'item_id' => $tv['item']->id
        ]);

        $review = $this->review->where(['id' => $initialReview->id])->first();
        sleep(1);
        $this->episodeUserService->toggleSeason($tv['item']->tmdb_id, $tv['episodes'][0]->season_number, true);
        $updatedReview = $this->review->first();

        $this->assertNotEquals($updatedReview->updated_at, $review->updated_at);
    }

    /** @test */
    public function it_should_update_items_only_on_seen_from_all_episodes()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $tv = $this->createTv();
        $initialReview = $this->createReview([
            'user_id' => $user->id,
            'item_id' => $tv['item']->id
        ]);

        $review = $this->review->where(['id' => $initialReview->id])->first();
        sleep(1);
        $this->episodeUserService->toggleSeason($tv['item']->tmdb_id, $tv['episodes'][0]->season_number, false);
        $reviewWithSeasonToggled = $this->review->where(['id' => $initialReview->id])->first();

        $this->assertEquals($reviewWithSeasonToggled->updated_at, $review->updated_at);
    }

    /** @test */
    public function it_should_toggle_season_even_with_some_episodes_already_watched(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $tv = $this->createTv();
        $this->createReview([
            'user_id' => $user->id,
            'item_id' => $tv['item']->id
        ]);

        $this->episodeUserService->toggleSeen($tv['episodes'][0]->id);
        $this->episodeUserService->toggleSeason($tv['item']->tmdb_id, $tv['episodes'][0]->season_number, true);

        $episodesSeenCount = EpisodeUser::count();
        $this->assertEquals(2, $episodesSeenCount);
    }
}
