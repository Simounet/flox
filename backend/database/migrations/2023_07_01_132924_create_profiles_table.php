<?php

use App\Profile;
use App\Services\Fediverse\Activity\ActorActivity;
use App\Services\Models\ProfileService;
use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('domain')->index();
            $table->string('username')->index();
            $table->string('name');
            $table->string('avatar_url')->nullable();
            $table->string('inbox_url');
            $table->string('outbox_url');
            $table->string('shared_inbox_url')->nullable()->index();
            $table->string('key_id_url');
            $table->string('followers_url')->nullable();
            $table->string('following_url')->nullable();
            $table->text('public_key');
            $table->text('private_key')->nullable();
            $table->string('remote_url')->nullable()->index();
            $table->timestamps();
            $table->timestamp('last_fetched_at')->nullable()->index();

            $table->softDeletes();

            $table->unique(['domain', 'username']);
            $table->unique(['key_id_url']);
        });

        $users = User::all();
        foreach($users as $user) {
            (new ProfileService(new Profile()))->storeLocal($user);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profiles');
    }
};
