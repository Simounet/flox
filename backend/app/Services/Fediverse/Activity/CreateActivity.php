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

        $localProfileRemoteUrl = $explodedUrl[0];
        $localProfile = Profile::where(['remote_url' => $localProfileRemoteUrl])->first();

        if(!$localProfile) {
            throw new \Exception(self::ACTIVITY_UNKNOWN_PROFILE);
        }

        $this->storeComment($localProfile, $createActivity, $reviewId);
    }

    private function storeComment(Profile $localProfile, Create $createActivity, int $reviewId): void
    {
        $actorComment = (new ActivityPubFetchService())->get($createActivity->get('actor'));
        $profileService = new ProfileService(new Profile());
        $profileComment = $profileService->updateOrCreate($actorComment);
        $commentModel = new Comment();
        $commentData = $this->getContent($localProfile, $createActivity);
        $commentModel->store(
            $profileComment->id,
            $reviewId,
            [
                'source_url' => $createActivity->id,
                'content' => $commentData['content'],
                'language' => $commentData['language'],
                'sensitive' => $createActivity->object->sensitive ?? false,
            ]
        );
    }

    private function getContent(Profile $localProfile, Create $createActivity): array
    {
        $hasContentMap = is_array($createActivity->object->contentMap);
        $activityObject = clone $createActivity->object;
        $language = $hasContentMap ?
            array_keys((array) $activityObject->contentMap)[0] : 'en';
        $content = $hasContentMap ?
            $createActivity->object->contentMap[$language] : $createActivity->object->content;
        return [
            'language' => $language,
            'content' => $this->cleanContent($localProfile, $content),
        ];
    }

    private function cleanContent(Profile $profile, string $content):string
    {
        return str_replace('@' . $profile->name . '@' . $profile->domain . ' ', '', strip_tags($content));
    }
}
