<?php

namespace App\Services\Fediverse;

use ActivityPhp\Type;
use ActivityPhp\Type\Core\OrderedCollection;
use App\Profile;

class FollowersCollection
{
    public function get(Profile $profile): OrderedCollection
    {
        $followers = Type::create('OrderedCollection');
        $followers->set('@context', 'https://www.w3.org/ns/activitystreams');
        $followers->set('id', $profile->followers_url);
        $followers->set('totalItems', $profile->followers()->count());
        return $followers;
    }
}
