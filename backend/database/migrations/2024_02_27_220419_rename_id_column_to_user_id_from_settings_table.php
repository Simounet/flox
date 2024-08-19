<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $firstUser = User::select('id')->orderBy('id', 'asc')->first();
        // A default row created by the 2016_10_18_063806_create_settings_table migration exists
        // We need to remove it if no users found (like in testing conditions)
        // because user_id can't be null
        if(!$firstUser) {
            Setting::truncate();
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->first();
        });

        if($firstUser) {
            DB::statement("UPDATE settings SET user_id = $firstUser->id");
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->increments('id')->first();
        });

        DB::statement("UPDATE settings SET id = user_id");

        Schema::table('settings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
