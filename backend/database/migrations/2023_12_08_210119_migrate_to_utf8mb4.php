<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/* From https://gist.github.com/hrsa/7a45420329e745315ee02a08ddbf3d41 Thanks Anton! */

return new class extends Migration {

    public function up(): void
    {
        $this->migrateCharsetTo('utf8mb4', 'utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        $this->migrateCharsetTo('utf8', 'utf8_unicode_ci');
    }

    protected function migrateCharsetTo($charset, $collation): void
    {
        $defaultConnection = config('database.default');
        if($defaultConnection !== 'mysql') {
            return;
        }
        $databaseName = config("database.connections.{$defaultConnection}.database");

        // Change default charset and collation
        DB::unprepared("ALTER SCHEMA {$databaseName} DEFAULT CHARACTER SET {$charset} DEFAULT COLLATE {$collation};");

        // Get the list of all tables
        $tableNames = DB::table('information_schema.tables')
            ->where('table_schema', $databaseName)
            ->where('table_type', '=', 'BASE TABLE')
            ->get(['TABLE_NAME'])
            ->pluck('TABLE_NAME');

        // Iterate through the list and alter each table
        foreach ($tableNames as $tableName) {
            DB::unprepared("ALTER TABLE {$tableName} CONVERT TO CHARACTER SET {$charset} COLLATE {$collation};");

        }


        // Get the list of all columns in the active db that have a collation
        $columns = DB::table('information_schema.columns')
            ->where('table_schema', $databaseName)
            ->whereIn('table_name', $tableNames)
            ->whereNotNull('COLLATION_NAME')
            ->get();

        // Iterate through the list and alter each column
        foreach ($columns as $column) {
            $tableName = $column->TABLE_NAME;
            $columnName = $column->COLUMN_NAME;
            $columnType = $column->COLUMN_TYPE;

            // Skip strange columns with char() type

            if (strpos($columnType, "char(") === 0) {
                continue;
            }

            // Check for default value in nullable and not nullable columns

            if ($column->IS_NULLABLE == 'NO') {
                if ($column->COLUMN_DEFAULT !== null) {
                    $default = "DEFAULT '" . $column->COLUMN_DEFAULT . "'";
                } else {
                    $default = 'NOT NULL';
                }
            } elseif ($column->COLUMN_DEFAULT !== null) {
                $default = "DEFAULT '" . $column->COLUMN_DEFAULT . "'";
            } else {
                $default = 'DEFAULT NULL';
            }

            $sql = "ALTER TABLE {$tableName}
                    CHANGE `{$columnName}` `{$columnName}`
                    {$columnType}
                    CHARACTER SET {$charset}
                    COLLATE {$collation}
                    {$default}";

            DB::unprepared($sql);
        }
    }
};

