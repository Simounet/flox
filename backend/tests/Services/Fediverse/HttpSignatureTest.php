<?php

declare(strict_types=1);

namespace Tests\Services\Fediverse;

use App\Services\Fediverse\HttpSignature;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class HttpSignatureTest extends TestCase
{
    private HttpSignature $httpSignature;

    public function setUp(): void
    {
        parent::setUp();

        $this->httpSignature = app(HttpSignature::class);
    }

    #[Test]
    #[DataProvider('validUrlProvider')]
    public function it_should_accept_valid_public_https_urls(string $url): void
    {
        $this->assertSame($url, $this->validateUrl($url));
    }

    public static function validUrlProvider(): array
    {
        return [
            'https public ip' => ['https://93.184.216.34/actor'],
        ];
    }

    #[Test]
    #[DataProvider('invalidUrlProvider')]
    public function it_should_reject_invalid_or_unsafe_urls(string $url): void
    {
        $this->assertFalse($this->validateUrl($url));
    }

    public static function invalidUrlProvider(): array
    {
        return [
            'ftp scheme' => ['ftp://93.184.216.34/actor'],
            'double scheme' => ['https://evil.example/redirect?url=https://93.184.216.34'],
            'malformed url' => ['https:///no-host'],
            'loopback ip' => ['https://127.0.0.1/actor'],
            'loopback ipv6' => ['https://[::1]/actor'],
            'localhost hostname' => ['https://localhost/actor'],
            'private class a' => ['https://10.0.0.1/actor'],
            'private class b' => ['https://172.16.0.1/actor'],
            'private class c' => ['https://192.168.0.1/actor'],
            'link local / cloud metadata' => ['https://169.254.169.254/latest/meta-data'],
            'unspecified ip' => ['https://0.0.0.0/actor'],
        ];
    }

    #[Test]
    public function it_should_reject_plain_http_urls(): void
    {
        $this->assertFalse($this->validateUrl('http://93.184.216.34/actor'));
    }

    #[Test]
    public function it_should_cache_the_validation_result(): void
    {
        $url = 'https://93.184.216.34/actor';

        $this->assertSame($url, $this->validateUrl($url));
    }

    #[Test]
    public function it_should_verify_when_all_claimed_headers_are_present(): void
    {
        [$publicKey, $signature, $inputHeaders] = $this->makeSignedRequestKeyPair();

        $signatureData = [
            'headers' => '(request-target) date',
            'signature' => $signature,
        ];

        [$verified] = $this->verify($publicKey, $signatureData, $inputHeaders, 'post', '/inbox', '');

        $this->assertSame(1, $verified);
    }

    #[Test]
    public function it_should_fail_closed_when_a_claimed_header_is_missing_from_the_request(): void
    {
        [$publicKey, $signature, $inputHeaders] = $this->makeSignedRequestKeyPair();

        $signatureData = [
            'headers' => '(request-target) date x-custom',
            'signature' => $signature,
        ];

        [$verified] = $this->verify($publicKey, $signatureData, $inputHeaders, 'post', '/inbox', '');

        $this->assertNotSame(1, $verified);
    }

    private function validateUrl(string $url): string|false
    {
        $method = new ReflectionMethod(HttpSignature::class, 'validateUrl');
        $method->setAccessible(true);

        return $method->invoke($this->httpSignature, $url);
    }

    /**
     * @return array{0: mixed, 1: string}
     */
    private function verify(
        \OpenSSLAsymmetricKey $publicKey,
        array $signatureData,
        array $inputHeaders,
        string $requestMethod,
        string $path,
        string $body
    ): array {
        $reflectionMethod = new ReflectionMethod(HttpSignature::class, 'verify');
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invoke(
            $this->httpSignature,
            $publicKey,
            $signatureData,
            $inputHeaders,
            $requestMethod,
            $path,
            $body
        );
    }

    /**
     * @return array{0: mixed, 1: string, 2: array<string, array<int, string>>}
     */
    private function makeSignedRequestKeyPair(): array
    {
        $keyPair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $privateKey = openssl_pkey_get_private($keyPair);
        $publicKeyDetails = openssl_pkey_get_details($keyPair);
        $publicKey = openssl_pkey_get_public($publicKeyDetails['key']);

        $signingString = "(request-target): post /inbox\ndate: Mon, 01 Sep 2026 00:00:00 GMT";
        openssl_sign($signingString, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $inputHeaders = [
            'date' => ['Mon, 01 Sep 2026 00:00:00 GMT'],
        ];

        return [$publicKey, base64_encode($signature), $inputHeaders];
    }
}
