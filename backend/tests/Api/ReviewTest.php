<?php

namespace Tests\Api;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase {

    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
      parent::setUp();

      $this->user = factory(User::class)->create();
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
      $this->actingAs($this->user)->postJson('api/review', [
        'itemId' => 1,
        'content' => 'Lorem ipsum.'
      ])->assertStatus(200);
      $this->assertDatabaseHas('reviews', [
        'item_id' => 1,
        'content' => 'Lorem ipsum.',
      ]);
    }

    /** @test */
    public function shouldUpdateAReview()
    {
      $this->actingAs($this->user)->postJson('api/review', [
        'itemId' => 1,
        'content' => 'Lorem ipsum.'
      ])->assertStatus(200);
      $this->actingAs($this->user)->postJson('api/review', [
        'itemId' => 1,
        'content' => 'Lorem ipsum dolor.'
      ])->assertStatus(200);
      $this->assertDatabaseHas('reviews', [
        'item_id' => 1,
        'content' => 'Lorem ipsum dolor.',
      ]);
    }
}
