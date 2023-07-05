<?php

declare(strict_types=1);

namespace App\Services;

class HelpersService
{
    public function urlValidate(string $url): bool
    {
        if(mb_substr($url, 0, 8) !== 'https://' && mb_substr($url, 0, 7) !== 'http://' ) {
            return false;
        }

        return true;
    }
}
