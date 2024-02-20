<?php

use App\Models\Review;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $user = User::orderBy('id')->first();
        Schema::table('reviews', function (Blueprint $table) {
            $table->tinyInteger('rating')->nullable()->after('item_id');
            $table->boolean('watchlist')->default(false)->after('rating');
            $table->longText('content')->nullable()->change();
            $table->unsignedBigInteger('id', false)->change();
            $table->dropPrimary();
            $table->primary(['user_id', 'item_id']);
            $table->index('id');
        });
        Item::query()->orderBy('id')->chunk('1000', function($rows) use ($user) {
            $dataToUpdate = [];
            $snowflake = app('Kra8\Snowflake\Snowflake');
            foreach($rows as $row) {
                $dataToUpdate[] = [
                    'id' => $snowflake->next(),
                    'user_id' => $user->id,
                    'item_id' => $row->id,
                    'rating' => $row->rating,
                    'watchlist' => $row->watchlist
                ];
            }
            Review::upsert(
                $dataToUpdate,
                [
                    'user_id',
                    'item_id'
                ],
                [
                    'rating',
                    'watchlist'
                ]
            );
        });
        Schema::table('items', function(Blueprint $table) {
            $table->dropColumn('rating', 'watchlist');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function(Blueprint $table) {
            $table->string('rating')->after('media_type');
            $table->boolean('watchlist')->default(false)->after('imdb_rating');
        });
        Review::query()->orderBy('id')->chunk('1000', function($rows) {
            foreach($rows as $row) {
                Item::where('id', $row->item_id)
                    ->update([
                        'rating' => $row->rating,
                        'watchlist' => $row->watchlist
                    ]);
            }
        });
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_id_index');
            $table->dropForeign(['user_id']);
            $table->dropForeign(['item_id']);
            $table->dropPrimary();
            $table->primary('id');
            $table->dropColumn('rating', 'watchlist');
            Review::whereNull('content')->delete();
        });
    }
};
