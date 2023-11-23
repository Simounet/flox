<?php

declare(strict_types=1);

namespace App\Services\Fediverse\Activity;

use ActivityPhp\Type\Extended\Activity\Delete;
use App\Profile;

class DeleteActivity
{
    public function activity(Delete $deleteActivity): void
    {
        Profile::where(['remote_url' => $deleteActivity->get('object')])->delete();
    }
}
