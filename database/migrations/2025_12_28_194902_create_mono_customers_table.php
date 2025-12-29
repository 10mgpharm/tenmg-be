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
        Schema::create('mono_customers', function (Blueprint $table) {
            $table->id();
            $table->string('mono_customer_id')->nullable()->unique()->comment('Mono API customer ID');
            $table->string('bvn_hash')->unique()->comment('Hashed BVN for secure lookup');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('vendor_business_id')
                ->nullable()
                ->constrained('businesses')
                ->onDelete('set null');
            $table->timestamps();

            // Index for faster lookups
            $table->index('bvn_hash');
            $table->index('mono_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mono_customers');
    }
};
