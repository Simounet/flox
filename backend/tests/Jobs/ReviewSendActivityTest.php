<?php

declare(strict_types=1);

namespace Tests\Jobs;

use App\Jobs\ReviewSendActivity;
use App\Models\Item;
use App\Models\Profile;
use App\Models\Review;
use App\Models\User;
use App\Services\Fediverse\Activity\Verbs;
use App\Services\Models\ProfileService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReviewSendActivityTest extends TestCase
{
    use DatabaseTransactions;

    private Profile $profile;
    private Review $review;
    private string $sharedInboxUrl = 'https://domain.tld/inbox';

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

    #[Test]
    public function sendsActivityToSharedInbox(): void
    {
        Http::fake([
            $this->sharedInboxUrl => Http::response('', 202),
        ]);

        $job = new ReviewSendActivity(
            Verbs::CREATE,
            $this->review->id,
            $this->profile->username,
            [$this->sharedInboxUrl],
            $this->sharedInboxUrl,
        );

        $job->handle();

        Http::assertSent(fn($request) => $request->url() === $this->sharedInboxUrl);
    }

    #[Test]
    public function throwsWhenProfileNotFound(): void
    {
        Http::fake();

        $this->expectException(ModelNotFoundException::class);

        $job = new ReviewSendActivity(
            Verbs::CREATE,
            $this->review->id,
            'nonexistent_user',
            [$this->sharedInboxUrl],
            $this->sharedInboxUrl,
        );

        $job->handle();
    }

    #[Test]
    public function throwsOnHttpFailure(): void
    {
        Http::fake([
            $this->sharedInboxUrl => Http::response('', 500),
        ]);

        $this->expectException(\Illuminate\Http\Client\RequestException::class);

        $job = new ReviewSendActivity(
            Verbs::CREATE,
            $this->review->id,
            $this->profile->username,
            [$this->sharedInboxUrl],
            $this->sharedInboxUrl,
        );

        $job->handle();
    }

    #[Test]
    public function hasCorrectBackoff(): void
    {
        $job = new ReviewSendActivity(
            Verbs::CREATE,
            $this->review->id,
            $this->profile->username,
            [$this->sharedInboxUrl],
            $this->sharedInboxUrl,
        );

        $this->assertEquals([60, 1800, 3600, 43200], $job->backoff());
    }
}
