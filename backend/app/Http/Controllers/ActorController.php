<?php

namespace App\Http\Controllers;

use ActivityPhp\Type;
use ActivityPhp\Type\Extended\Activity\Follow;
use ActivityPhp\Type\Extended\Activity\Undo;
use ActivityPhp\Type\TypeConfiguration;
use App\Profile;
use App\Services\Fediverse\Activity\ActivityService;
use App\Services\Fediverse\Activity\ActorActivity;
use App\Services\Fediverse\Activity\UndoActivity;
use App\Services\Fediverse\Activity\FollowActivity;
use App\Services\Fediverse\FollowingCollection;
use App\Services\Fediverse\FollowersCollection;
use App\Services\Fediverse\HttpSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActorController
{
    public function actor(string $username)
    {
        $profile = (new Profile())->whereLocalProfile($username);
        if($profile === null) {
            return response('', 404);
        }
        $person = (new ActorActivity())->actorObject($profile);

        return response()->json($person->toArray(), 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function followers(string $username)
    {
        $profileBuilder = Profile::where('username', $username);
        switch($profileBuilder->count()) {
            case 0:
                return response('', 404);
            case 1:
                $followers = (new FollowersCollection())->get($profileBuilder->first());
                return response()->json($followers->toArray(), 200, [], JSON_UNESCAPED_SLASHES)
                    ->header('Access-Control-Allow-Origin', '*');
            default:
                return response('', 500);
        }
    }

    public function following(string $username)
    {
        $profileBuilder = Profile::where('username', $username);
        switch($profileBuilder->count()) {
            case 0:
                return response('', 404);
            case 1:
                $followings = (new FollowingCollection())->get($profileBuilder->first());
                return response()->json($followings->toArray(), 200, [], JSON_UNESCAPED_SLASHES)
                    ->header('Access-Control-Allow-Origin', '*');
            default:
                return response('', 500);
        }
    }

    public function inbox(Request $request, string $username)
    {
        $payload = $request->getContent();
        Log::debug("[InboxRequest]", $request->all());
        try {
            TypeConfiguration::set('undefined_properties', 'ignore');
            $activity = Type::fromJson($payload);
        } catch(\Exception $e) {
            return response('', 400);
        }
        Log::debug("[InboxActivity] " . $activity::class . ': ' . $activity->toJson(JSON_UNESCAPED_SLASHES));
        $activityService = new ActivityService();

        $headers = $request->headers;
        Log::debug("[InboxRequestHeaders] " . (string) $headers);
        $verifiedSignature = (new HttpSignature())->verifySignature($request->getMethod(),  $request->getPathInfo(), $headers, $payload);
        if(!$verifiedSignature) {
            Log::debug("[InboxActivity] Wrong signature");
            return response('', 401);
        }

        switch($activity::class) {
            case Follow::class:
                try {
                    $followActivity = new FollowActivity();
                    $followActivity->activity($activity);
                } catch(\Exception $e) {
                    switch($e->getMessage()) {
                        case $activityService::ACTIVITY_WRONG_TARGET:
                            Log::debug("[InboxFollowResponse] Wrong target");
                            return response('', 404);
                        default:
                            throw new \Exception($e);
                    }
                }
                return response()->json('', 200, [], JSON_UNESCAPED_SLASHES)
                    ->header('Access-Control-Allow-Origin', '*');
            case Undo::class:
                try {
                    $undoActivity = new UndoActivity();
                    $undoActivity->activity($activity);
                } catch(\Exception $e) {
                    switch($e->getMessage()) {
                        case $activityService::ACTIVITY_WRONG_TARGET:
                            Log::debug("[InboxUndoResponse] Wrong target");
                            return response('', 404);
                        case $undoActivity::ACTIVITY_WRONG_OBJECT:
                            Log::debug("[InboxUndoResponse] Wrong object");
                            return response('', 400);
                        case $undoActivity::ACTIVITY_UNKNOWN_FOLLOWER:
                            Log::debug("[InboxUndoResponse] Unknown follower");
                            return response()->json('', 200, [], JSON_UNESCAPED_SLASHES)
                                ->header('Access-Control-Allow-Origin', '*');
                        default:
                            throw new \Exception($e);
                    }
                }
                return response()->json('', 200, [], JSON_UNESCAPED_SLASHES)
                    ->header('Access-Control-Allow-Origin', '*');
            default:
                Log::debug("[InboxDefaultResponse] Unknown activity: " . $activity::class);
                return response('', 501);
        }

    }

    public function outbox()
    {
    }

    public function sharedInbox(Request $request)
    {
        return $this->inbox($request, '');
    }
}
