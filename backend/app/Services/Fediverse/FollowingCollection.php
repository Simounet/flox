<?php

namespace App\Services\Fediverse;

use ActivityPhp\Type;
use ActivityPhp\Type\Core\OrderedCollection;
use App\Profile;

class FollowingCollection
{
    public function get(Profile $profile): OrderedCollection
    {
        $following = Type::create('OrderedCollection');
        $following->set('@context', 'https://www.w3.org/ns/activitystreams');
        $following->set('id', $profile->following_url);
        $following->set('totalItems', $profile->following()->count());
        echo '<pre>' . print_r( $following, true ) . '</pre>'; exit;
        return $following;
    }
}
