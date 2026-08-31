<?php

use App\Models\EpisodeUser;
use App\Models\Item;
use App\Models\ItemUser;
use App\Models\Review;
use App\Models\UserActivities;
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
        Schema::create('item_user', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('item_id');
            $table->tinyInteger('rating')->nullable();
            $table->boolean('watchlist')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('item_id')->references('id')->on('items');
        });

        Review::select(['user_id', 'item_id', 'rating', 'watchlist', 'created_at', 'updated_at'])->chunk(500, function($rows) {
            foreach($rows as $row) {
                $data = [
                    'user_id' => $row->user_id,
                    'item_id' => $row->item_id,
                    'rating' => $row->rating,
                    'watchlist' => $row->watchlist,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at
                ];
                ItemUser::upsert(
                    $data,
                    [
                        'user_id',
                        'item_id'
                    ]
                );
            }
        });

        Schema::disableForeignKeyConstraints();
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('item_user_id')
                ->after('id')
                ->references('id')
                ->on('item_user')
                ->cascadeOnDelete();
        });

        ItemUser::query()
            ->select(['id', 'item_id'])
            ->orderBy('id')
            ->chunkById(500, function ($itemUsers) {
                foreach ($itemUsers as $itemUser) {
                    DB::table('reviews')
                        ->where('item_id', $itemUser->item_id)
                        ->update([
                            'item_user_id' => $itemUser->id,
                        ]);
                }
            });
        Schema::enableForeignKeyConstraints();

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropPrimary('item_id');
            $table->dropForeign(['item_id']);
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('item_user_id')->nullable(false)->change();
            $table->dropColumn(['item_id', 'user_id', 'watchlist']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->after('id');
            $table->unsignedInteger('item_id')->after('user_id');
            $table->boolean('watchlist')->default(false)->after('rating');
        });

        ItemUser::query()
            ->select(['id', 'item_id', 'user_id', 'watchlist'])
            ->orderBy('id')
            ->chunkById(500, function ($itemUsers) {
                foreach ($itemUsers as $itemUser) {
                    DB::table('reviews')
                        ->where('item_user_id', $itemUser->id)
                        ->update([
                            'item_id' => $itemUser->item_id,
                            'user_id' => $itemUser->user_id,
                            'watchlist' => $itemUser->watchlist,
                        ]);
                }
            });

        $unmappedReviews = DB::table('reviews')
            ->whereNull('item_id')
            ->orWhereNull('user_id')
            ->exists();

        if ($unmappedReviews) {
            throw new RuntimeException(
                'Cannot roll back: some reviews could not be mapped to item_id and user_id.'
            );
        }

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['item_user_id']);
            $table->dropColumn('item_user_id');

            $table->primary(['user_id', 'item_id']);
            $table->index('item_id', 'reviews_item_id_foreign');
            $table->index('user_id', 'reviews_user_id_foreign');
            $table->foreign('item_id', 'reviews_item_id_foreign')
                ->references('id')
                ->on('items');

            $table->foreign('user_id', 'reviews_user_id_foreign')
                ->references('id')
                ->on('users');
        });
        Schema::dropIfExists('item_user');
    }
};
