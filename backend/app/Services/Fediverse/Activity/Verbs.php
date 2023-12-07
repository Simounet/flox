<?php

declare(strict_types=1);

namespace App\Services\Fediverse\Activity;

abstract class Verbs
{
    public const ACCEPT = 'Accept';
    public const CREATE = 'Create';
    public const DELETE = 'Delete';
    public const UPDATE = 'Update';
}
