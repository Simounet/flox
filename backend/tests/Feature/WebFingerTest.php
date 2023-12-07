<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebFingerTest extends TestCase
{

    use RefreshDatabase;

    private $host;
    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->host = parse_url(env('APP_URL'))['host'];
        $this->withHeader('Host', $this->host);
        $this->user = factory(User::class)->create();
    }

    /** @test */
    public function shouldReturnResourceOk(): void
    {
        $actor = $this->user->username . '@' . $this->host;
        $url = '/.well-known/webfinger?resource=acct:' . $actor;
        $expectedJson = '{"subject":"acct:' . $actor . '","links":[{"rel":"self","type":"application\/activity+json","href":"https:\/\/flox.dev\/user\/prof.amyacollinsiii"}]}';
        $response = $this->get($url);
        $response->assertStatus(200);
        $this->assertJson($expectedJson, json_encode($response->json()));
    }

    /** @test */
    public function shouldReturn404OnWrongUser(): void
    {
        $response = $this->get('/.well-known/webfinger?resource=acct:simon@test.com');
        $response->assertStatus(404);
    }

    /** @test */
    public function shouldReturn400OnResourceMissing(): void
    {
        $response = $this->get('/.well-known/webfinger');
        $response->assertStatus(400);
    }
}
