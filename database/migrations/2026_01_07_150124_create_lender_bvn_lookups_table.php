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
        Schema::create('lender_bvn_lookups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lender_business_id')
                ->constrained('businesses')
                ->cascadeOnDelete();
            $table->string('bvn_hash')->comment('Hashed BVN for secure storage');
            $table->string('session_id')->unique()->comment('Mono API session ID');
            $table->string('scope')->default('identity')->comment('identity or bank_accounts');
            $table->string('status')->default('initiated')->comment('initiated, verified, completed, failed');
            $table->string('verification_method')->nullable()->comment('phone, phone_1, alternate_phone, email');
            $table->string('phone_number')->nullable()->comment('Required for alternate_phone method');
            $table->json('verification_methods')->nullable()->comment('Available verification methods from Mono');
            $table->json('lookup_data')->nullable()->comment('BVN details or bank accounts from Mono');
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('session_id');
            $table->index('lender_business_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lender_bvn_lookups');
    }
};
