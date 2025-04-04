<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Follower;
use App\Models\Profile;
use App\Services\Fediverse\HttpSignature;
use App\Services\Models\ProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FederationTest extends TestCase
{

    use DatabaseTransactions;

    private $host;
    private $profile;
    private $profileService;
    private $remoteProfile;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(
            ThrottleRequests::class
        );

        $this->host = parse_url(env('APP_URL'))['host'];
        $this->withHeader('Host', $this->host);
        $this->profileService = new ProfileService(new Profile());
        $user = User::factory()->create();
        $this->profile = $this->profileService->storeLocal($user);
        $this->remoteProfile = json_decode(file_get_contents(__DIR__ . '/../_Fixtures/fediverse-fake-user/profile.json'));

        Http::fake([
            $this->remoteProfile->remote_url => Http::response(file_get_contents(__DIR__ . '/../_Fixtures/fediverse-fake-user/actor.json')),
            $this->remoteProfile->shared_inbox_url => Http::response('', 202)
        ]);
    }

    #[Test]
    public function shouldReturnAcceptOnFollowRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"' . $this->remoteProfile->remote_url . '","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));
        $response->assertStatus(200);
    }

    #[Test]
    public function shouldReturnAcceptOnMultipleFollowRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"' . $this->remoteProfile->remote_url . '","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));
        $response = $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));

        $response->assertStatus(200);
    }

    #[Test]
    public function shouldFailOnWrongFollowRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"' . $this->remoteProfile->remote_url . '","object":"https://fake-instance.tld/users/test"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));

        $response->assertStatus(404);
    }

    #[Test]
    public function shouldFailOnWrongRequest(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","actor":"' . $this->remoteProfile->remote_url . '","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));

        $response->assertStatus(400);
    }

    #[Test]
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

    #[Test]
    public function shouldReturn501OnNotSupportedActivity(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Offer","actor":"' . $this->remoteProfile->remote_url . '","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));

        $response->assertStatus(501);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function shouldReturn200ForUnknownProfileDeleteActivity(): void
    {
        $payload = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/users/testuser#delete","type":"Delete","actor":"https://fediverse.tld/users/testuser","to":["https://www.w3.org/ns/activitystreams#Public"],"object":"https://fediverse.tld/users/testuser","signature":{"type":"RsaSignature2017","creator":"https://fediverse.tld/users/testuser#main-key","created":"2023-11-30T05:01:05Z","signatureValue":"PWVGCagVgZToMyHT6aCUCE9qmn/JuYRrUAqDL/EGt1Pde/+LjpvnLOzB3H61EWKWVaSX0EYfZN96nJbSnxrtDE9MgqXjAM91X1Lg/oB9iF1DZAcpympuGUQ1mvRs2e5qVaXxb9rO9urW7MrO2lY2xMHd9LRIvP8b3O74m4GwwRcBOL3CEovOjfseV2uyBPm20USskCZo862KcXYaY7FqzvcnM55EuJHJIc0Zff7Y0VjmBTCZhmNgDvAoeF0Whfousuv+aAmgGAY6EFZmgoX/oxkINchfRSbGtvv5hE/NA2XxJDOKAWCO6L/b/0ozR/wJafxZfVMBHtGX0EsBkB+Gvw=="}}';
        $headers = '{"content-length":["763"],"connection":["Keep-Alive"],"signature":["keyId=\"https://fediverse.tld/users/testuser#main-key\",algorithm=\"rsa-sha256\",headers=\"(request-target) host date digest content-type\",signature=\"J0+4Kym+gHSsLl+AaNOal/PqkQesJFpNI6pIIA/z2pVqU57lNSfqFXNsjSl6x/YmFSWuhsX4FpOWh8g98BEVh1qHU43m5sdPCIoW5/86aeiZMpPsHU+zl3KQaN1HYggRIzrRB3OrRcyZKzECiOulbKOB5j+JSSbftkGxYeI/VUcHXkJP17DS5lTYEv0TbgN46kfvNCa3cQp9WoSnWO1hPmOYtLTT0FYWYQUEqF7eP1TGZLHcwZ1wp2/gwc9nMHJOk8ineCl4P3SS6aV+K2FekKGBM5yfIBYfx3DYLjO0F2mOCM5v03a1yXIoXTqHb+YTAkruROMpuckyaewF6Z2UfA==\""],"content-type":["application/activity+json"],"digest":["SHA-256=Mg+W0QxohcR9zMik1BOX492LppyvdHLuqP9UWC95f6M="],"accept-encoding":["gzip"],"date":["Thu, 30 Nov 2023 05:01:17 GMT"],"host":["' . $this->profile->domain . '"],"user-agent":["http.rb/5.1.1 (Fediverse/1.0.0; +https://fediverse.tld/)"]}';
        $response = $this->postJson(
            $this->profile->shared_inbox_url,
            (array) json_decode($payload),
            (array) json_decode($headers)
        );
        $response->assertStatus(200);
    }

    #[Test]
    public function shouldRemoveProfileAndAssociatedFollowerOnDeleteActivity(): void
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"' . $this->remoteProfile->remote_url . '","object":"' . $this->profile->remote_url . '"}';
        $response = $this->postJson(
            $this->profile->shared_inbox_url,
            (array) json_decode($dataStr),
            $this->getHeaders($dataStr)
        );

        $deleteStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"' . $this->remoteProfile->remote_url . '#delete","type":"Delete","actor":"' . $this->remoteProfile->remote_url . '","to":["https://www.w3.org/ns/activitystreams#Public"],"object":"' . $this->remoteProfile->remote_url . '"}';
        $response = $this->postJson(
            $this->profile->shared_inbox_url,
            (array) json_decode($deleteStr),
            $this->getHeaders($deleteStr)
        );
        $response->assertStatus(200);

        $this->assertEquals(0, Follower::count());
    }

    private function getHeaders(string $dataStr): array
    {
	return (new HttpSignature)->sign(
            $this->profile->shared_inbox_url,
            $this->remoteProfile->private_key,
            $this->remoteProfile->key_id_url,
            json_encode(json_decode($dataStr))
        );
    }
}
