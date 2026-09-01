<?php

declare(strict_types=1);

namespace Tests\Services\Fediverse;

use App\Services\Fediverse\ActivityPubFetchService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityPubFetchServiceTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_should_wrap_connection_failures_without_a_fatal_error(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Could not connect');
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageIsOrContains('Could not connect');

        new ActivityPubFetchService()->get('https://93.184.216.34/users/alice');
    }
}
