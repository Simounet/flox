<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActorTest extends TestCase
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
        $username = $this->user->username;
        $url = '/users/' . $username;
        $expectedJson = '{"type":"Person","streams":[],"@context":"https:\/\/www.w3.org\/ns\/activitystreams","id":"https:\/\/flox.dev\/users\/' . $username . '","outbox":"https:\/\/flox.dev\/users\/' . $username . '\/outbox","following":"https:\/\/flox.dev\/users\/' . $username . '\/following","followers":"https:\/\/flox.dev\/users\/' . $username . '\/followers","inbox":"https:\/\/flox.dev\/users\/' . $username . '\/inbox","preferredUsername":"' . $username . '","name":"' . $username . '","publicKey":{"id":"https:\/\/flox.dev\/users\/' . $username . '#main-key","owner":"https:\/\/flox.dev\/users\/' . $username . '","publicKeyPem":"@TODO"}}';
        $response = $this->get($url);
        $response->assertStatus(200);
        $this->assertJson($expectedJson, json_encode($response->json()));
    }

    /** @test */
    public function shouldReturn404OnUnknownUser(): void
    {
        $response = $this->get('/users/unknownuser');
        $response->assertStatus(404);
    }
}
