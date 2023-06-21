<?php

namespace App\Services\Fediverse\Activity;

use ActivityPhp\Type\Extended\Actor\Person;

class ActorActivity
{
    public function actorObject(string $username): Person
    {
        $userRoute = route('federation.user', ['username' => $username]);

        $publicKey = [
            'id' => $userRoute . '#main-key',
            'owner' => $userRoute,
            'publicKeyPem' => '@TODO'
        ];

        $person = new Person();
        $person->set('@context', 'https://www.w3.org/ns/activitystreams');
        $person->set('id', $userRoute);
        $person->set('outbox', route('federation.user.outbox', ['username' => $username]));
        $person->set('following', route('federation.user.following', ['username' => $username]));
        $person->set('followers', route('federation.user.followers', ['username' => $username]));
        $person->set('inbox', route('federation.user.inbox', ['username' => $username]));
        $person->set('preferredUsername', $username);
        $person->set('name', $username);
        $person->set('publicKey', $publicKey);
        return $person;
    }
}
