<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Exceptions\UrlValidationException;
use App\Services\UrlService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UrlServiceTest extends TestCase
{
    private UrlService $urlService;

    public function setUp(): void
    {
        parent::setUp();

        $this->urlService = app(UrlService::class);
    }

    #[Test]
    #[DataProvider('validUrlProvider')]
    public function it_should_accept_valid_public_urls(string $url): void
    {
        $this->expectNotToPerformAssertions();
        $this->urlService->validate($url);
    }

    public static function validUrlProvider(): array
    {
        return [
            'https public ip' => ['https://93.184.216.34/inbox'],
            'http public ip' => ['http://93.184.216.34/inbox'],
        ];
    }

    #[Test]
    #[DataProvider('invalidUrlProvider')]
    public function it_should_reject_invalid_or_unsafe_urls(string $url): void
    {
        $this->expectException(UrlValidationException::class);
        $this->urlService->validate($url);
    }

    public static function invalidUrlProvider(): array
    {
        return [
            'no scheme' => ['93.184.216.34/inbox'],
            'ftp scheme' => ['ftp://93.184.216.34/inbox'],
            'double scheme' => ['https://evil.example/redirect?url=https://93.184.216.34'],
            'malformed url' => ['https:///no-host'],
            'loopback ip' => ['https://127.0.0.1/inbox'],
            'loopback ipv6' => ['https://[::1]/inbox'],
            'localhost hostname' => ['https://localhost/inbox'],
            'private class a' => ['https://10.0.0.1/inbox'],
            'private class b' => ['https://172.16.0.1/inbox'],
            'private class c' => ['https://192.168.0.1/inbox'],
            'link local / cloud metadata' => ['https://169.254.169.254/latest/meta-data'],
            'unspecified ip' => ['https://0.0.0.0/inbox'],
        ];
    }
}
