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
        if (! Schema::hasTable('lender_mono_profiles')) {
            Schema::create('lender_mono_profiles', function (Blueprint $table) {
                $table->id();

                $table->foreignId('lender_business_id')
                    ->unique()
                    ->constrained('businesses')
                    ->cascadeOnDelete();

                $table->string('mono_customer_id')
                    ->nullable()
                    ->unique()
                    ->comment('Mono API customer ID representing this lender');

                $table->string('identity_type')
                    ->comment('BVN or NIN');

                $table->string('identity_hash')
                    ->unique()
                    ->comment('Hashed BVN/NIN for secure lookup');

                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->text('address')->nullable();

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lender_mono_profiles');
    }
};
