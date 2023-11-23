<?php

namespace App\Services\Fediverse\Activity;

use ActivityPhp\Type\Extended\Actor\Person;
use ActivityPhp\Type\Extended\Object\Image;
use ActivityPhp\Type\TypeConfiguration;
use App\Profile;

class ActorActivity
{
    public const DEFAULT_PROFILE_AVATAR = '/assets/img/logo-small.png';

    public function actorObject(Profile $profile): Person
    {
        TypeConfiguration::set('undefined_properties', 'ignore');
        $avatarUrl = $profile->avatar_url ?? env('APP_URL') . self::DEFAULT_PROFILE_AVATAR;
        $icon = new Image();
        $icon->set('mediaType', 'image/jpg');
        $icon->set('url', $avatarUrl);

        $person = new Person();
        $person->set('@context', 'https://www.w3.org/ns/activitystreams');
        $person->set('id', $profile->remote_url);
        $person->set('name', $profile->name);
        $person->set('preferredUsername', $profile->username);
        $person->set('inbox', $profile->inbox_url);
        $person->set('outbox', $profile->outbox_url);
        $person->set('following', $profile->following_url);
        $person->set('followers', $profile->followers_url);
        $person->set('icon', $icon);
        $person->set('publicKey', [
            'id' => $profile->key_id_url,
            'owner' => $profile->remote_url,
            'publicKeyPem' => $profile->public_key
        ]);
        return $person;
    }
}
