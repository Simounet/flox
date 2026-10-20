<?php

declare(strict_types=1);

namespace App\Services\Fediverse\Activity;

use ActivityPhp\Type\Extended\Actor\Person;
use ActivityPhp\Type\Extended\Object\Image;
use ActivityPhp\Type\TypeConfiguration;
use App\Models\Profile;

class ActorActivity
{
    public const DEFAULT_PROFILE_AVATAR = '/assets/img/logo-small.png';

    public function actorObject(Profile $profile): Person
    {
        TypeConfiguration::set('undefined_properties', 'ignore');
        // @TODO avatar handling on Flox side ($profile->avatar_url already available on ActivityPub side)
        $avatarUrl = config('app.url') . '/' . ($profile->avatar_url ?? self::DEFAULT_PROFILE_AVATAR);
        $icon = new Image();
        // @TODO $profile->avatar_url should change the mimetype
        $icon->set('mediaType', 'image/png');
        $icon->set('url', $avatarUrl);

        $person = new Person();
        $person->set('@context', ['https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1']);
        $person->set('id', $profile->remote_url);
        $person->set('url', $profile->remote_url);
        $person->set('name', $profile->name);
        $person->set('published', $profile->created_at->toIso8601ZuluString());
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

        $person->set('endpoints', (object) [
            'sharedInbox' => $profile->shared_inbox_url
        ]);
        return $person;
    }
}
