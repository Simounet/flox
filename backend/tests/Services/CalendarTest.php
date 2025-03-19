<?php

namespace Tests\Services;

use App\Services\Calendar;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\Traits\Factories;
use Tests\Traits\Fixtures;
use Tests\Traits\Mocks;

class CalendarTest extends TestCase {

  use DatabaseTransactions;
  use Factories;
  use Fixtures;
  use Mocks;

  private $calendar;

  public function setUp(): void
  {
    parent::setUp();

    $this->calendar = app(Calendar::class);
  }

  #[Test]
  public function it_should_contain_and_format_tv_shows()
  {
    $user = $this->createUser();
    $this->be($user);
    $tv = $this->createTv();
    $this->createReview([
      'user_id' => $user->id,
      'item_id' => $tv['item']->id
    ]);

    $items = $this->calendar->items();

    $this->assertCount(4, $items);

    foreach($items as $item) {
      $this->assertArrayHasKey('startDate', $item);
      $this->assertArrayHasKey('id', $item);
      $this->assertArrayHasKey('tmdb_id', $item);
      $this->assertArrayHasKey('type', $item);
      $this->assertArrayHasKey('classes', $item);
      $this->assertArrayHasKey('title', $item);

      $this->assertEquals('tv', $item['type']);
      $this->assertEquals(['tv', 'watchlist-0'], $item['classes']);
    }
  }

  #[Test]
  public function it_should_format_tv_shows_on_watchlist()
  {
    $user = $this->createUser();
    $this->be($user);
    $tv = $this->createTv();
    $this->createReview([
      'user_id' => $user->id,
      'item_id' => $tv['item']->id,
      'watchlist' => true
    ]);

    $items = $this->calendar->items();

    foreach($items as $item) {
      $this->assertEquals(['tv', 'watchlist-1'], $item['classes']);
    }
  }

  #[Test]
  public function it_should_contain_and_format_movies()
  {
    $user = $this->createUser();
    $this->be($user);
    $movie = $this->createMovie();
    $this->createReview([
      'user_id' => $user->id,
      'item_id' => $movie->id
    ]);

    $items = $this->calendar->items();

    $this->assertCount(1, $items);

    foreach($items as $item) {
      $this->assertArrayHasKey('startDate', $item);
      $this->assertArrayHasKey('id', $item);
      $this->assertArrayHasKey('tmdb_id', $item);
      $this->assertArrayHasKey('type', $item);
      $this->assertArrayHasKey('classes', $item);
      $this->assertArrayHasKey('title', $item);

      $this->assertEquals('movies', $item['type']);
      $this->assertEquals(['movies', 'watchlist-0'], $item['classes']);
    }
  }

  #[Test]
  public function it_should_format_movies_on_watchlist()
  {
    $user = $this->createUser();
    $this->be($user);
    $movie = $this->createMovie();
    $this->createReview([
      'user_id' => $user->id,
      'item_id' => $movie->id,
      'watchlist' => true
    ]);

    $items = $this->calendar->items();

    foreach($items as $item) {
      $this->assertEquals(['movies', 'watchlist-1'], $item['classes']);
    }
  }

  #[Test]
  public function it_should_format_tv_shows_and_movies_on_watchlist()
  {
    $user = $this->createUser();
    $this->be($user);
    $tv = $this->createTv();
    $this->createReview([
      'user_id' => $user->id,
      'item_id' => $tv['item']->id,
      'watchlist' => true
    ]);
    $movie = $this->createMovie();
    $this->createReview([
      'user_id' => $user->id,
      'item_id' => $movie->id,
      'watchlist' => false
    ]);

    $items = $this->calendar->items();

    $this->assertEquals(['movies', 'watchlist-0'], $items[0]['classes']);
    $this->assertEquals(['tv', 'watchlist-1'], $items[1]['classes']);
    $this->assertEquals(['tv', 'watchlist-1'], $items[2]['classes']);
    $this->assertEquals(['tv', 'watchlist-1'], $items[3]['classes']);
    $this->assertEquals(['tv', 'watchlist-1'], $items[4]['classes']);
  }
}
