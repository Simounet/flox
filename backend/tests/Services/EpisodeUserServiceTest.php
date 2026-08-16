<?php

namespace Tests\Services;

use App\Models\Episode;
use App\Models\EpisodeUser;
use App\Models\ItemUser;
use App\Services\Models\EpisodeUserService;
use App\ValueObjects\EpisodeUserValueObject;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
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
    private ItemUser $itemUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->episode = app(Episode::class);
        $this->episodeUser = app(EpisodeUser::class);
        $this->episodeUserService = app(EpisodeUserService::class);
        $this->itemUser = app(ItemUser::class);
    }

    #[Test]
    public function it_should_set_a_episode_as_seen_or_unseen()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $tv = $this->createTv();
        $episodeId = $tv['episodes'][0]->id;
        $episodeUserValueObject = new EpisodeUserValueObject($user->id, $episodeId);

        $isEpisodeSeen1 = $this->episodeUser->isSeen($episodeUserValueObject);
        $this->episodeUserService->toggleSeen($episodeId, true);
        $isEpisodeSeen2 = $this->episodeUser->isSeen($episodeUserValueObject);
        $this->episodeUserService->toggleSeen($episodeId, false);
        $isEpisodeSeen3 = $this->episodeUser->isSeen($episodeUserValueObject);

        $this->assertEquals(0, $isEpisodeSeen1);
        $this->assertEquals(1, $isEpisodeSeen2);
        $this->assertEquals(0, $isEpisodeSeen3);
    }

    #[Test]
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

    #[Test]
    public function it_should_update_items_from_one_episode()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $tv = $this->createTv();
        $initialItemUser = $this->createItemUser([
            'user_id' => $user->id,
            'item_id' => $tv['item']->id,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay()
        ]);

        $itemUser = $this->itemUser->where(['id' => $initialItemUser->id])->first();
        $this->episodeUserService->toggleSeen($tv['episodes'][0]->id, true);
        $itemUserUpdated = $this->itemUser->where(['id' => $itemUser->id])->first();

        $this->assertNotEquals($itemUserUpdated->updated_at->timestamp, $itemUser->updated_at->timestamp);
    }

    #[Test]
    public function it_should_update_items_from_all_episodes()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $tv = $this->createTv();
        $initialItemUser = $this->createItemUser([
            'user_id' => $user->id,
            'item_id' => $tv['item']->id,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay()
        ]);

        $itemUser = $this->itemUser->where(['id' => $initialItemUser->id])->first();
        $this->episodeUserService->toggleSeason($tv['item']->tmdb_id, $tv['episodes'][0]->season_number, true);
        $updatedItemUser = $this->itemUser->first();

        $this->assertNotEquals($updatedItemUser->updated_at->timestamp, $itemUser->updated_at->timestamp);
    }

    #[Test]
    public function it_should_update_items_only_on_seen_from_all_episodes()
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $tv = $this->createTv();
        $initialItemUser = $this->createItemUser([
            'user_id' => $user->id,
            'item_id' => $tv['item']->id,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay()
        ]);

        $itemUser = $this->itemUser->where(['id' => $initialItemUser->id])->first();
        $this->episodeUserService->toggleSeason($tv['item']->tmdb_id, $tv['episodes'][0]->season_number, false);
        $itemUserWithSeasonToggled = $this->itemUser->where(['id' => $initialItemUser->id])->first();

        $this->assertEquals($itemUserWithSeasonToggled->updated_at, $itemUser->updated_at);
    }

    #[Test]
    public function it_should_toggle_season_even_with_some_episodes_already_watched(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $tv = $this->createTv();
        $this->createItemUser([
            'user_id' => $user->id,
            'item_id' => $tv['item']->id
        ]);

        $this->episodeUserService->toggleSeen($tv['episodes'][0]->id, false);
        $this->episodeUserService->toggleSeason($tv['item']->tmdb_id, $tv['episodes'][0]->season_number, true);

        $episodesSeenCount = EpisodeUser::count();
        $this->assertEquals(2, $episodesSeenCount);
    }
}
