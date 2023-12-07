<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Profile;
use App\Services\Fediverse\Activity\ActivityService;
use App\Services\Fediverse\Activity\ReviewActivity;
use App\Services\Fediverse\Activity\Verbs;
use App\Services\Fediverse\HttpSignature;
use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReviewSendActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $activityType = '';
    private $review;
    private $username = '';
    private $followersInbox = [];
    private $sharedInboxUrl = '';

    public function __construct(
        string $activityType,
        int $reviewId,
        string $username,
        array $followersInbox,
        string $sharedInboxUrl
    )
    {
        $this->activityType = $activityType;
        $this->review = Review::find($reviewId)->withoutRelations();
        $this->username = $username;
        $this->followersInbox = $followersInbox;
        $this->sharedInboxUrl = $sharedInboxUrl;
    }

    public function handle(): void
    {
        $profile = Profile::where(['username' => $this->username])->first();
        $reviewActivity = (new ReviewActivity())->activity(
            $this->review,
            $profile,
            $this->followersInbox
        );
        $createId = $profile->remote_url . '#' . ActivityService::TYPE_TO_REPLACE_PLACEHOLDER . '/review/' . $this->review->id;
        $activity = (new ActivityService())->wrappedActivity(
            $this->activityType,
            $createId,
            $profile->remote_url,
            $reviewActivity
        );

        $headers = (new HttpSignature)->sign(
            $this->sharedInboxUrl,
            $profile->private_key,
            $profile->key_id_url,
            $activity->toJson()
        );

        $response = Http::withHeaders($headers)
            ->post($this->sharedInboxUrl, $activity->toArray());
        $response->successful();
    }

    public function backoff(): array
    {
        return [60, 1800, 3600, 43200]; // 1mn, 30mn, 1h, 12h
    }
}
