<?php

namespace Tests\Services\Fediverse;

use App\Services\Fediverse\WebFingerService;
use Tests\TestCase;

class WebFingerServiceTest extends TestCase
{

    private $webFingerService;


    public function setUp(): void
    {
        parent::setUp();

        $this->webFingerService = new WebFingerService();
    }

    /** @test */
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

    /** @test */
    public function shouldReturnEmptyResourceOnEmptyInfo(): void
    {
        $expectedResource = [];
        $resourceStr = '';
        $resource = $this->webFingerService->resourceFromProfileUrl($resourceStr);

        $this->assertEquals($expectedResource, $resource);
    }
}
