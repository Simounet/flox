<?php

namespace Tests\Api;

use App\Profile;
use App\Services\Models\ItemService;
use App\Services\Models\ProfileService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\Fixtures;
use Tests\Traits\Mocks;

class ReviewTest extends TestCase {

    use RefreshDatabase;
    use Fixtures;
    use Mocks;

    protected $user;

    public function setUp(): void
    {
      parent::setUp();

      $profileService = new ProfileService(new Profile());
      $this->user = factory(User::class)->create();
      $profileService->storeLocal($this->user);
      $this->createStorageDownloadsMock();
    }

    /** @test */
    public function shouldFailOnPostingAReviewIfUserNotLoggedIn()
    {
      $this->postJson('api/review', [
        'itemId' => 1,
        'content' => 'Lorem ipsum.'
      ])->assertStatus(401);
    }

    /** @test */
    public function shouldFailOnPostingAReviewWithoutItemId()
    {
      $this->actingAs($this->user)->postJson('api/review', [
        'content' => 'Lorem ipsum.'
      ])->assertStatus(400);
    }

    /** @test */
    public function shouldPostAReview()
    {
      $this->mockItem();
      $this->actingAs($this->user)->postJson('api/review', [
        'itemId' => 1,
        'content' => 'Lorem ipsum.'
      ])->assertStatus(200);
      $this->assertDatabaseHas('reviews', [
        'item_id' => 1,
        'content' => 'Lorem ipsum.',
      ]);
    }

    /** @test */
    public function shouldUpdateAReview()
    {
      $this->mockItem();
      $this->actingAs($this->user)->postJson('api/review', [
        'itemId' => 1,
        'content' => 'Lorem ipsum.'
      ])->assertStatus(200);
      $this->actingAs($this->user)->postJson('api/review', [
        'itemId' => 1,
        'content' => 'Lorem ipsum dolor.'
      ])->assertStatus(200);
      $this->assertDatabaseHas('reviews', [
        'item_id' => 1,
        'content' => 'Lorem ipsum dolor.',
      ]);
    }

    private function mockItem()
    {
        $this->createGuzzleMock(
            $this->tmdbFixtures('movie/details'),
            $this->tmdbFixtures('movie/alternative_titles')
        );
        $itemService = app(ItemService::class);
        $itemService->create($this->floxFixtures('movie'));
    }
}
