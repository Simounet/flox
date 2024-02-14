<?php

namespace App\Services\Fediverse\Activity;

use ActivityPhp\Type\Extended\Object\Note;
use App\Models\Profile;

class ActivityService
{
    public const ACTIVITY_WRONG_TARGET = 'activity-wrong-target';
    public const TYPE_TO_REPLACE_PLACEHOLDER = '%TYPE_TO_REPLACE_PLACEHOLDER%';

    public function targetValidation(Profile|null $targetProfile): void
    {
        if(
                $targetProfile === null
                || $targetProfile->domain !== env('APP_DOMAIN')
          ) {
            throw new \Exception(self::ACTIVITY_WRONG_TARGET);
        }
    }

    public function wrappedActivity(string $type, string $activityId, string $actorUrl, Note $objectActivity)
    {
        $activity = (new Activity($type))->activity(
            str_replace(self::TYPE_TO_REPLACE_PLACEHOLDER, strtolower($type), $activityId),
            $actorUrl,
            $objectActivity
        );
        return $activity;
    }
}
