<?php

namespace Tests\Services\Fediverse;

use App\Services\Fediverse\WebFingerService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebFingerServiceTest extends TestCase
{

    private $webFingerService;


    public function setUp(): void
    {
        parent::setUp();

        $this->webFingerService = new WebFingerService();
    }

    #[Test]
    public function shouldReturnResourceFromProfileUrl(): void
    {
        $username = 'test';
        $host = 'flox.dev';
        $expectedResource = [
            'domain' => $host,
            'username' => $username
        ];
        $resourceStr = 'acct:' . $username . '@' . $host;
        $resource = $this->webFingerService->resourceFromProfileUrl($resourceStr);

        $this->assertEquals($expectedResource, $resource);
    }

    #[Test]
    public function shouldReturnEmptyResourceOnEmptyInfo(): void
    {
        $expectedResource = [];
        $resourceStr = '';
        $resource = $this->webFingerService->resourceFromProfileUrl($resourceStr);

        $this->assertEquals($expectedResource, $resource);
    }
}
