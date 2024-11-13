<?php

namespace Tests\Services;

use App\Services\Calendar;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\Traits\Factories;
use Tests\Traits\Fixtures;
use Tests\Traits\Mocks;

class CalendarTest extends TestCase {

  use DatabaseMigrations;
  use Factories;
  use Fixtures;
  use Mocks;

  private $calendar;

  public function setUp(): void
  {
    parent::setUp();

    $this->calendar = app(Calendar::class);
  }

  /** @test */
  public function it_should_contain_and_format_tv_shows()
  {
    $user = $this->createUser();
    $this->be($user);
    $this->createTv();
    $this->createReview();

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

  /** @test */
  public function it_should_format_tv_shows_on_watchlist()
  {
    $user = $this->createUser();
    $this->be($user);
    $this->createTv();
    $this->createReview([
      'watchlist' => true
    ]);

    $items = $this->calendar->items();

    foreach($items as $item) {
      $this->assertEquals(['tv', 'watchlist-1'], $item['classes']);
    }
  }

  /** @test */
  public function it_should_contain_and_format_movies()
  {
    $user = $this->createUser();
    $this->be($user);
    $this->createMovie();
    $this->createReview();

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

  /** @test */
  public function it_should_format_movies_on_watchlist()
  {
    $user = $this->createUser();
    $this->be($user);
    $this->createMovie();
    $this->createReview([
      'watchlist' => true
    ]);

    $items = $this->calendar->items();

    foreach($items as $item) {
      $this->assertEquals(['movies', 'watchlist-1'], $item['classes']);
    }
  }

  /** @test */
  public function it_should_format_tv_shows_and_movies_on_watchlist()
  {
    $user = $this->createUser();
    $this->be($user);
    $tv = $this->createTv();
    $this->createReview([
      'item_id' => $tv['item']->id,
      'watchlist' => true
    ]);
    $movie = $this->createMovie();
    $this->createReview([
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
