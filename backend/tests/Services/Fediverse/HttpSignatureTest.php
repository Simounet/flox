<?php

namespace Tests\Services\Fediverse;

use App\Models\Profile;
use App\Services\Fediverse\Activity\ActorActivity;
use App\Services\Fediverse\HttpSignature;
use App\Services\Models\ProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\HeaderBag;
use Tests\TestCase;

class HttpSignatureTest extends TestCase
{

    use DatabaseMigrations;

    private $host;
    private $profile;
    private $profileService;

    public function setUp(): void
    {
        parent::setUp();

        $this->host = parse_url(env('APP_URL'))['host'];
        $user = User::factory()->create();
        $this->profileService = new ProfileService(new Profile());
        $this->profile = $this->profileService->storeLocal($user);
        $person = (new ActorActivity())->actorObject($this->profile);
        Http::fake([
            $this->profile->remote_url => Http::response($person->toJson())
        ]);
    }

    /** @test */
    public function shouldSignAndVerifySignature(): void
    {
        $payload = '{"id": "' . $this->profile->remote_url . '#Follow", "actor": "' . $this->profile->remote_url . '"}';
        $headers = (new HttpSignature)->sign(
            $this->profile->shared_inbox_url,
            $this->profile->private_key,
            $this->profile->key_id_url,
            $payload
        );
        $headerBag = new HeaderBag($headers);
        $verifiedSignature = (new HttpSignature())->verifySignature('post', '/inbox', $headerBag, $payload);
        $this->assertTrue($verifiedSignature);
    }
}
