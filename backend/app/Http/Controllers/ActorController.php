<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use ActivityPhp\Type;
use ActivityPhp\Type\Extended\Activity\Delete;
use ActivityPhp\Type\Extended\Activity\Follow;
use ActivityPhp\Type\Extended\Activity\Undo;
use ActivityPhp\Type\Extended\Activity\Create;
use ActivityPhp\Type\TypeConfiguration;
use App\Models\Comment;
use App\Models\Profile;
use App\Services\Fediverse\Activity\ActivityService;
use App\Services\Fediverse\Activity\ActorActivity;
use App\Services\Fediverse\Activity\CreateActivity;
use App\Services\Fediverse\Activity\UndoActivity;
use App\Services\Fediverse\Activity\FollowActivity;
use App\Services\Fediverse\FollowingCollection;
use App\Services\Fediverse\FollowersCollection;
use App\Services\Fediverse\HttpFediverseResponse;
use App\Services\Fediverse\HttpSignature;
use App\Services\Fediverse\OutboxCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ActorController
{
    public function __construct(
        private HttpFediverseResponse $httpFediverseResponse
    ) {}

    public function actor(string $username): JsonResponse
    {
        abort_if(config('flox.federation.enabled') === false, 404);

        $profile = (new Profile())->whereLocalProfile($username);
        Log::debug('[ActorRequest] profile: ' . json_encode($profile));
        if($profile === null) {
            return $this->httpFediverseResponse->get(Response::HTTP_NOT_FOUND);
        }
        $person = (new ActorActivity())->actorObject($profile)->toArray();
        Log::debug('[ActorRequest] person: ' . json_encode($person));

        return $this->httpFediverseResponse->get(Response::HTTP_OK, $person);
    }

    public function followers(string $username): JsonResponse
    {
        abort_if(config('flox.federation.enabled') === false, 404);

        $profileBuilder = Profile::where('username', $username);
        switch($profileBuilder->count()) {
            case 0:
                return $this->httpFediverseResponse->get(Response::HTTP_NOT_FOUND);
            case 1:
                $followers = (new FollowersCollection())->get($profileBuilder->first());
                return $this->httpFediverseResponse->get(Response::HTTP_OK, $followers->toArray());
            default:
                return $this->httpFediverseResponse->get(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function following(string $username): JsonResponse
    {
        abort_if(config('flox.federation.enabled') === false, 404);

        $profileBuilder = Profile::where('username', $username);
        switch($profileBuilder->count()) {
            case 0:
                return $this->httpFediverseResponse->get(Response::HTTP_NOT_FOUND);
            case 1:
                $followings = (new FollowingCollection())->get($profileBuilder->first());
                return $this->httpFediverseResponse->get(Response::HTTP_OK, $followings->toArray());
            default:
                return $this->httpFediverseResponse->get(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function inbox(Request $request, string $username): JsonResponse
    {
        abort_if(config('flox.federation.enabled') === false, 404);

        $payload = $request->getContent();
        Log::debug("[InboxRequest]", $request->all());
        try {
            TypeConfiguration::set('undefined_properties', 'ignore');
            $activity = Type::fromJson($payload);
        } catch(\Exception $e) {
            return $this->httpFediverseResponse->get(Response::HTTP_BAD_REQUEST);
        }
        Log::debug("[InboxActivity] " . $activity::class . ': ' . $activity->toJson(JSON_UNESCAPED_SLASHES));
        $activityService = new ActivityService();

        $headers = $request->headers;
        Log::debug("[InboxRequestHeaders] " . (string) $headers);
        try {
            $actor = false;
            if(Delete::class === $activity::class) {
                $objectActivity = $activity->get('object');
                if(!$objectActivity || ($objectActivity !== $activity->get('actor') && $objectActivity && !$this->isTombstone($activity))) {
                    $this->logInvalidState("[InboxActivity] " . $activity::class . ' invalid state', $request);
                    return $this->httpFediverseResponse->get(Response::HTTP_OK);
                }
                $remoteUrl = is_string($objectActivity) ?
                    $objectActivity : $activity->get('actor');
                $profiles = Profile::where(['remote_url' => $remoteUrl]);
                if(0 === $profiles->count()) {
                    Log::debug("[InboxActivity] " . $activity::class . ' unknown profile: ' . $remoteUrl);
                    return $this->httpFediverseResponse->get(Response::HTTP_OK);
                } else {
                    $profile = $profiles->first();
                    $actor = (new ActorActivity())->actorObject($profile);
                }
            }
            $verifiedSignature = (new HttpSignature())->verifySignature($request->getMethod(),  $request->getPathInfo(), $headers, $payload, $actor);
        } catch(\Exception $e) {
            Log::error('[InboxActivity] Error: ' . $e->getMessage());
            return $this->httpFediverseResponse->get(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if(!$verifiedSignature) {
            Log::debug("[InboxActivity] Wrong signature");
            return $this->httpFediverseResponse->get(Response::HTTP_UNAUTHORIZED);
        }

        switch($activity::class) {
            case Create::class:
                try {
                    $createActivity = new CreateActivity();
                    $createActivity->activity($activity);
                    $this->logInvalidState("[InboxCreateResponse] Create activity: " . $activity::class, $request);
                } catch(\Exception $e) {
                    switch($e->getMessage()) {
                        case $createActivity::ACTIVITY_MISSING_IN_REPLY_TO:
                            Log::debug("[InboxCreateResponse] Missing inReplyTo");
                            return $this->httpFediverseResponse->get(Response::HTTP_BAD_REQUEST);
                        case $createActivity::ACTIVITY_UNKNOWN_PROFILE:
                            Log::debug("[InboxCreateResponse] Unknown local profile");
                            return $this->httpFediverseResponse->get(Response::HTTP_NOT_FOUND);
                        case $createActivity::ACTIVITY_UNKNOWN_REVIEW:
                            Log::debug("[InboxCreateResponse] Unknown review");
                            return $this->httpFediverseResponse->get(Response::HTTP_NOT_FOUND);
                        default:
                            throw new \Exception($e);
                    }
                }
                return $this->httpFediverseResponse->get(Response::HTTP_OK);

            case Delete::class:
                try {
                    $objectActivity = $activity->get('object');
                    if($this->isTombstone($activity)) {
                        Log::debug("[InboxDeleteResponseBefore] Deleted: " . json_encode($objectActivity));
                        $deletedComment = Comment::where([
                            'profile_id' => $profile->id,
                            'source_url' => $objectActivity->id
                        ])->delete();
                        Log::debug("[InboxDeleteResponseAfter] Deleted: " . json_encode($deletedComment));
                    } else {
                        Profile::where(['remote_url' => $objectActivity])->delete();
                    }
                } catch(\Exception $e) {
                    switch($e->getMessage()) {
                        default:
                            throw new \Exception($e);
                    }
                }
                Log::debug("[InboxDeleteResponse] Deleted: " . json_encode($objectActivity));
                return $this->httpFediverseResponse->get(Response::HTTP_OK);
            case Follow::class:
                try {
                    $followActivity = new FollowActivity();
                    $followActivity->activity($activity);
                } catch(\Exception $e) {
                    switch($e->getMessage()) {
                        case $activityService::ACTIVITY_WRONG_TARGET:
                            Log::debug("[InboxFollowResponse] Wrong target");
                            return $this->httpFediverseResponse->get(Response::HTTP_NOT_FOUND);
                        default:
                            throw new \Exception($e);
                    }
                }
                return $this->httpFediverseResponse->get(Response::HTTP_OK);
            case Undo::class:
                try {
                    $undoActivity = new UndoActivity();
                    $undoActivity->activity($activity);
                } catch(\Exception $e) {
                    switch($e->getMessage()) {
                        case $activityService::ACTIVITY_WRONG_TARGET:
                            Log::debug("[InboxUndoResponse] Wrong target");
                            return $this->httpFediverseResponse->get(Response::HTTP_NOT_FOUND);
                        case $undoActivity::ACTIVITY_WRONG_OBJECT:
                            Log::debug("[InboxUndoResponse] Wrong object");
                            return $this->httpFediverseResponse->get(Response::HTTP_BAD_REQUEST);
                        case $undoActivity::ACTIVITY_UNKNOWN_FOLLOWER:
                            Log::debug("[InboxUndoResponse] Unknown follower");
                            return $this->httpFediverseResponse->get(Response::HTTP_OK);
                        default:
                            throw new \Exception($e);
                    }
                }
                return $this->httpFediverseResponse->get(Response::HTTP_OK);
            default:
                $this->logInvalidState("[InboxDefaultResponse] Unknown activity: " . $activity::class, $request);
                return $this->httpFediverseResponse->get(Response::HTTP_NOT_IMPLEMENTED);
        }

    }

     public function outbox(string $username): JsonResponse
     {
         abort_if(config('flox.federation.enabled') === false, 404);

         $profileBuilder = Profile::where('username', $username);
         switch($profileBuilder->count()) {
             case 0:
                 return $this->httpFediverseResponse->get(Response::HTTP_NOT_FOUND);
             case 1:
                 $outbox = (new OutboxCollection())->get($profileBuilder->first());
                 return $this->httpFediverseResponse->get(Response::HTTP_OK, $outbox->toArray());
             default:
                 return $this->httpFediverseResponse->get(Response::HTTP_INTERNAL_SERVER_ERROR);
         }
     }

    public function sharedInbox(Request $request): JsonResponse
    {
        abort_if(config('flox.federation.enabled') === false, 404);

        return $this->inbox($request, '');
    }

    private function logInvalidState(string $header, Request $request): void
    {
        Log::channel('federation-unknown')->debug($header);
        Log::channel('federation-unknown')->debug(str_replace('\/', '/', json_encode($request->all(), JSON_PRETTY_PRINT)));
    }

    private function isTombstone(Delete $activity): bool
    {
        $objectActivity = $activity->get('object');
        return is_object($objectActivity) && $objectActivity->type === 'Tombstone';
    }
}
