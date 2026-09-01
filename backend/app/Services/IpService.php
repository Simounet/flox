<?php

declare(strict_types=1);

namespace App\Services;

class IpService
{
    public function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    /**
     * @return string[]
     */
    public function resolveHost(string $host): array
    {
        $host = trim($host, '[]');

        if(filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        return app(self::class)->hostToIps($host);
    }

    /**
     * @return string[]
     */
    protected function hostToIps(string $host): array
    {
        $recordsA = dns_get_record($host, DNS_A);
        $ipsV4 = $this->ipsFilter($recordsA, 'ip');

        $recordsAAAA = dns_get_record($host, DNS_AAAA);
        $ipsV6 = $this->ipsFilter($recordsAAAA, 'ipv6');

        return array_merge($ipsV4, $ipsV6);
    }

    /**
     * @return string[]
     */
    private function ipsFilter(array $records, string $field): array
    {
        return array_column(
            array_filter($records, fn ($record) => isset($record[$field])),
            $field
        );
    }
}
