<?php

declare(strict_types=1);

namespace App\Services\Fediverse\Activity;

use ActivityPhp\Type\Core\ObjectType;
use ActivityPhp\Type\Extended\Activity\Accept;
use ActivityPhp\Type\Extended\Activity\Create;
use ActivityPhp\Type\Extended\Activity\Delete;
use ActivityPhp\Type\Extended\Activity\Update;
use App\Services\Fediverse\Activity\Verbs;

class Activity
{
    private $activity;

    public function __construct(string $type)
    {
        switch($type) {
            case Verbs::ACCEPT:
                $this->activity = new Accept();
                break;
            case Verbs::CREATE:
                $this->activity = new Create();
                break;
            case Verbs::DELETE:
                $this->activity = new Delete();
                break;
            case Verbs::UPDATE:
                $this->activity = new Update();
                break;
            default:
                throw new \Exception('Wrong verb: ' . $type);

        }
    }

    public function activity(string $activityId, string $actorUrl, ObjectType $object): Accept|Create|Delete|Update
    {
        $this->activity->set('@context', 'https://www.w3.org/ns/activitystreams');
        $this->activity->set('id', $activityId);
        $this->activity->set('actor', $actorUrl);
        $this->activity->set('object', $object);
        return $this->activity;
    }
}
