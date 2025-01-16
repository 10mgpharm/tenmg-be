<?php

use App\Enums\StatusEnum;
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
        Schema::table('users', function (Blueprint $table) {
             // Add 'status' column if it doesn't exist
            if (! Schema::hasColumns('users', ['status', 'status_comment'])) {
                $table->string('status')->default(StatusEnum::ACTIVE->value)
                    ->nullable()
                    ->comment('The status of the record. Possible values: ' . implode(', ', array_map(fn($case) => $case->value, StatusEnum::cases())));

                    $table->text('status_comment')->nullable();
            
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
