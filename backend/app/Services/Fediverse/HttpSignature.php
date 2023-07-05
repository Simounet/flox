<?php

declare(strict_types=1);

namespace App\Services\Fediverse;

use \DateTime;

class HttpSignature {

    public const ACCEPT_HEADER = 'application/ld+json; profile="https://www.w3.org/ns/activitystreams", application/activity+json, application/ld+json, application/json';

    public function sign(string $url, string $privateKey, $keyId, string $body): array
    {
        $method = 'post';
        $headers = $this->headers($url, $method, $privateKey, $keyId, $body);

        return $headers;
    }

    public function instanceActorSign(string $url, string $privateKey, string $keyId): array
    {
        $method = 'get';
        $headers = $this->headers($url, $method, $privateKey, $keyId);

        return $headers;
    }

    private function headers(string $url, string $method, string $privateKey, string $keyId, $body = '')
    {
        $digest = empty($body) ? '' : $this->digest($body);
        $headers = $this->headersToSign($url, $digest, $method);

        $key = openssl_pkey_get_private($privateKey);
        openssl_sign(
            $this->headersToSigningString($headers),
            $signature,
            $key,
            OPENSSL_ALGO_SHA256
        );

        $signedHeaders = implode(' ', array_map('strtolower', array_keys($headers)));
        unset($headers['(request-target)']);
        $headers['Signature'] = 'keyId="' . $keyId . '",headers="' . $signedHeaders . '",algorithm="rsa-sha256",signature="' . base64_encode($signature) . '"';
        $headers['User-Agent'] = 'FloxBot/1.0.0 (Flox/'.config('app.version').'; +'.config('app.url').')';
        $headers['Content-Type'] = 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"';
        return $headers;
    }

    private function headersToSigningString($headers) {
        return implode("\n", array_map(function($key, $value){
            return strtolower($key) . ': ' . $value;
        }, array_keys($headers), $headers));
    }

    private function digest($body) {
        if(is_array($body)) {
            $body = json_encode($body);
        }
        return base64_encode(hash('sha256', $body, true));
    }

    protected function headersToSign(string $url, string $digest, string $method): array
    {
        $date = new DateTime('UTC');

        if(!in_array($method, ['post', 'get'])) {
            throw new \Exception('Invalid method used to sign headers in HttpSignature');
        }
        $headers = [
            '(request-target)' => $method . ' ' . parse_url($url, PHP_URL_PATH),
            'Accept' => self::ACCEPT_HEADER,
            'Host' => parse_url($url, PHP_URL_HOST),
            'Date' => $date->format('D, d M Y H:i:s \G\M\T'),
        ];

        if(!empty($digest)) {
            $headers['Digest'] = 'SHA-256=' . $digest;
        }

        return $headers;
    }

}
