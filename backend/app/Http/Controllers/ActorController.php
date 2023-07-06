<?php

namespace App\Http\Controllers;

use ActivityPhp\Type;
use ActivityPhp\Type\Extended\Activity\Follow;
use ActivityPhp\Type\Extended\Activity\Undo;
use App\Profile;
use App\Services\Fediverse\Activity\ActivityService;
use App\Services\Fediverse\Activity\ActorActivity;
use App\Services\Fediverse\Activity\UndoActivity;
use App\Services\Fediverse\Activity\FollowActivity;
use App\Services\Fediverse\FollowingCollection;
use App\Services\Fediverse\FollowersCollection;
use Illuminate\Http\Request;

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

    public function inbox(Request $request)
    {
        $payload = $request->getContent();
        try {
            $activity = Type::fromJson($payload);
        } catch(\Exception $e) {
            return response('', 400);
        }
        $activityService = new ActivityService();

        switch($activity::class) {
            case Follow::class:
                try {
                    $followActivity = new FollowActivity();
                    $accept = $followActivity->activity($activity);
                } catch(\Exception $e) {
                    switch($e->getMessage()) {
                        case $followActivity::ACTIVITY_ALREADY_PROCESSED:
                            return response()->json(['message' => 'Already processed.'], 200);
                        case $activityService::ACTIVITY_WRONG_TARGET:
                            return response('', 404);
                        default:
                            return response($e->getMessage(), 500);
                    }
                }
                return response()->json($accept->toArray(), 200, [], JSON_UNESCAPED_SLASHES)
                    ->header('Access-Control-Allow-Origin', '*');
            case Undo::class:
                try {
                    $undoActivity = new UndoActivity();
                    $undoActivity->activity($activity);
                } catch(\Exception $e) {
                    switch($e->getMessage()) {
                        case $activityService::ACTIVITY_WRONG_TARGET:
                            return response('', 404);
                        case $undoActivity::ACTIVITY_WRONG_OBJECT:
                            return response('', 400);
                        case $undoActivity::ACTIVITY_UNKNOWN_FOLLOWER:
                            return response()->json('', 200, [], JSON_UNESCAPED_SLASHES)
                                ->header('Access-Control-Allow-Origin', '*');
                        default:
                            return response($e->getMessage(), 500);
                    }
                }
                return response()->json('', 200, [], JSON_UNESCAPED_SLASHES)
                    ->header('Access-Control-Allow-Origin', '*');
            default:
                return response('', 501);
        }

    }

    public function outbox()
    {
    }

    public function sharedInbox()
    {
    }
}
