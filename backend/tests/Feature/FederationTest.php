<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Profile;
use App\Services\Fediverse\HttpSignature;
use App\Services\Models\ProfileService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FederationTest extends TestCase
{

    use RefreshDatabase;

    private $host;
    private $profile;
    private $profileService;
    private $remoteProfile;

    public function setUp(): void
    {
        parent::setUp();

        $this->host = parse_url(env('APP_URL'))['host'];
        $this->withHeader('Host', $this->host);
        $this->profileService = new ProfileService(new Profile());
        $user = factory(User::class)->create();
        $this->profile = $this->profileService->storeLocal($user);
        $this->remoteProfile = json_decode(file_get_contents(__DIR__ . '/../_Fixtures/fediverse-fake-user/profile.json'));

        Http::fake([
            $this->remoteProfile->remote_url => Http::response(file_get_contents(__DIR__ . '/../_Fixtures/fediverse-fake-user/actor.json')),
            $this->remoteProfile->shared_inbox_url => Http::response('', 202)
        ]);
    }

    /** @test */
    public function shouldReturnAcceptOnFollowRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"' . $this->remoteProfile->remote_url . '","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));
        $response->assertStatus(200);
    }

    /** @test */
    public function shouldReturnAcceptOnMultipleFollowRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"' . $this->remoteProfile->remote_url . '","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));
        $response = $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));

        $response->assertStatus(200);
    }

    /** @test */
    public function shouldFailOnWrongFollowRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"' . $this->remoteProfile->remote_url . '","object":"https://fake-instance.tld/users/test"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));

        $response->assertStatus(404);
    }

    /** @test */
    public function shouldFailOnWrongRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","actor":"' . $this->remoteProfile->remote_url . '","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));

        $response->assertStatus(400);
    }

    /** @test */
    public function shouldFailOnWrongUndoRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"' . $this->remoteProfile->remote_url . '","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));

        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Undo","actor":"' . $this->remoteProfile->remote_url . '","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);
        $response = $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));

        $response->assertStatus(400);
    }

    /** @test */
    public function shouldReturn501OnNotSupportedActivity(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Offer","actor":"' . $this->remoteProfile->remote_url . '","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));

        $response->assertStatus(501);
    }

    /** @test */
    public function followUnfollowWorkflow(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"' . $this->remoteProfile->remote_url . '","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));

        $response->assertStatus(200);
        $this->getJson($this->profile->followers_url)
            ->assertStatus(200)
            ->assertJsonFragment([
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => $this->profile->followers_url,
                'type' => 'OrderedCollection',
                'totalItems' => 1
            ]);
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881dd","type":"Undo","actor":"' . $this->remoteProfile->remote_url . '","object":{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"' . $this->remoteProfile->remote_url . '","object":"' . $this->profile->remote_url . '"}}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));

        $response->assertStatus(200);
    }

    /** @test */
    public function shouldFollowerList(): void
    {
        $this->getJson($this->profile->followers_url)
            ->assertStatus(200)
            ->assertJsonFragment([
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => $this->profile->followers_url,
                'type' => 'OrderedCollection',
                'totalItems' => 0
            ]);
    }
}
