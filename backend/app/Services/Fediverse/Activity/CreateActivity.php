<?php

declare(strict_types=1);

namespace App\Services\Fediverse\Activity;

use ActivityPhp\Type\Extended\Activity\Create;
use App\Models\Comment;
use App\Models\Profile;
use App\Models\Review;
use App\Services\Models\ProfileService;
use App\Services\Fediverse\ActivityPubFetchService;

class CreateActivity
{
    public const ACTIVITY_MISSING_IN_REPLY_TO = 'activity-missing-in-reply-to';
    public const ACTIVITY_UNKNOWN_REVIEW = 'activity-unknown-review';
    public const ACTIVITY_UNKNOWN_PROFILE = 'activity-unknown-profile';

    public function activity(Create $createActivity): void
    {
        $inReplyTo = $createActivity->object->inReplyTo ?? '';
        if ($inReplyTo === '') {
            throw new \Exception(self::ACTIVITY_MISSING_IN_REPLY_TO);
        }

        $explodedUrl = explode('/review/', $inReplyTo);
        $reviewId = (int) end($explodedUrl);
        $reviewExists = Review::whereId($reviewId)->count();

        if($reviewExists === 0) {
            throw new \Exception(self::ACTIVITY_UNKNOWN_REVIEW);
        }

        $profileService = new ProfileService(new Profile());
        $sourceProfile = $profileService->updateOrCreate($actor);
        // @TODO store comment
    }
}
