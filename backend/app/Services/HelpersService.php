<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\UrlValidationException;

class HelpersService
{
    public function urlValidate(string $url): void
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

        $ips = $this->resolveHost($host);
        if ($ips === []) {
            throw new UrlValidationException('URL validation: wrong host resolving (host: ' . $host . ')');
        }

        array_walk($ips, function($ip) {
            if (!$this->isPublicIp($ip)) {
                throw new UrlValidationException('URL validation: not a public IP (ip: ' . $ip . ')');
            }
        });
    }

    /**
     * @return string[]
     */
    private function resolveHost(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = [];

        $recordsA = dns_get_record($host, DNS_A);
        foreach ($recordsA as $record) {
            if (isset($record['ip'])) {
                $ips[] = $record['ip'];
            }
        }

        $recordsAAAA = dns_get_record($host, DNS_AAAA);
        foreach ($recordsAAAA as $record) {
            if (isset($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return $ips;
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
