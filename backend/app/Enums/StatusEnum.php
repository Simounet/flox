<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusEnum
{
    case OK;
    case NOT_IMPLEMENTED;
    case NOT_FOUND;
    case UNAUTHORIZED;
}
