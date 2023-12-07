<?php

namespace App\Services\Fediverse\Activity;

use ActivityPhp\Type\Extended\Activity\Follow;
use ActivityPhp\Type\Extended\Activity\Undo;
use App\Models\Follower;
use App\Models\Profile;
use App\Services\Fediverse\ActivityPubFetchService;
use App\Services\Models\ProfileService;

class UndoActivity
{
    public const ACTIVITY_NOT_IMPLEMENTED = 'activity-no-implemented';
    public const ACTIVITY_UNKNOWN_FOLLOWER = 'activity-unknown-follower';
    public const ACTIVITY_WRONG_OBJECT = 'activity-wrong-object';

    public function activity(Undo $undoActivity): void
    {
        $objectActivity = $undoActivity->get('object');
        if(is_object($objectActivity) === false) {
            throw new \Exception(self::ACTIVITY_WRONG_OBJECT);
        }
        switch($objectActivity::class) {
            case Follow::class:
                $this->followUndo($objectActivity);
                break;
            default:
                throw new \Exception(self::ACTIVITY_NOT_IMPLEMENTED);
        }
    }

    private function followUndo($objectActivity): void
    {
        $activityService = new ActivityService();
        $targetProfile = Profile::firstWhere(['remote_url' => $objectActivity->get('object')]);
        $activityService->targetValidation($targetProfile);
        $actor = (new ActivityPubFetchService())->get($objectActivity->get('actor'));
        $profileService = new ProfileService(new Profile());
        $sourceProfile = $profileService->updateOrCreate($actor);
        $deletedFollower = Follower::where(['profile_id' => $sourceProfile->id, 'target_profile_id' => $targetProfile->id])->delete();
        $this->deleteValidation($deletedFollower);
    }

    private function deleteValidation(int|null $deleted): void
    {
        if($deleted !== 1) {
            throw new \Exception(self::ACTIVITY_UNKNOWN_FOLLOWER);
        }
    }
}
