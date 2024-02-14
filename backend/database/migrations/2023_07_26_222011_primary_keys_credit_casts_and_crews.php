<?php

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
        Schema::table('credit_crews', function (Blueprint $table) {
            $table->dropPrimary('person_id');
        });
        Schema::table('credit_casts', function (Blueprint $table) {
            $table->dropPrimary('person_id');
        });
        Schema::table('credit_crews', function (Blueprint $table) {
            $table->primary(['tmdb_id', 'person_id']);
        });
        Schema::table('credit_casts', function (Blueprint $table) {
            $table->primary(['tmdb_id', 'person_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_crews', function (Blueprint $table) {
            $table->dropPrimary(['tmdb_id', 'person_id']);
        });
        Schema::table('credit_casts', function (Blueprint $table) {
            $table->dropPrimary(['tmdb_id', 'person_id']);
        });
        Schema::table('credit_crews', function (Blueprint $table) {
            $table->primary('person_id');
        });
        Schema::table('credit_casts', function (Blueprint $table) {
            $table->primary('person_id');
        });
    }
};
