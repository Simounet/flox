<?php

namespace Tests\Services;

use App\Models\Review;
use App\Models\User;
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

    public function setUp(): void
    {
      parent::setUp();

      $user = $this->createUser();

      $this->review = app(Review::class);
      $this->reviewService = app(ReviewService::class);

      $this->createStorageDownloadsMock();
      $this->createImdbRatingMock();
    }

    /** @test */
    public function it_should_change_rating()
    {
      $review = $this->createReview();
      $reviewId = $review->id;

      $itemBefore = $this->review->find($reviewId);
      $this->reviewService->changeRating($reviewId, 3);
      $itemAfter = $this->review->find($reviewId);

      $this->assertEquals(1, $itemBefore->rating);
      $this->assertEquals(3, $itemAfter->rating);
      $this->assertEquals($itemBefore->last_seen_at, $itemAfter->last_seen_at);
    }
}
