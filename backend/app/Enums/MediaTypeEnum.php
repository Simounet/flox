<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaTypeEnum: string {
    case ALL = '';
    case MOVIE = 'movie';
    case TV = 'tv';
}
