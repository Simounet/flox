<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\UrlValidationException;

class UrlService
{
    public function validate(string $url): void
    {
        $lowerCasedUrl = strtolower($url);
        if (mb_substr($lowerCasedUrl, 0, 8) !== 'https://' && mb_substr($lowerCasedUrl, 0, 7) !== 'http://') {
            throw new UrlValidationException('URL validation: wrong protocol ' . $url);
        }

        if (substr_count($url, '://') !== 1) {
            throw new UrlValidationException('URL validation: wrong separator ' . $url);
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new UrlValidationException('URL validation: filter validate url failed ' . $url);
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            throw new UrlValidationException('URL validation: wrong host (url: ' . $url . ' / host: ' . $host . ')');
        }

        $ips = (new IpService())->resolveHost($host);
        if ($ips === []) {
            throw new UrlValidationException('URL validation: wrong host resolving (host: ' . $host . ')');
        }

        array_walk($ips, function($ip) {
            if (!(new IpService())->isPublicIp($ip)) {
                throw new UrlValidationException('URL validation: not a public IP (ip: ' . $ip . ')');
            }
        });
    }
}
