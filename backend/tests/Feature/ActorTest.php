<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Services\Models\ProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActorTest extends TestCase
{

    use DatabaseTransactions;

    private $host;
    private $profile;
    private $profileService;

    public function setUp(): void
    {
        parent::setUp();

        $this->host = parse_url(env('APP_URL'))['host'];
        $this->withHeader('Host', $this->host);
        $user = User::factory()->create();
        $this->profileService = new ProfileService(new Profile());
        $this->profile = $this->profileService->storeLocal($user);
    }

    /** @test */
    public function shouldReturnResourceOk(): void
    {
        $response = $this->get($this->profile->remote_url);
        $response->assertStatus(200);
        $response->assertJsonFragment([
                'type' => 'Person',
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => $this->profile->remote_url,
                'outbox' => $this->profile->outbox_url,
                'following' => $this->profile->following_url,
                'followers' => $this->profile->followers_url,
                'inbox' => $this->profile->inbox_url,
                'preferredUsername' => $this->profile->username,
                'name' => $this->profile->username,
                'publicKey' => [
                    'id' => $this->profile->key_id_url,
                    'owner' => $this->profile->remote_url,
                    'publicKeyPem' => $this->profile->public_key,
                ]
        ]);
    }

    /** @test */
    public function shouldReturn404OnUnknownUser(): void
    {
        $response = $this->get('/users/unknownuser');
        $response->assertStatus(404);
    }
}
