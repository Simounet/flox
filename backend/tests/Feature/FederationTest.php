<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Profile;
use App\Services\Models\ProfileService;
use App\User;
use ActivityPhp\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FederationTest extends TestCase
{

    use RefreshDatabase;

    private $host;
    private $profile;
    private $profileService;

    public function setUp(): void
    {
        parent::setUp();

        $this->host = parse_url(env('APP_URL'))['host'];
        $this->withHeader('Host', $this->host);
        $user = factory(User::class)->create();
        $this->profileService = new ProfileService(new Profile());
        $this->profile = $this->profileService->storeLocal($user);
    }

    /** @test */
    public function shouldReturnAcceptOnFollowRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://mastodon.simounet.net/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"https://mastodon.simounet.net/users/simounet","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->inbox_url, $data);

        $sourceProfile = Profile::latest('id')->first();

        $response->assertStatus(200);
        $response->assertJsonIsObject();
        $response->assertJsonFragment([
            'type' => 'Accept',
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $this->profileService->acceptFollowsId($sourceProfile, $this->profile),
            'actor' => $this->profile->remote_url,
            'object' => $data
        ]);
    }

    /** @test */
    public function shouldReturnAcceptOnMultipleFollowRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://mastodon.simounet.net/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"https://mastodon.simounet.net/users/simounet","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $this->postJson($this->profile->inbox_url, $data);
        $response = $this->postJson($this->profile->inbox_url, $data);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Already processed.']);
    }

    /** @test */
    public function shouldFailOnWrongFollowRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://mastodon.simounet.net/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"https://mastodon.simounet.net/users/simounet","object":"https://fake-instance.dev/users/test"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->inbox_url, $data);

        $response->assertStatus(404);
    }

    /** @test */
    public function shouldFailOnWrongRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://mastodon.simounet.net/c5a8e80d-eeba-4f1f-827a-e759687881cc","actor":"https://mastodon.simounet.net/users/simounet","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->inbox_url, $data);

        $response->assertStatus(500);
    }

    /** @test */
    public function shouldReturn501OnNotSupportedActivity(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://mastodon.simounet.net/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Undo","actor":"https://mastodon.simounet.net/users/simounet","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->inbox_url, $data);

        $response->assertStatus(501);
    }
}
