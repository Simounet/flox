<?php

declare(strict_types=1);

namespace App\Services\Models;

use ActivityPhp\Type\AbstractObject;
use App\Profile;
use App\User;

class ProfileService
{
    private Profile $profile;

    public function __construct(Profile $profile)
    {
        $this->profile = $profile;
    }

    public function storeLocal(User $user, int $forcedId = null): Profile
    {
        $profileExists = Profile::select('user_id')->where('user_id', $user->id)->first();
        if($profileExists) {
            throw new \Exception('Local profile already created');
        }

        $this->profile->username = $user->username;
        $snowflake = app('Kra8\Snowflake\Snowflake');
        $keys = $this->generateKeys();
        $profile = [
            'id' => $forcedId ?? $snowflake->next(),
            'user_id' => $user->id,
            'domain' => env('APP_DOMAIN'),
            'username' => $user->username,
            'name' => $user->username,
            'shared_inbox_url' => $this->sharedInboxUrl(),
            'inbox_url' => $this->inboxUrl(),
            'outbox_url' => $this->outboxUrl(),
            'key_id_url' => $this->mainKey(),
            'followers_url' => $this->followersUrl(),
            'following_url' => $this->followingUrl(),
            'public_key' => $keys['public'],
            'private_key' => $keys['private'],
            'remote_url' => self::user(),
        ];

        return Profile::create($profile);
    }

    public function updateOrCreate(AbstractObject $person): Profile
    {
        $snowflake = app('Kra8\Snowflake\Snowflake');
        $domain = parse_url($person->get('url'))['host'];
        $entity = [
            'id' => $snowflake->next(),
            'domain' => $domain,
            'username' => $person->get('preferredUsername') . '@' . $domain,
            'name' => $person->get('name'),
            'shared_inbox_url' => $person->get('endpoints')['sharedInbox'],
            'inbox_url' => $person->get('inbox'),
            'outbox_url' => $person->get('outbox'),
            'key_id_url' => $person->get('publicKey')['id'],
            'followers_url' => $person->get('followers'),
            'following_url' => $person->get('following'),
            'public_key' => $person->get('publicKey')['publicKeyPem'],
            'remote_url' => $person->get('url'),
        ];

        return Profile::updateOrCreate(['domain' => $entity['domain'], 'username' => $entity['username']], $entity);
    }

    public function user(): string
    {
        return $this->profile->remote_url ?? route('federation.user', ['username' => $this->profile->username]);
    }

    public function mainKey(): string
    {
        return $this->profile->key_id ?? self::user() . '#main-key';
    }

    public function inboxUrl(): string
    {
        return $this->profile->inbox_url ?? route('federation.user.inbox', ['username' => $this->profile->username]);
    }

    public function outboxUrl(): string
    {
        return $this->profile->outbox_url ?? route('federation.user.outbox', ['username' => $this->profile->username]);
    }

    public function sharedInboxUrl(): string
    {
        return $this->profile->shared_inbox_url ?? route('federation.shared-inbox');
    }

    public function followingUrl(): string
    {
        return $this->profile->following_url ?? route('federation.user.following', ['username' => $this->profile->username]);
    }

    public function followersUrl(): string
    {
        return $this->profile->followers_url ?? route('federation.user.followers', ['username' => $this->profile->username]);
    }


    private function generateKeys(): array
    {
        $pkiConfig = [
            'digest_alg'       => 'sha512',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $pki = openssl_pkey_new($pkiConfig);
        openssl_pkey_export($pki, $pkiPrivate);
        $pkiPublic = openssl_pkey_get_details($pki)['key'];
        return [
            'public' => $pkiPublic,
            'private' => $pkiPrivate,
        ];
    }

    public function acceptFollowsId(Profile $sourceProfile, Profile $targetProfile): string
    {
        return $sourceProfile->remote_url . '#accepts/follows/' . $targetProfile->id;
    }
}
