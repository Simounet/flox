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

    private function validateUrl(string $url): string|false
    {
        $method = new ReflectionMethod(HttpSignature::class, 'validateUrl');
        $method->setAccessible(true);

        return $method->invoke($this->httpSignature, $url);
    }

}
