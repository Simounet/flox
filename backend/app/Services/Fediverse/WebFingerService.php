<?php

namespace App\Services\Fediverse;

use Illuminate\Support\Str;

class WebFingerService
{

    public const RESOURCE_PREFIX = 'acct:';

    public function webFingerObject(array $resource): object
    {
        $data = '{
            "subject": "' . self::RESOURCE_PREFIX . $resource['username'] . '@' . $resource['domain'] . '",
            "aliases": [' .
                '"' . route('federation.user', ['username' => $resource['username']]) . '"' .
            '],
            "links": [
                {
                    "rel": "self",
                    "type": "application/activity+json",
                    "href": "' . route('federation.user', ['username' => $resource['username']]) . '"
                }
            ]
        }';

        return json_decode($data);
    }

    public function resourceFromProfileUrl(string $url): array
    {
        if(!Str::of($url)->contains('@')) {
            return [];
        }

        if(Str::startsWith($url, self::RESOURCE_PREFIX)) {
            $url = str_replace(self::RESOURCE_PREFIX, '', $url);
        }

        if(Str::startsWith($url, '@')) {
            $url = substr($url, 1);
        }

        $parts = explode('@', $url);
        $username = $parts[0];
        $domain = $parts[1];

        return [
            'domain' => $domain,
            'username' => $username
        ];
    }
}
