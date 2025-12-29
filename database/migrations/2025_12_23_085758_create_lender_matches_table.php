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
        Schema::create('lender_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_business_id')
                ->constrained('businesses')
                ->cascadeOnDelete();
            $table->foreignId('lender_business_id')
                ->constrained('businesses')
                ->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('NGN');
            $table->unsignedInteger('default_tenor');
            $table->string('borrower_reference')->unique();
            $table->json('transaction_history')->nullable();
            $table->json('product_items')->nullable();
            $table->text('callback_url')->nullable();
            $table->string('status')->default('matched');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lender_matches');
    }
};
