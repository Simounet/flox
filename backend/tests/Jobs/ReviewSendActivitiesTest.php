<?php

declare(strict_types=1);

namespace Tests\Jobs;

use App\Jobs\ReviewSendActivities;
use App\Jobs\ReviewSendActivity;
use App\Models\Follower;
use App\Models\Item;
use App\Models\Profile;
use App\Models\Review;
use App\Models\User;
use App\Services\Fediverse\Activity\Verbs;
use App\Services\Models\ProfileService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReviewSendActivitiesTest extends TestCase
{
    use DatabaseTransactions;

    private Profile $profile;
    private Review $review;

    public function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->profile = (new ProfileService(new Profile()))->storeLocal($user);

        $item = Item::factory()->create();
        $this->review = Review::factory()->create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);
    }

    private function createFollower(): void
    {
        $user = User::factory()->create();
        $followerProfile = (new ProfileService(new Profile()))->storeLocal($user);

        Follower::create([
            'profile_id' => $followerProfile->id,
            'target_profile_id' => $this->profile->id,
        ]);
    }

    #[Test]
    public function dispatchesOneJobPerSharedInbox(): void
    {
        Queue::fake();

        $this->createFollower();

        $job = new ReviewSendActivities(Verbs::CREATE, $this->review->id, $this->profile->username);
        $job->handle();

        Queue::assertPushed(ReviewSendActivity::class, 1);
    }

    #[Test]
    public function groupsFollowersBySharedInbox(): void
    {
        Queue::fake();

        $this->createFollower();
        $this->createFollower();

        $job = new ReviewSendActivities(Verbs::CREATE, $this->review->id, $this->profile->username);
        $job->handle();

        Queue::assertPushed(ReviewSendActivity::class, 1);
    }

    #[Test]
    public function dispatchesNothingWithNoFollowers(): void
    {
        Queue::fake();

        $job = new ReviewSendActivities(Verbs::CREATE, $this->review->id, $this->profile->username);
        $job->handle();

        Queue::assertNothingPushed();
    }

    #[Test]
    public function throwsWhenProfileNotFound(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $job = new ReviewSendActivities(Verbs::CREATE, $this->review->id, 'nonexistent_user');
        $job->handle();
    }
}
