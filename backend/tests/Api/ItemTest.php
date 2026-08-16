<?php

declare(strict_types=1);

namespace Tests\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\Factories;
use Tests\Traits\Fixtures;
use Tests\Traits\Mocks;

final class ItemTest extends TestCase {

    use Factories;
    use Fixtures;
    use Mocks;
    use DatabaseTransactions;

    protected User $user1;

    public function setUp(): void
    {
        parent::setUp();
        $this->user1 = $this->createUser();
    }

    #[Test]
    public function it_should_check_item_user_with_one_user(): void
    {
        $this->actingAs($this->user1);
        $movie = $this->createMovie();
        $this->createItemUser([
            'user_id' => $this->user1->id,
            'item_id' => $movie->id
        ]);

        $response = $this->actingAs($this->user1)->getJson('api/items/home/last%20seen/desc');
        $response->assertStatus(200);

        $item = $response->json()['data'][0];
        $this->assertArrayHasKey('item_user', $item);
        $this->assertEquals(false, empty($item['item_user']));
        $this->assertEquals($this->user1->id, $item['item_user']['user_id']);
    }

    #[Test]
    public function it_should_check_item_user_with_multiple_users(): void
    {
        $movie = $this->createMovie();

        $itemUser1 = $this->actingAs($this->user1)->createItemUser([
            'user_id' => $this->user1->id,
            'item_id' => $movie->id
        ]);

        $user2 = $this->createUser();
        $itemUser2 = $this->actingAs($user2)->createItemUser([
            'user_id' => $user2->id ,
            'item_id' => $movie->id
        ]);

        $responseWithUser1 = $this->actingAs($this->user1)->getJson('api/items/home/last%20seen/desc');
        $responseWithUser1->assertStatus(200);
        $itemUser1ResponseData = $responseWithUser1->json()['data'][0];
        $this->assertEquals($itemUser1->id, $itemUser1ResponseData['item_user']['id']);
        $this->assertEquals($this->user1->id, $itemUser1ResponseData['item_user']['user_id']);

        $responseWithUser2 = $this->actingAs($user2)->getJson('api/items/home/last%20seen/desc');
        $responseWithUser2->assertStatus(200);
        $itemUser2ResponseData = $responseWithUser2->json()['data'][0];
        $this->assertEquals($itemUser2->id, $itemUser2ResponseData['item_user']['id']);
        $this->assertEquals($user2->id, $itemUser2ResponseData['item_user']['user_id']);
    }

    #[Test]
    public function it_should_return_empty_data_on_anonymous_call(): void
    {
        $movie = $this->createMovie();
        $this->createItemUser([
            'user_id' => $this->user1->id,
            'item_id' => $movie->id
        ]);

        $data = $this->getJson('api/items/home/last%20seen/desc')->json()['data'];
        $this->assertTrue(empty($data));
    }

    #[Test]
    public function itShouldFailAtChangingOtherUserRating(): void
    {
      $movie = $this->createMovie();
      $review = $this->createItemUser([
        'user_id' => $this->user1->id,
        'item_id' => $movie->id
      ]);
      $user2 = $this->createUser();

      $this->actingAs($user2)->patchJson('api/item/change-rating/' . $review->id, [
        'rating' => 2
      ])->assertStatus(404);
    }
}
