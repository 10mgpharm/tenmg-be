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
        Schema::create('mono_mandates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lender_match_id')
                ->constrained('lender_matches')
                ->cascadeOnDelete();
            $table->foreignId('mono_customer_id')
                ->nullable()
                ->constrained('mono_customers')
                ->nullOnDelete();

            $table->string('mandate_id')->unique()->index()
                ->comment('Mono mandate ID (e.g., mmc_6d4bdf870f11fef8f6f1c7a0)');
            $table->string('reference')->index()
                ->comment('Unique reference for this mandate');
            $table->string('mono_url')->nullable()
                ->comment('Mono authorization URL');

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'expired'])
                ->default('pending')
                ->index();

            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('NGN');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('description')->nullable();
            $table->string('redirect_url')->nullable();

            $table->json('meta')->nullable()
                ->comment('Additional metadata from Mono API');
            $table->json('mono_response')->nullable()
                ->comment('Full response from Mono API');

            $table->boolean('is_mock')->default(false)
                ->comment('Whether this is a mock/dummy response');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mono_mandates');
    }
};
