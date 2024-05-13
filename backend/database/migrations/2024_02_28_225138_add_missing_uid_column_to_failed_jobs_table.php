<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->string('uuid')->unique()->nullable()->after('id');
        });

        $failedJobs = DB::table('failed_jobs')->get();

        foreach ($failedJobs as $failedJob) {
            $uuid = (string) Str::uuid();
            DB::table('failed_jobs')
                ->where('id', $failedJob->id)
                ->update(['uuid' => $uuid]);
        }

        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->string('uuid')->unique()->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('failed_jobs', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
