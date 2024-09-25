<?php

namespace Tests\Services\Api;

interface ApiTestInterface
{
  public function setUp(): void;
  public function it_should_abort_the_request();
  public function it_should_create_a_new_movie();
  public function it_should_not_create_a_new_movie_if_it_exists();
  public function it_should_create_a_new_tv_show();
  public function it_should_not_create_a_new_tv_show_if_it_exists();
  public function it_should_rate_a_movie();
  public function it_should_rate_a_tv_show();
  public function it_should_mark_an_episode_as_seen();
  public function it_should_updated_review_updated_at();
  public function it_should_add_a_review_to_existing_item();
  public function add_a_movie_from_api();
  public function mark_episode_seen_multiple_times_from_api();
}
