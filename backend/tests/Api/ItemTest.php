<?php

declare(strict_types=1);

namespace Tests\Api;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Tests\Traits\Factories;
use Tests\Traits\Fixtures;
use Tests\Traits\Mocks;

final class ItemTest extends TestCase {

    use Factories;
    use Fixtures;
    use Mocks;
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_should_check_user_review_with_one_user(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $movie = $this->createMovie();
        $this->createReview(['item_id' => $movie->id]);

        $response = $this->actingAs($user)->getJson('api/items/home/last%20seen/desc');
        $response->assertStatus(200);

        $item = $response->json()['data'][0];
        $this->assertArrayHasKey('user_review', $item);
        $this->assertEquals(false, empty($item['user_review']));
        $this->assertEquals($user->id, $item['user_review']['user_id']);
    }

    /** @test */
    public function it_should_check_user_review_with_multiple_users(): void
    {
        $movie = $this->createMovie();

        $user1 = $this->createUser();
        $reviewContentUser1 = 'Testing review content';
        $reviewUser1 = $this->actingAs($user1)->createReview(['item_id' => $movie->id, 'content' => $reviewContentUser1]);

        $user2 = $this->createUser();
        $reviewUser2 = $this->actingAs($user2)->createReview(['user_id' => $user2->id , 'item_id' => $movie->id]);

        $responseWithUser1 = $this->actingAs($user1)->getJson('api/items/home/last%20seen/desc');
        $responseWithUser1->assertStatus(200);
        $itemUser1 = $responseWithUser1->json()['data'][0];
        $this->assertEquals($reviewUser1->id, $itemUser1['user_review']['id']);
        $this->assertEquals($user1->id, $itemUser1['user_review']['user_id']);
        $this->assertEquals($reviewContentUser1, $itemUser1['user_review']['content']);

        $responseWithUser2 = $this->actingAs($user2)->getJson('api/items/home/last%20seen/desc');
        $responseWithUser2->assertStatus(200);
        $itemUser2 = $responseWithUser2->json()['data'][0];
        $this->assertEquals($reviewUser2->id, $itemUser2['user_review']['id']);
        $this->assertEquals($user2->id, $itemUser2['user_review']['user_id']);
        $this->assertEquals('', $itemUser2['user_review']['content']);
    }

    /** @test */
    public function it_should_return_empty_data_on_anonymous_call(): void
    {
        $this->createUser();
        $movie = $this->createMovie();
        $this->createReview(['item_id' => $movie->id]);

        $data = $this->getJson('api/items/home/last%20seen/desc')->json()['data'];
        $this->assertTrue(empty($data));
    }
}
