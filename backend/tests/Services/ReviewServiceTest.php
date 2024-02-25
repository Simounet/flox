<?php

namespace Tests\Services;

use App\Models\Item;
use App\Models\Review;
use App\Services\Models\ItemService;
use App\Services\Models\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\Factories;
use Tests\Traits\Fixtures;
use Tests\Traits\Mocks;

class ReviewServiceTest extends TestCase
{

    use RefreshDatabase;
    use Factories;
    use Fixtures;
    use Mocks;

    private $review;
    private $reviewService;
    private $user;

    public function setUp(): void
    {
      parent::setUp();

      $this->user = $this->createUser();

      $this->review = app(Review::class);
      $this->reviewService = app(ReviewService::class);

      $this->createStorageDownloadsMock();
      $this->createImdbRatingMock();
    }

    /** @test */
    public function review_should_be_created_on_item_creation(): void
    {
      $this->createGuzzleMock(
        $this->tmdbFixtures('movie/details'),
        $this->tmdbFixtures('movie/alternative_titles')
      );

      $item = app(Item::class);
      $itemService = app(ItemService::class);
      $itemService->create($this->floxFixtures('movie'), $this->user->id);
      $item = $item->all()->first();

      $this->assertIsObject($item->review);
      $this->assertCount(1, $item->review);
      $this->assertEquals(0, $item->review[0]->rating);
    }

    /** @test */
    public function it_should_change_rating()
    {
      $item = $this->createMovie();
      $review = $this->createReview(['item_id' => $item->id]);

      $review = $this->review->find($review->id);
      $this->reviewService->changeRating($review->id, 3, $this->user->id);
      $reviewUpdated = $this->review->find($review->id);

      $this->assertEquals(1, $review->rating);
      $this->assertEquals(3, $reviewUpdated->rating);
      $this->assertEquals($review->updated_at, $reviewUpdated->updated_at);
    }

    /** @test */
    public function it_should_change_review_updated_at_if_rating_was_neutral()
    {
      $item = $this->createMovie();
      $review = $this->createReview(['item_id' => $item->id, 'rating' => 0]);

      $review = $this->review->find($review->id);
      sleep(1);
      $this->reviewService->changeRating($review->id, 1, $this->user->id);
      $reviewUpdated = $this->review->find($review->id);

      $this->assertNotEquals($review->updated_at, $reviewUpdated->updated_at);
    }

    /** @test */
    public function it_should_only_change_target_user_rating(): void
    {
      $item = $this->createMovie();
      $user1InitialRating = 0;
      $reviewUser1 = $this->createReview(['item_id' => $item->id, 'rating' => $user1InitialRating]);

      $user2 = $this->createUser();
      $reviewUser2 = $this->createReview(['user_id' => $user2->id, 'item_id' => $item->id, 'rating' => 1]);
      $user2ModifiedRating = 1;
      $this->reviewService->changeRating($reviewUser2->id, $user2ModifiedRating, $user2->id);

      $reviewUser2Fresh = Review::select('rating')->where('id', $reviewUser1->id)->first();
      $this->assertEquals($user1InitialRating, $reviewUser2Fresh->rating);

      $reviewUser2Fresh = Review::select('rating')->where('id', $reviewUser2->id)->first();
      $this->assertEquals($user2ModifiedRating, $reviewUser2Fresh->rating);
    }
}
