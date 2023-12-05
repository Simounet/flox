<?php

namespace App\Services\Fediverse\Activity;

use ActivityPhp\Type\Extended\AbstractActor;
use ActivityPhp\Type\Extended\Activity\Follow;
use App\Follower;
use App\Profile;
use App\Services\Fediverse\ActivityPubFetchService;
use App\Services\Fediverse\HttpSignature;
use App\Services\Models\ProfileService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FollowActivity
{
    public function activity(Follow $followActivity): void
    {
        $activityService = new ActivityService();
        $targetProfile = Profile::firstWhere(['remote_url' => $followActivity->get('object')]);
        $activityService->targetValidation($targetProfile);
        Log::debug("[FollowActivityTest] followActivityActor", (array) $followActivity->get('actor'));
        $actor = (new ActivityPubFetchService())->get($followActivity->get('actor'));
        $profileService = new ProfileService(new Profile());
        $sourceProfile = $profileService->updateOrCreate($actor);

        Follower::firstOrCreate([
            'profile_id' => $sourceProfile->id,
            'target_profile_id' => $targetProfile->id
        ], [
            'activity_id' => $followActivity->get('id')
        ]);

        $this->sendAcceptActivity($profileService, $sourceProfile, $targetProfile, $followActivity, $actor);
    }

    private function sendAcceptActivity(
        ProfileService $profileService,
        Profile $sourceProfile,
        Profile $targetProfile,
        Follow $followActivity,
        AbstractActor $actor
    ): void
    {
        $acceptId = $profileService->acceptFollowsId($sourceProfile, $targetProfile);
        $accept = (new Activity(Verbs::ACCEPT))->activity($acceptId, $followActivity->get('object'), $followActivity);
        Log::debug("[FollowActivityAccept]", $accept->toArray());

        $remoteInboxUrl = $actor->endpoints['sharedInbox'];
        $headers = (new HttpSignature)->sign(
                $remoteInboxUrl,
                $targetProfile->private_key,
                $targetProfile->key_id_url,
                $accept->toJson()
                );

        $response = Http::withHeaders($headers)
            ->post($remoteInboxUrl, $accept->toArray());
        if($response->getStatusCode() !== 202) {
            throw new \Exception('Accept activity not validated');
        }
    }
}
