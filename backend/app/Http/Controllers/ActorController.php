<?php

namespace App\Http\Controllers;

use ActivityPhp\Type;
use ActivityPhp\Type\Ontology;
use App\Follower;
use App\Profile;
use App\Services\Fediverse\ActivityPubFetchService;
use App\Services\Fediverse\Activity\ActorActivity;
use App\Services\Models\ProfileService;
use App\User;
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

    public function inbox()
    {
    }

    public function outbox()
    {
    }
}
