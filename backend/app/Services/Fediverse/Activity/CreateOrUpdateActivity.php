<?php

declare(strict_types=1);

namespace App\Services\Fediverse\Activity;

use ActivityPhp\Type\Core\ObjectType;
use ActivityPhp\Type\Extended\Activity\Create;
use ActivityPhp\Type\Extended\Activity\Update;
use App\Services\Fediverse\Activity\Verbs;

class CreateOrUpdateActivity
{
    private $activity;

    public function __construct(string $verb)
    {
        if(
            $verb !== Verbs::CREATE
            && $verb !== Verbs::UPDATE
        ) {
            throw new \Exception('Wrong verb: ' . $verb);
        }
        $this->activity = $verb === Verbs::UPDATE ?
            new Update() : new Create();
    }

    public function activity(string $activityId, string $actorUrl, ObjectType $object): Create|Update
    {
        $this->activity->set('@context', 'https://www.w3.org/ns/activitystreams');
        $this->activity->set('id', $activityId);
        $this->activity->set('actor', $actorUrl);
        $this->activity->set('object', $object);
        return $this->activity;
    }
}
