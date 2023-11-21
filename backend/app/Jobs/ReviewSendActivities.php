<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Profile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReviewSendActivities implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $reviewId;
    private $username;

    public function __construct(int $reviewId, string $username)
    {
        $this->reviewId = $reviewId;
        $this->username = $username;
    }

    public function handle(): void
    {
        $actor = Profile::where('username', $this->username)->first();
        $bySharedInboxUrl = [];
        foreach($actor->followers as $follower) {
            $bySharedInboxUrl[$follower->shared_inbox_url][] = $follower->inbox_url;
        }
        foreach($bySharedInboxUrl as $sharedInboxUrl => $followersInbox) {
            ReviewSendActivity::dispatch(
                $this->reviewId,
                $this->username,
                $followersInbox,
                $sharedInboxUrl
            );
        }
    }
}
