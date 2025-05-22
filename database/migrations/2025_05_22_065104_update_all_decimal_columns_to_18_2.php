<?php

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

        $tables = DB::select('SHOW TABLES');

foreach ($tables as $table) {
    $tableName = reset($table); // Gets the table name

    // Get all columns for the table
    $columns = Schema::getColumnListing($tableName);

    foreach ($columns as $column) {
        // Get column type using raw query
        $columnInfo = DB::selectOne(
            "SHOW COLUMNS FROM `{$tableName}` WHERE Field = ?",
            [$column]
        );

        if (str_contains($columnInfo->Type, 'decimal')) {
            // Check if column is nullable
            $isNullable = $columnInfo->Null === 'YES';

            // Change the decimal column to 18,2 while preserving nullability
            Schema::table($tableName, function (Blueprint $table) use ($column, $isNullable) {
                $columnDefinition = $table->decimal($column, 18, 2);

                // Preserve the original nullability
                if ($isNullable) {
                    $columnDefinition->nullable();
                } else {
                    $columnDefinition->nullable(false);
                }

                $columnDefinition->change();
            });

            // Optional: Log the change
            echo "Updated {$tableName}.{$column} to DECIMAL(18,2) " .
                 ($isNullable ? "NULL" : "NOT NULL") . "\n";
        }
    }
}


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
