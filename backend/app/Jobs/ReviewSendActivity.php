<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Fediverse\Activity\NoteActivity;
use App\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReviewSendActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $review;
    private $username = '';
    private $followersInbox = [];
    private $sharedInboxUrl = '';

    public function __construct(
        int $reviewId,
        string $username,
        array $followersInbox,
        string $sharedInboxUrl
    )
    {
        $this->review = Review::find($reviewId)->withoutRelations();
        $this->username = $username;
        $this->followersInbox = $followersInbox;
        $this->sharedInboxUrl = $sharedInboxUrl;
    }

    public function handle(): void
    {
        (new NoteActivity())->activity(
            $this->review,
            $this->username,
            $this->followersInbox,
            $this->sharedInboxUrl
        );
    }

    public function backoff(): array
    {
        return [60, 1800, 3600, 43200]; // 1mn, 30mn, 1h, 12h
    }
}
