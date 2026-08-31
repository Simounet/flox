<?php

namespace Tests\Api;

use App\Jobs\ReviewSendActivities;
use App\Models\Item;
use App\Models\ItemUser;
use App\Models\Profile;
use App\Models\Review;
use App\Services\Fediverse\HttpSignature;
use App\Services\Models\ItemService;
use App\Services\Models\ItemUserService;
use App\Services\Models\ProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\Factories;
use Tests\Traits\Fixtures;
use Tests\Traits\Mocks;

class ReviewTest extends TestCase {

    use DatabaseTransactions;
    use Factories;
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
      $this->user = User::factory()->create();
      $this->createStorageDownloadsMock();
      $this->profile = $profileService->storeLocal($this->user);
      $this->remoteProfile = json_decode(file_get_contents(__DIR__ . '/../_Fixtures/fediverse-fake-user/profile.json'));

      Http::fake([
          $this->remoteProfile->remote_url => Http::response(file_get_contents(__DIR__ . '/../_Fixtures/fediverse-fake-user/actor.json')),
          $this->remoteProfile->shared_inbox_url => Http::response('', 202)
      ]);
    }

    #[Test]
    public function shouldFailOnPostingAReviewIfUserNotLoggedIn()
    {
      $this->postJson('api/review', [
        'itemId' => 1,
        'rating' => 1,
        'content' => 'Lorem ipsum.'
      ])->assertStatus(401);
    }

    #[Test]
    public function shouldFailOnPostingAReviewWithoutItemId()
    {
      $this->actingAs($this->user)->postJson('api/review', [
        'rating' => 1,
        'content' => 'Lorem ipsum.'
      ])->assertStatus(422);
    }

    #[Test]
    public function shouldPostAReview()
    {
      Queue::fake();
      $this->addFollower();
      $movie = $this->mockItem();
      $response = $this->actingAs($this->user)->postJson('api/review', [
        'itemId' => $movie->id,
        'rating' => 1,
        'content' => 'Lorem ipsum.'
      ])->assertStatus(200);
      Queue::assertPushed(ReviewSendActivities::class);
      $this->assertDatabaseHas('reviews', [
        'id' => $response->json()['reviewId'],
        'content' => 'Lorem ipsum.',
      ]);
    }

    #[Test]
    public function shouldUpdateAReview()
    {
      Queue::fake();
      $this->addFollower();
      $movie = $this->mockItem();
      $this->actingAs($this->user)->postJson('api/review', [
        'itemId' => $movie->id,
        'rating' => 1,
        'content' => 'Lorem ipsum.'
      ])->assertStatus(200);
      $result = $this->actingAs($this->user)->postJson('api/review', [
        'itemId' => $movie->id,
        'rating' => 1,
        'content' => 'Lorem ipsum dolor.'
      ])->assertStatus(200);
      Queue::assertPushed(ReviewSendActivities::class);
      $this->assertDatabaseHas('reviews', [
        'id' => $result->json()['reviewId'],
        'rating' => 1,
        'content' => 'Lorem ipsum dolor.',
      ]);
    }

    #[Test]
    public function itShouldNotChangeItemUserRatingOnReviewPost(): void
    {
      $this->actingAs($this->user);
      $item = $this->mockItem();
      $itemUserRating = 1;
      $this->createItemUser([
          'user_id' => $this->user->id,
          'item_id' => $item->id,
          'rating' => $itemUserRating
      ]);

      $reviewResponse = $this->postJson('api/review', [
        'itemId' => $item->id,
        'rating' => 1,
        'content' => 'Lorem ipsum.'
      ]);
      $reviewResponse->assertStatus(200);

      $reviews = Review::query()->get();
      $this->assertEquals(1, $reviews->count());
      $review = $reviews->first();

      $updatedRating = 1;

      $this->patchJson('api/item/change-rating/' . $item->id, [
        'rating' => $updatedRating
      ]);

      $itemUser = ItemUser::find($item->id);
      $this->assertEquals($updatedRating, $itemUser->rating);
    }

    #[Test]
    public function shouldNotBeAbleToPostAReviewBeforeItemUserAdded(): void
    {
      $this->actingAs($this->user);

      $reviewResponse = $this->postJson('api/review', [
        'itemId' => 1,
        'rating' => 1,
        'content' => 'Lorem ipsum.'
      ]);
      $reviewResponse->assertStatus(500);
    }

    private function mockItem(): Item
    {
        $movieFixture = $this->floxFixtures('movie');

        $this->createGuzzleMock(
            $this->tmdbFixtures('movie/details'),
            $this->tmdbFixtures('movie/alternative_titles')
        );
        $itemService = app(ItemService::class);
        return $itemService->create($movieFixture['tmdb_id'], $movieFixture['media_type'], $this->user->id);
    }

    private function mockItemUser(Item $item, int $userId): ItemUser
    {
        $itemUserService = app(ItemUserService::class);
        return $itemUserService->create($item, $userId);
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
