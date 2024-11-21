<?php

  namespace Tests;
  
  use Illuminate\Foundation\Testing\DatabaseTransactions;
  use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
  use Illuminate\Support\Facades\Artisan;
  
  abstract class TestCase extends BaseTestCase {
    
    use CreatesApplication;
    use DatabaseTransactions;

    protected function setUp(): void
    {
      parent::setUp();

      Artisan::call('migrate');
    }
  }
