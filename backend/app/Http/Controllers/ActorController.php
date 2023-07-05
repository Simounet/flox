<?php

namespace App\Http\Controllers;

use ActivityPhp\Type;
use ActivityPhp\Type\Extended\Activity\Follow;
use App\Profile;
use App\Services\Fediverse\Activity\ActorActivity;
use App\Services\Fediverse\Activity\FollowActivity;
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

    public function followers()
    {
    }

    public function following()
    {
    }

    public function inbox(Request $request)
    {
        $payload = $request->getContent();
        $activity = Type::fromJson($payload);

        switch($activity::class) {
            case Follow::class:
                try {
                    $followActivity = new FollowActivity();
                    $accept = $followActivity->activity($activity);
                } catch(\Exception $e) {
                    switch($e->getMessage()) {
                        case $followActivity::ACTIVITY_ALREADY_PROCESSED:
                            return response()->json(['message' => 'Already processed.'], 200);
                        case $followActivity::ACTIVITY_WRONG_TARGET:
                            return response('', 404);
                        default:
                            return response($e->getMessage(), 500);
                    }
                }
                return response()->json($accept->toArray(), 200, [], JSON_UNESCAPED_SLASHES)
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
