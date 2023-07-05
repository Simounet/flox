<?php

namespace App\Services\Fediverse\Activity;

use ActivityPhp\Type\Extended\Activity\Accept;
use ActivityPhp\Type\Extended\Activity\Follow;
use App\Follower;
use App\Profile;
use App\Services\Fediverse\ActivityPubFetchService;
use App\Services\Models\ProfileService;

class FollowActivity
{
    public const ACTIVITY_ALREADY_PROCESSED = 'activity-already-processed';
    public const ACTIVITY_WRONG_TARGET = 'activity-wrong-target';

    public function activity(Follow $followActivity): Accept
    {
        $targetProfile = Profile::firstWhere(['remote_url' => $followActivity->get('object')]);
        $this->targetValidation($targetProfile);
        $actor = (new ActivityPubFetchService())->get($followActivity->get('actor'));
        $profileService = new ProfileService(new Profile());
        $sourceProfile = $profileService->updateOrCreate($actor);
        $this->alreadyProcessedValidation($sourceProfile, $targetProfile);

        Follower::create([
            'profile_id' => $sourceProfile->id,
            'target_profile_id' => $targetProfile->id,
            'activity_id' => $followActivity->get('id')
        ]);

        $acceptId = $profileService->acceptFollowsId($sourceProfile, $targetProfile);
        $accept = (new AcceptActivity())->activity($acceptId, $followActivity);
        return $accept;
    }

    private function alreadyProcessedValidation($sourceProfile, $targetProfile): void
    {
        if(
            Follower::where('profile_id', $sourceProfile->id)
                ->where('target_profile_id', $targetProfile->id)
                ->exists()
        ) {
            throw new \Exception(self::ACTIVITY_ALREADY_PROCESSED);
        }
    }

    private function targetValidation(Profile|null $targetProfile): void
    {
        if(
                $targetProfile === null
                || $targetProfile->domain !== env('APP_DOMAIN')
          ) {
            throw new \Exception(self::ACTIVITY_WRONG_TARGET);
        }
    }
}
