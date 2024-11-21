<?php

  namespace Tests\Setting;

  use Illuminate\Foundation\Testing\DatabaseTransactions;
  use Tests\TestCase;
  use App\Models\Setting;
  use Tests\Traits\Factories;

  class SettingTest extends TestCase {

    use DatabaseTransactions;
    use Factories;

    protected $user1;
    protected $user2;

    public function setUp(): void
    {
      parent::setUp();

      $this->user1 = $this->createUser();
      $this->user2 = $this->createUser();
    }

    /** @test */
    public function user_can_change_settings()
    {
      $oldSettings = Setting::where('user_id', $this->user1->id)->first();

      $this->actingAs($this->user1)->patchJson('api/settings', [
        'genre' => 1,
        'date' => 0,
        'spoiler' => 0,
        'watchlist' => 1,
        'ratings' => 'hover',
      ]);

      $newSettings = Setting::where('user_id', $this->user1->id)->first();

      $this->assertEquals(0, $oldSettings->show_genre);
      $this->assertEquals(1, $oldSettings->show_date);
      $this->assertEquals(1, $oldSettings->episode_spoiler_protection);
      $this->assertEquals(0, $oldSettings->show_watchlist_everywhere);
      $this->assertEquals('always', $oldSettings->show_ratings);
      $this->assertEquals(1, $newSettings->show_genre);
      $this->assertEquals(0, $newSettings->show_date);
      $this->assertEquals(0, $newSettings->episode_spoiler_protection);
      $this->assertEquals(1, $newSettings->show_watchlist_everywhere);
      $this->assertEquals('hover', $newSettings->show_ratings);
    }

    /** @test */
    public function multi_user_change_only_its_settings()
    {
      $beforeSettingsUser1 = Setting::where('user_id', $this->user1->id)->first();
      $beforeSettingsUser2 = Setting::where('user_id', $this->user2->id)->first();

      $this->actingAs($this->user2)->patchJson('api/settings', [
        'genre' => 1,
        'date' => 0,
        'spoiler' => 0,
        'watchlist' => 1,
        'ratings' => 'hover',
      ]);

      $afterSettingsUser1 = Setting::where('user_id', $this->user1->id)->first();
      $afterSettingsUser2 = Setting::where('user_id', $this->user2->id)->first();

      $this->assertEquals(0, $beforeSettingsUser1->show_genre);
      $this->assertEquals(1, $beforeSettingsUser1->show_date);
      $this->assertEquals(1, $beforeSettingsUser1->episode_spoiler_protection);
      $this->assertEquals(0, $beforeSettingsUser1->show_watchlist_everywhere);
      $this->assertEquals('always', $beforeSettingsUser1->show_ratings);

      $this->assertEquals(0, $beforeSettingsUser2->show_genre);
      $this->assertEquals(1, $beforeSettingsUser2->show_date);
      $this->assertEquals(1, $beforeSettingsUser2->episode_spoiler_protection);
      $this->assertEquals(0, $beforeSettingsUser2->show_watchlist_everywhere);
      $this->assertEquals('always', $beforeSettingsUser2->show_ratings);

      $this->assertEquals(0, $afterSettingsUser1->show_genre);
      $this->assertEquals(1, $afterSettingsUser1->show_date);
      $this->assertEquals(1, $afterSettingsUser1->episode_spoiler_protection);
      $this->assertEquals(0, $afterSettingsUser1->show_watchlist_everywhere);
      $this->assertEquals('always', $afterSettingsUser1->show_ratings);

      $this->assertEquals(1, $afterSettingsUser2->show_genre);
      $this->assertEquals(0, $afterSettingsUser2->show_date);
      $this->assertEquals(0, $afterSettingsUser2->episode_spoiler_protection);
      $this->assertEquals(1, $afterSettingsUser2->show_watchlist_everywhere);
      $this->assertEquals('hover', $afterSettingsUser2->show_ratings);
    }

    /** @test */
    public function user_can_change_refresh()
    {
      $oldSettings = Setting::first();

      $this->actingAs($this->user1)->patchJson('api/settings/refresh', [
        'refresh' => 1,
      ]);

      $newSettings = Setting::first();

      $this->assertEquals(0, $oldSettings->refresh_automatically);
      $this->assertEquals(1, $newSettings->refresh_automatically);
    }

    /** @test */
    public function user_can_change_reminders_send_to()
    {
      $oldSettings = Setting::first();

      $this->actingAs($this->user1)->patchJson('api/settings/reminders-send-to', [
        'reminders_send_to' => 'jon@snow.io',
      ]);

      $newSettings = Setting::first();

      $this->assertNull($oldSettings->reminders_send_to);
      $this->assertEquals('jon@snow.io', $newSettings->reminders_send_to);
    }

    /** @test */
    public function user_can_change_reminder_options()
    {
      $oldSettings = Setting::first();

      $this->actingAs($this->user1)->patchJson('api/settings/reminder-options', [
        'daily' => 1,
        'weekly' => 1,
      ]);

      $newSettings = Setting::first();

      $this->assertEquals(0, $oldSettings->daily_reminder);
      $this->assertEquals(0, $oldSettings->weekly_reminder);
      $this->assertEquals(1, $newSettings->daily_reminder);
      $this->assertEquals(1, $newSettings->weekly_reminder);
    }

    /** @test */
    public function user_can_generate_a_new_api_key()
    {
      $apiKeyBefore = $this->user1->api_key;

      $this->actingAs($this->user1)->patchJson('api/settings/api-key');

      $apiKeyAfter = $this->user1->api_key;

      $this->actingAs($this->user1)->patchJson('api/settings/api-key');

      $apiKeyAfterSecond = $this->user1->api_key;

      $this->assertNull($apiKeyBefore);
      $this->assertNotNull($apiKeyAfter);
      $this->assertNotEquals($apiKeyAfterSecond, $apiKeyAfter);
    }

    /** @test */
    public function multi_user_can_generate_a_new_api_key(): void
    {
      $this->user2 = $this->createUser();
      $user2DefaultApiKey = $this->user2->api_key;

      $this->actingAs($this->user1)->patchJson('api/settings/api-key');
      $user1ApiKey = $this->user1->api_key;

      $this->actingAs($this->user2)->patchJson('api/settings/api-key');
      $user2ApiKey = $this->user2->api_key;

      $this->assertNotNull($user1ApiKey);
      $this->assertNotNull($user2ApiKey);
      $this->assertNotEquals($user2DefaultApiKey, $user2ApiKey);
      $this->assertNotEquals($user1ApiKey, $user2ApiKey);
    }
  }
