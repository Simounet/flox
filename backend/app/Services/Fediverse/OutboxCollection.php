<?php

declare(strict_types=1);

namespace App\Services\Fediverse;

use ActivityPhp\Type;
use ActivityPhp\Type\Core\OrderedCollection;
use App\Models\Profile;
use App\Models\Review;
use App\Services\Fediverse\Activity\ActivityService;
use App\Services\Fediverse\Activity\ReviewActivity;
use App\Services\Fediverse\Activity\Verbs;

class OutboxCollection
{
    public const PER_PAGE = 20;

    public function get(Profile $profile): OrderedCollection
    {
        $reviews = Review::findByUser($profile->user_id)
            ->where('content', '!=', '')
            ->orderByDesc('created_at')
            ->limit(self::PER_PAGE)
            ->get();

        $activityService = new ActivityService();
        $reviewActivity = new ReviewActivity();

        $orderedItems = $reviews->map(function (Review $review) use ($profile, $activityService, $reviewActivity) {
            $note = $reviewActivity->activity($review, $profile);
            $activityId = $profile->remote_url . '#create/review/' . $review->id;
            return $activityService->wrappedActivity(Verbs::CREATE, $activityId, $profile->remote_url, $note);
        })->all();

        $outbox = Type::create('OrderedCollection');
        $outbox->set('@context', 'https://www.w3.org/ns/activitystreams');
        $outbox->set('id', $profile->outbox_url);
        $outbox->set('totalItems', Review::findByUser($profile->user_id)->count());
        $outbox->set('orderedItems', $orderedItems);
        return $outbox;
    }
}
