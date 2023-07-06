<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Profile;
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

    public function setUp(): void
    {
        parent::setUp();

        Http::fake(['https://mastodon.tld/users/alice' => Http::response('{"@context":["https://www.w3.org/ns/activitystreams","https://w3id.org/security/v1"],"id":"https://mastodon.tld/users/alice","type":"Person","following":"https://mastodon.tld/users/alice/following","followers":"https://mastodon.tld/users/alice/followers","inbox":"https://mastodon.tld/users/alice/inbox","outbox":"https://mastodon.tld/users/alice/outbox","featured":"https://mastodon.tld/users/alice/collections/featured","featuredTags":"https://mastodon.tld/users/alice/collections/tags","preferredUsername":"alice","name":"Simounet","summary":"\u003cp\u003eHumain // Développeur web // Partisan des logiciels libres // Jamais loin de mes flux RSS\u003c/p\u003e","url":"https://mastodon.tld/@alice","manuallyApprovesFollowers":false,"discoverable":true,"published":"2019-03-29T00:00:00Z","devices":"https://mastodon.tld/users/alice/collections/devices","publicKey":{"id":"https://mastodon.tld/users/alice#main-key","owner":"https://mastodon.tld/users/alice","publicKeyPem":"-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtWONzwymF2OW/oTM7/3U\nWoofFL7+ma6w5OfjRo3KitF3F6XAlBzdGZPhBBOie235DEs1dG4iOjK0HvljMcU3\nsj6MS3hGnAQLhPBooLEDmk4PpwuEeniKxTf0Yt5licKvHWX0cdrS7uXo/aopk4Gj\no2y9TUBlQm8qRaMHcN81D3fd4v4w2NPKXlEWVaOf76ZdxoBIKMWjIIlESi+U6Skm\nSzCtOFoMEVGjw1uiB8/OA313NialvLVb2qBabD7DkqJtDWwc7HNNM3JTPa36Z1Hd\nTf8U3cU0ZvRDh3XhKTjuhDSyceu/0vLjNAr+d1jBgSK0holloOUbRKXLeuGnBdf5\nhwIDAQAB\n-----END PUBLIC KEY-----\n"},"endpoints":{"sharedInbox":"https://mastodon.tld/inbox"},"icon":{"type":"Image","mediaType":"image/jpeg","url":"https://mastodon.tld/system/accounts/avatars/000/000/002/original/fd898e1a5ff33258.jpg"},"image":{"type":"Image","mediaType":"image/jpeg","url":"https://mastodon.tld/system/accounts/headers/000/000/002/original/f9c0a36fa5258d2e.jpg"}}')]);
        $this->host = parse_url(env('APP_URL'))['host'];
        $this->withHeader('Host', $this->host);
        $user = factory(User::class)->create();
        $this->profileService = new ProfileService(new Profile());
        $this->profile = $this->profileService->storeLocal($user);
    }

    /** @test */
    public function shouldReturnAcceptOnFollowRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://mastodon.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"https://mastodon.tld/users/alice","object":"' . $this->profile->remote_url . '"}';
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
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://mastodon.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"https://mastodon.tld/users/alice","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $this->postJson($this->profile->inbox_url, $data);
        $response = $this->postJson($this->profile->inbox_url, $data);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Already processed.']);
    }

    /** @test */
    public function shouldFailOnWrongFollowRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://mastodon.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"https://mastodon.tld/users/alice","object":"https://fake-instance.tld/users/test"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->inbox_url, $data);

        $response->assertStatus(404);
    }

    /** @test */
    public function shouldFailOnWrongRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://mastodon.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","actor":"https://mastodon.tld/users/alice","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->inbox_url, $data);

        $response->assertStatus(400);
    }

    /** @test */
    public function shouldFailOnWrongUndoRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://mastodon.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"https://mastodon.tld/users/alice","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->inbox_url, $data);

        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://mastodon.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Undo","actor":"https://mastodon.tld/users/alice","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);
        $response = $this->postJson($this->profile->inbox_url, $data);

        $response->assertStatus(400);
    }

    /** @test */
    public function shouldReturn501OnNotSupportedActivity(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://mastodon.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Offer","actor":"https://mastodon.tld/users/alice","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->inbox_url, $data);

        $response->assertStatus(501);
    }

    /** @test */
    public function followUnfollowWorkflow(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://mastodon.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"https://mastodon.tld/users/alice","object":"' . $this->profile->remote_url . '"}';
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
        $this->getJson($this->profile->followers_url)
            ->assertStatus(200)
            ->assertJsonFragment([
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => $this->profile->followers_url,
                'type' => 'OrderedCollection',
                'totalItems' => 1
            ]);
        $dataStrWithWrongObject = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://mastodon.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Undo","actor":"https://mastodon.tld/users/alice","object":"' . $this->profile->remote_url . '"}';
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://mastodon.tld/c5a8e80d-eeba-4f1f-827a-e759687881dd","type":"Undo","actor":"https://mastodon.tld/users/alice","object":{"@context":"https://www.w3.org/ns/activitystreams","id":"https://mastodon.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"https://mastodon.tld/users/alice","object":"' . $this->profile->remote_url . '"}}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->inbox_url, $data);

        $sourceProfile = Profile::latest('id')->first();

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
