<?php

namespace App\Services\Fediverse\Activity;

use ActivityPhp\Type\Extended\Activity\Accept;
use ActivityPhp\Type\Extended\Activity\Follow;
use ActivityPhp\Type\Extended\Activity\Undo;
use App\Profile;
use App\Services\Models\ProfileService;

class ActivityService
{
    public const ACTIVITY_WRONG_TARGET = 'activity-wrong-target';

    public function targetValidation(Profile|null $targetProfile): void
    {
        if(
                $targetProfile === null
                || $targetProfile->domain !== env('APP_DOMAIN')
          ) {
            throw new \Exception(self::ACTIVITY_WRONG_TARGET);
        }
    }
}
