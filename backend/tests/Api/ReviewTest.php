<?php

namespace Tests\Api;

use App\Jobs\ReviewSendActivity;
use App\Jobs\ReviewSendActivities;
use App\Profile;
use App\Services\Fediverse\HttpSignature;
use App\Services\Models\ItemService;
use App\Services\Models\ProfileService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\Fixtures;
use Tests\Traits\Mocks;

class ReviewTest extends TestCase {

    use RefreshDatabase;
    use Fixtures;
    use Mocks;

    protected $user;

    private Profile $profile;

    private mixed $remoteProfile;

    public function setUp(): void
    {
      parent::setUp();

      $host = parse_url(env('APP_URL'))['host'];
      $this->withHeader('Host', $host);
      $profileService = new ProfileService(new Profile());
      $this->user = factory(User::class)->create();
      $this->createStorageDownloadsMock();
      $this->profile = $profileService->storeLocal($this->user);
      $this->remoteProfile = json_decode(file_get_contents(__DIR__ . '/../_Fixtures/fediverse-fake-user/profile.json'));

      Http::fake([
          $this->remoteProfile->remote_url => Http::response(file_get_contents(__DIR__ . '/../_Fixtures/fediverse-fake-user/actor.json')),
          $this->remoteProfile->shared_inbox_url => Http::response('', 202)
      ]);
    }

    /** @test */
    public function shouldFailOnPostingAReviewIfUserNotLoggedIn()
    {
      $this->postJson('api/review', [
        'itemId' => 1,
        'content' => 'Lorem ipsum.'
      ])->assertStatus(401);
    }

    /** @test */
    public function shouldFailOnPostingAReviewWithoutItemId()
    {
      $this->actingAs($this->user)->postJson('api/review', [
        'content' => 'Lorem ipsum.'
      ])->assertStatus(400);
    }

    /** @test */
    public function shouldPostAReview()
    {
      Queue::fake();
      $this->addFollower();
      $this->mockItem();
      $this->actingAs($this->user)->postJson('api/review', [
        'itemId' => 1,
        'content' => 'Lorem ipsum.'
      ])->assertStatus(200);
      Queue::assertPushed(ReviewSendActivities::class);
      $this->assertDatabaseHas('reviews', [
        'item_id' => 1,
        'content' => 'Lorem ipsum.',
      ]);
    }

    /** @test */
    public function shouldUpdateAReview()
    {
      Queue::fake();
      $this->addFollower();
      $this->mockItem();
      $this->actingAs($this->user)->postJson('api/review', [
        'itemId' => 1,
        'content' => 'Lorem ipsum.'
      ])->assertStatus(200);
      $this->actingAs($this->user)->postJson('api/review', [
        'itemId' => 1,
        'content' => 'Lorem ipsum dolor.'
      ])->assertStatus(200);
      Queue::assertPushed(ReviewSendActivities::class);
      $this->assertDatabaseHas('reviews', [
        'item_id' => 1,
        'content' => 'Lorem ipsum dolor.',
      ]);
    }

    private function mockItem()
    {
        $this->createGuzzleMock(
            $this->tmdbFixtures('movie/details'),
            $this->tmdbFixtures('movie/alternative_titles')
        );
        $itemService = app(ItemService::class);
        $itemService->create($this->floxFixtures('movie'));
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

    private function addFollower()
    {
        $dataStr = '{"@context":"https://www.w3.org/ns/activitystreams","id":"https://fediverse.tld/c5a8e80d-eeba-4f1f-827a-e759687881cc","type":"Follow","actor":"' . $this->remoteProfile->remote_url . '","object":"' . $this->profile->remote_url . '"}';
        $data = (array) json_decode($dataStr);

        $response = $this->postJson($this->profile->shared_inbox_url, $data, $this->getHeaders($dataStr));
        $response->assertStatus(200);
    }
}
