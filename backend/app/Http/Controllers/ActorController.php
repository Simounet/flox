<?php

namespace App\Http\Controllers;

use App\Services\Fediverse\Activity\ActorActivity;
use App\User;
use Illuminate\Http\Request;

class ActorController
{
    public function actor(Request $request, string $username)
    {
        $user = User::firstWhere('username', $username);
        if($user === null) {
            return response('', 404);
        }
        $person = (new ActorActivity)->actorObject($username);

        return response()->json($person->toArray(), 200, [], JSON_UNESCAPED_SLASHES)
            ->header('Access-Control-Allow-Origin', '*');
    }

    public function followers()
    {
    }

    public function following()
    {
    }

    public function inbox()
    {
    }

    public function outbox()
    {
    }
}
