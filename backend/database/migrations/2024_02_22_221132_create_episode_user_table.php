<?php

use App\Models\Episode;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $user = User::orderBy('id')->first();

        Schema::create('episode_user', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('episode_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('episode_id')->references('id')->on('episodes')->onDelete('cascade');
            $table->primary(['user_id', 'episode_id']);
        });

        Episode::where('seen', 1)->orderBy('id')->chunk('1000', function($rows) use ($user) {
            $dataToUpdate = [];
            foreach($rows as $row) {
                $dataToUpdate[] = [
                    'user_id' => $user->id,
                    'episode_id' => $row->id,
                    'created_at' => $row->updated_at,
                    'updated_at' => $row->updated_at
                ];
            }
            DB::table('episode_user')->upsert(
                $dataToUpdate,
                [
                    'user_id',
                    'episode_id'
                ],
                [
                    'created_at',
                    'updated_at'
                ]
            );
        });

        Schema::table('episodes', function(Blueprint $table) {
            $table->dropColumn('seen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('episodes', function(Blueprint $table) {
            $table->integer('seen')->default(0)->after('episode_tmdb_id');
        });

        DB::table('episode_user')->orderBy('episode_id')->chunk('1000', function($rows) {
            foreach($rows as $row) {
                Episode::withoutTimestamps(function () use($row) {
                    return Episode::where('id', $row->episode_id)->update([
                        'seen' => 1
                    ]);
                });
            }
        });

        Schema::dropIfExists('episode_user');
    }
};
