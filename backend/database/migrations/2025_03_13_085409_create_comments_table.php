<?php

declare(strict_types=1);

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
        Schema::table('reviews', function (Blueprint $table) {
            $table->unique('id');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->unique('id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id()->unique();
            $table->unsignedBigInteger('profile_id');
            $table->char('review_id');
            $table->longText('content');
            $table->string('source_url');
            $table->tinytext('language');
            $table->boolean('sensitive')->default(false);
            $table->timestamps();

            $table->foreign('profile_id')->references('id')->on('profiles');
            $table->foreign('review_id')->references('id')->on('reviews');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropUnique(['id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['id']);
        });
    }
};
