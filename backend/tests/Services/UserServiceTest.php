<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Models\User;
use App\Services\Models\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\Factories;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;
    use Factories;

    private UserService $userService;

    public function setUp(): void
    {
        parent::setUp();

        $this->userService = app(UserService::class);
    }

    /** @test */
    public function it_should_create_a_user(): void
    {
        $newUser = $this->userService->create('user1', 'test');
        $this->assertTrue($newUser instanceof User);
    }

    /** @test */
    public function it_should_create_multiple_users_with_different_usernames(): void
    {
        $newUser1 = $this->userService->create('user1', 'test');
        $newUser2 = $this->userService->create('user2', 'test');
        $this->assertTrue($newUser1 instanceof User);
        $this->assertTrue($newUser2 instanceof User);
    }

    /** @test */
    public function it_should_fail_at_creating_user_with_existing_username(): void
    {
        $username = 'user1';
        $this->userService->create($username, 'test');
        $existingUsernameCreated = $this->userService->create($username, 'test');
        $this->assertFalse($existingUsernameCreated);
    }
}
