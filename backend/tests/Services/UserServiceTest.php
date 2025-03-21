<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\Profile;
use App\Models\Setting;
use App\Models\User;
use App\Services\Models\UserService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\Factories;

class UserServiceTest extends TestCase
{
    use DatabaseTransactions;
    use Factories;

    private UserService $userService;

    public function setUp(): void
    {
        parent::setUp();

        $this->userService = app(UserService::class);
    }

    #[Test]
    public function it_should_create_a_user(): void
    {
        $newUser = $this->userService->create('user1', '0123456789');
        $this->assertTrue($newUser instanceof User);
    }

    #[Test]
    public function it_should_create_multiple_users_with_different_usernames(): void
    {
        $newUser1 = $this->userService->create('user1', '0123456789');
        $newUser2 = $this->userService->create('user2', '0123456789');
        $this->assertTrue($newUser1 instanceof User);
        $this->assertTrue($newUser2 instanceof User);
    }

    #[Test]
    public function it_should_verify_password_change_success(): void
    {
        $user = $this->userService->create('user1', 'password');
        $this->actingAs($user);
        $passwordChanged = $this->userService->changePassword('newPassword');

        $this->assertTrue($passwordChanged);
    }

    #[Test]
    public function it_should_fail_changing_password_not_logged_in(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Authenticated user only');

        $this->userService->create('user1', 'password');
        $passwordChanged = $this->userService->changePassword('newPassword');
    }

    #[Test]
    public function it_should_valid_username(): void
    {
        $isUsernameValid = $this->userService->isUsernameValid('username');
        $this->assertTrue($isUsernameValid);
    }

    #[Test]
    public function it_should_fail_creating_user_with_existing_username(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pick another username');

        $username = 'user1';

        $this->userService->create($username, '0123456798');
        $this->userService->isUsernameValid($username);
    }

    #[Test]
    public function it_should_fail_creating_user_with_empty_username(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Username cannot be empty');

        $this->userService->isUsernameValid('');
    }

    #[Test]
    public function it_should_fail_creating_user_without_enough_characters(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Password should be composed by at least ' . UserService::PASSWORD_MIN_LENGTH. ' characters');

        $this->userService->isPasswordValid('pass');
    }

    #[Test]
    public function it_should_fail_creating_user_with_empty_password(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Password cannot be empty');

        $this->userService->isPasswordValid('');
    }

    #[Test]
    public function it_should_verify_settings_created(): void
    {
        $initialSettingsCount = Setting::count();

        $this->userService->create('user', '0123456798');
        $settingsCount = Setting::count();

        $this->assertEquals(0, $initialSettingsCount);
        $this->assertEquals(1, $settingsCount);
    }

    #[Test]
    public function it_should_verify_profile_created(): void
    {
        $serverProfileCount = Profile::count();

        $this->userService->create('user', '0123456798');
        $profileCount = Profile::count();

        $this->assertEquals(1, $serverProfileCount);
        $this->assertEquals(2, $profileCount);
    }
}
