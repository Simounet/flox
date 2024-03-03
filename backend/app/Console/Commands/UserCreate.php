<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Models\UserService;
use App\Traits\CommandInputValidator;
use Illuminate\Console\Command;

class UserCreate extends Command
{
    use CommandInputValidator;

    protected $signature = 'user:create {--username=} {--password=} {--is-admin=no}';
    protected $description = 'Create a new user';

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        parent::__construct();

        $this->userService = $userService;
    }

    public function handle(): void
    {
        $this->info('Creating a new user...');

        $infoText = $this->isCreatedFromOptions() || $this->isCreatedFromInputs()
            ? 'Successfully created user!'
            : 'User creation failed.';

        $this->info($infoText);
    }

    private function isCreatedFromInputs(): bool
    {
        $username = $this->getValidInput(
            'Username',
            function($username) {
                return $this->userService->isUsernameValid((string) $username);
            }
        );

        $password = $this->getValidInput(
            'Password',
            function($password) {
                return $this->userService->isPasswordValid((string) $password);
            },
            'secret'
        );

        $this->getValidInput(
            'Confirm Password',
            function($confirm) use ($password) {
                if($password !== $confirm) {
                    $this->error('Password mismatch, please try again...');
                    return false;
                }
                return true;
            },
            'secret'
        );

        $isAdmin = (string) $this->anticipate(
            'Should this user be an administrator? (yes/no)',
            ['yes', 'no']
        );

        if($this->confirm('Are you sure you want to create this user?')) {
            $this->userService->create($username, $password);
            $this->shouldBeAdmin($isAdmin, $username);
            return true;
        }

        $this->warn('Missing or empty arguments.');
        return false;
    }

    private function shouldBeAdmin(
        string $username,
        string $isAdmin
    ): bool
    {
        if($isAdmin === 'yes') {
            $this->userService->grantAdminPrivileges($username);

            return true;
        }

        return false;
    }

    private function isCreatedFromOptions(): bool
    {
        $options = $this->options();

        if(
            $options['username']
            && $options['password']
        ) {
            try {
                $this->userService->create($options['username'], $options['password']);
                $this->shouldBeAdmin($options['username'], $options['is-admin']);
                return true;
            } catch(\Exception $e) {
                $this->error($e->getMessage());
                exit;
            }

        }
        return false;
    }
}
