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
        Schema::table('ecommerce_products', function (Blueprint $table) {

            // Add 'status' column if it doesn't exist
            if (! Schema::hasColumn('ecommerce_products', 'status')) {
                $table->string('status')->default(StatusEnum::PENDING->value)
                    ->nullable()
                    ->comment('The status of the record. Possible values: ' . implode(', ', array_map(fn($case) => $case->value, StatusEnum::cases())));
            } else {
                // Update 'status' column if it exists
                $table->string('status')->default(StatusEnum::PENDING->value)
                    ->nullable()
                    ->comment('The status of the record. Possible values: ' . implode(', ', array_map(fn($case) => $case->value, StatusEnum::cases())))
                    ->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_products', function (Blueprint $table) {
            //
        });
    }
};
