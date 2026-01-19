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
        if (! Schema::hasTable('lender_kyc_sessions')) {
            Schema::create('lender_kyc_sessions', function (Blueprint $table) {
                $table->id();

                $table->foreignId('lender_business_id')
                    ->constrained('businesses')
                    ->cascadeOnDelete();

                $table->foreignId('lender_mono_profile_id')
                    ->nullable()
                    ->constrained('lender_mono_profiles')
                    ->nullOnDelete();

                $table->string('prove_id')
                    ->index()
                    ->comment('Mono Prove session ID (data.id)');

                $table->string('reference')
                    ->index()
                    ->comment('Reference sent to Mono Prove');

                $table->string('mono_url')->nullable();

                $table->string('status')
                    ->default('pending')
                    ->comment('pending/successful/cancelled/expired/rejected');

                $table->string('kyc_level')->nullable();

                $table->boolean('bank_accounts')->default(false);

                $table->json('meta')->nullable();

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lender_kyc_sessions');
    }
};
