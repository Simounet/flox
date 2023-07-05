<?php

namespace App\Services\Fediverse\Activity;

use ActivityPhp\Type;
use ActivityPhp\Type\Extended\Activity\Accept;
use ActivityPhp\Type\Extended\Activity\Follow;

class AcceptActivity
{

    public function activity(
        string $acceptId,
        Follow $followActivity
    ): Accept
    {
        $accept = Type::create('Accept');
        $accept->set('@context', 'https://www.w3.org/ns/activitystreams');
        $accept->set('id', $acceptId);
        $accept->set('actor', $followActivity->object);
        $accept->set('object', $followActivity);
        return $accept;
    }
}
