<?php

declare(strict_types=1);

namespace App\Services\Fediverse;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OpenSSLAsymmetricKey;
use Symfony\Component\HttpFoundation\HeaderBag;
use \DateTime;

class HttpSignature {

    public const ACCEPT_HEADER = 'application/ld+json; profile="https://www.w3.org/ns/activitystreams", application/activity+json, application/ld+json, application/json';
    private const AUTHORIZED_ALGORITHMS = ['rsa-sha256'];

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
        $toSignString = $this->headersToSigningString($headers);
        $this->log('[' . self::class . '] string to sign: ' . $toSignString);

        $key = openssl_pkey_get_private($privateKey);
        openssl_sign(
            $toSignString,
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

    public function parseSignatureHeader(string $signature): array
    {
        $parts = explode(',', $signature);
        $signatureData = [];

        foreach($parts as $part) {
            if(preg_match('/(.+)="(.+)"/', $part, $match)) {
                $signatureData[$match[1]] = $match[2];
            }
        }

        if(!in_array(strtolower($signatureData['algorithm']), self::AUTHORIZED_ALGORITHMS)) {
            return [
                'error' => 'Unsupported signature algorithm (only ' . implode(',', self::AUTHORIZED_ALGORITHMS) . ' are supported). Found: ' . $signatureData['algorithm']
            ];
        }
        if(!isset($signatureData['keyId'])) {
            return [
                'error' => 'No keyId was found in the signature header. Found: '.implode(', ', array_keys($signatureData))
            ];
        }

        if(!filter_var($signatureData['keyId'], FILTER_VALIDATE_URL)) {
            return [
                'error' => 'keyId is not a URL: '.$signatureData['keyId']
            ];
        }

        if(!isset($signatureData['headers']) || !isset($signatureData['signature'])) {
            return [
                'error' => 'Signature is missing headers or signature parts'
            ];
        }

        return $signatureData;
    }

    public function verifySignature(
        string $method,
        string $path,
        HeaderBag $headers,
        Profile $profile,
        string $payload
    ): bool
    {
        $message = json_decode($payload, true, 8);
        $this->log('[' . self::class . '] message id: ' . $message['id']);
        if(!isset($message['id'])) {
            $this->log('[' . self::class . '] No message id provided on request');
            return false;
        }

        $signature = $headers->get('signature', null);
        $this->log('[' . self::class . '] signature: ' . $signature);
        if(!$signature) {
            $this->log('[' . self::class . '] No signature provided on request');
            return false;
        }

        $date = $headers->get('date', null);
        $this->log('[' . self::class . '] date: ' . $date);
        if(!$date) {
            $this->log('[' . self::class . '] No date provided on request');
            return false;
        }
        $now = now();
        $parsedDate = $now->parse($date);
        if(!$parsedDate->gt($now->subDays(1)) ||
           !$parsedDate->lt($now->addDays(1))
        ) {
            $this->log('[' . self::class . '] Wrong date provided');
            return false;
        }

        $signatureData = $this->parseSignatureHeader($signature);

        if(isset($signatureData['error'])) {
            $this->log('[' . self::class . '] Signature error: ' . $signatureData['error']);
            return false;
        }

        $keyId = $this->validateUrl($signatureData['keyId']);
        if(!$keyId) {
            $this->log('[' . self::class . '] Wrong keyId: ' . $keyId);
            return false;
        }

        $id = $this->validateUrl($message['id']);
        if(!$id) {
            $this->log('[' . self::class . '] Wrong message id: ' . $message['id']);
            return false;
        }

        $keyDomain = parse_url($keyId, PHP_URL_HOST);
        $idDomain = parse_url($id, PHP_URL_HOST);
        if(isset($message['object'])
            && is_array($message['object'])
            && isset($message['object']['attributedTo'])
        ) {
            $attributedTo = $message['object']['attributedTo'] ?? '';

            if(parse_url($attributedTo, PHP_URL_HOST) !== $keyDomain) {
                $this->log('[' . self::class . '] Wrong domain: ' . parse_url($attributedTo, PHP_URL_HOST) . ' !== ' . $keyDomain);
                return false;
            }
        }
        if(!$keyDomain || !$idDomain || $keyDomain !== $idDomain) {
            $this->log('[' . self::class . '] Missing or not equalse keyDomain: ' . $keyDomain . ' - idDomain: ' . $idDomain);
            return false;
        }
        $actor = (new ActivityPubFetchService())->get($message['actor']);
        if(!$actor) {
            $this->log('[' . self::class . '] Wrong actor');
            return false;
        }
        $pkey = openssl_pkey_get_public($actor->publicKey['publicKeyPem']);
        if(!$pkey) {
            $this->log('[' . self::class . '] Wrong public key pem');
            return false;
        }
        list($verified, $headers) = $this->verify($pkey, $signatureData, $headers->all(), $method, $path, $payload);
        $this->log('[' . self::class . '] verified: ' . ($verified ? 'yes' : 'no' ));
        return $verified === 1;
    }

    private function validateUrl(string $url): string|false
    {
        $hash = hash('sha256', $url);
        $key = "helpers:url:valid:sha256-{$hash}";

        $valid = Cache::remember($key, 900, function() use($url) {
            if(strtolower(mb_substr($url, 0, 8)) !== 'https://') {
                return false;
            }

            if(substr_count($url, '://') !== 1) {
                return false;
            }

            if(mb_substr($url, 0, 8) !== 'https://') {
                $url = 'https://' . substr($url, 8);
            }

            $valid = filter_var($url, FILTER_VALIDATE_URL);

            if(!$valid) {
                return false;
            }

            $localhosts = [
                '127.0.0.1', 'localhost', '::1'
            ];
            $host = parse_url($valid, PHP_URL_HOST);

            if(in_array($host, $localhosts)) {
                return false;
            }

            return $url;
        });

        return $valid;
    }

    private function verify(
        OpenSSLAsymmetricKey $publicKey,
        array $signatureData,
        array $inputHeaders,
        string $method,
        string $path,
        string $body
    ): array
    {
        $digest = 'SHA-256=' . $this->digest($body);
        $headersToSign = [];
        foreach(explode(' ',$signatureData['headers']) as $h) {
            if($h === '(request-target)') {
                $headersToSign[$h] = strtolower($method) . ' ' . $path;
            } elseif($h === 'digest') {
                $headersToSign[$h] = $digest;
            } elseif(isset($inputHeaders[$h][0])) {
                $headersToSign[$h] = $inputHeaders[$h][0];
            }
        }
        $signingString = self::headersToSigningString($headersToSign);
        $this->log('[' . self::class . '] verify signatureData: ' . json_encode($signatureData, JSON_UNESCAPED_SLASHES));
        $this->log('[' . self::class . '] verify inputHeader: ' . json_encode($inputHeaders, JSON_UNESCAPED_SLASHES));
        $this->log('[' . self::class . '] verify signing string: ' . $signingString);

        $verified = openssl_verify($signingString, base64_decode($signatureData['signature']), $publicKey, OPENSSL_ALGO_SHA256);

        return [$verified, $signingString];
    }

    private function log(string $message): void
    {
        Log::channel('federation')->debug($message);
    }
}
