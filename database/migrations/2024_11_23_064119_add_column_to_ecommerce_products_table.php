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
                $table->enum('status', array_map(fn ($status) => $status->value, StatusEnum::cases()))
                    ->default(StatusEnum::PENDING->value)
                    ->nullable();
            } else {
                // Update 'status' column if it exists
                $table->enum('status', array_map(fn ($status) => $status->value, StatusEnum::cases()))
                    ->default(StatusEnum::PENDING->value)
                    ->nullable()
                    ->change();
            }

            // Add 'status_comment' column if it doesn't exist
            if (! Schema::hasColumn('ecommerce_products', 'status_comment')) {
                $table->text('status_comment')->nullable();
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
