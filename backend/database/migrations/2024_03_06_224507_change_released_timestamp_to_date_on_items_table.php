<?php

use App\Models\Item;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dateTime('released_datetime')->nullable();
        });

        Item::query()->chunk(100, function (Collection $rows) {
            foreach($rows as $item) {
                $item->update([
                    'released_datetime' => Carbon::parse($item->released),
                ]);
            }
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('released_timestamp');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('released_datetime');
            $table->dateTime('released_timestamp')->nullable();
        });

        Item::query()->each(function (Item $item) {
            $item->update([
                'released_timestamp' => Carbon::parse($item->released),
            ]);
        });
    }
};
