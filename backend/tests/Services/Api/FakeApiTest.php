<?php

namespace Tests\Services\Api;

use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\FakeApi;
use Tests\TestCase;

class FakeApiTest extends TestCase implements ApiTestInterface
{
  private ApiTestBase $apiTestBase;

  public function setUp(): void
  {
    parent::setUp();

    $this->apiTestBase = new ApiTestBase('test');

    $this->apiTestBase->apiClass = FakeApi::class;

    $this->apiTestBase->setUp();
  }

  #[Test]
  public function it_should_abort_the_request()
  {
    $this->apiTestBase->it_should_abort_the_request('fake/abort.json');
  }

  #[Test]
  public function it_should_create_a_new_movie()
  {
    $this->apiTestBase->it_should_create_a_new_movie('fake/movie.json');
  }

  #[Test]
  public function it_should_not_create_a_new_movie_if_it_exists()
  {
    $this->apiTestBase->it_should_not_create_a_new_movie_if_it_exists('fake/movie.json');
  }

  #[Test]
  public function it_should_create_a_new_tv_show()
  {
    $this->apiTestBase->it_should_create_a_new_tv_show('fake/tv.json');
  }

  #[Test]
  public function it_should_not_create_a_new_tv_show_if_it_exists()
  {
    $this->apiTestBase->it_should_not_create_a_new_tv_show_if_it_exists('fake/tv.json');
  }

  #[Test]
  public function it_should_rate_a_movie()
  {
    $this->apiTestBase->it_should_rate_a_movie('fake/movie_rating.json', 2);
  }

  #[Test]
  public function it_should_rate_a_tv_show()
  {
    $this->apiTestBase->it_should_rate_a_tv_show('fake/tv_rating.json', 3);
  }

  #[Test]
  public function it_should_mark_an_episode_as_seen()
  {
    $this->apiTestBase->it_should_mark_an_episode_as_seen('fake/episode_seen.json');
  }

  #[Test]
  public function it_should_updated_review_updated_at()
  {
    $this->apiTestBase->it_should_updated_review_updated_at('fake/episode_seen.json');
  }

  #[Test]
  public function it_should_add_a_review_to_existing_item()
  {
    $this->apiTestBase->it_should_add_a_review_to_existing_item('fake/episode_seen.json');
  }

  #[Test]
  public function add_a_movie_from_api() {
    self::expectNotToPerformAssertions();
  }

  #[Test]
  public function mark_episode_seen_multiple_times_from_api() {
    self::expectNotToPerformAssertions();
  }
}
