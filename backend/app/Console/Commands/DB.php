<?php

  namespace App\Console\Commands;

  use Illuminate\Console\Command;
  use Illuminate\Support\Facades\DB as LaravelDB;

  class DB extends Command {

    protected $signature = 'flox:db {--fresh : Whether all data should be reset} {username?} {password?}';
    protected $description = 'Create database migrations and admin account';

    public function __construct()
    {
      parent::__construct();
    }

    public function handle()
    {
      if($this->option('fresh')) {
        $this->alert('ALL DATA WILL BE REMOVED');
      }
      
      try {
        $this->createMigrations();
      } catch(\Exception $e) {
        $this->error('Can not connect to the database. Error: ' . $e->getMessage());
        $this->error('Make sure your database credentials in .env are correct');

        return false;
      }
      
      $this->createUser();
    }

    private function createMigrations()
    {
      $this->info('TRYING TO MIGRATE DATABASE');
      
      if($this->option('fresh')) {
        $this->call('migrate:fresh', ['--force' => true]);
      } else {
        $this->call('migrate', ['--force' => true]);
      }
      
      $this->info('MIGRATION COMPLETED');
    }

    private function createUser(): void
    {
      $username = $this->ask('Enter your admin username', $this->argument("username"));
      $password = $this->ask('Enter your admin password', $this->argument("password"));

      if($this->option('fresh')) {
        LaravelDB::table('users')->delete();
      }
      
      $this->userService->create($username, $password);
    }
  }
