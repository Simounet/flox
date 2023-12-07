<?php

use App\Profile;
use App\Services\Models\ProfileService;
use App\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $profile = new Profile();
        $profile->followers_url = '';
        $profile->following_url = '';

        $user = new User();
        $user->id = null;
        $user->username = env('APP_DOMAIN');

        Profile::unguard();
        (new ProfileService($profile))->storeLocal($user, Profile::INSTANCE_ACTOR_ID);
        Profile::reguard();
    }

    public function down(): void
    {
        $profile = Profile::where('id', Profile::INSTANCE_ACTOR_ID)->first();
        $profile->delete();
    }
};
