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
        if(!Schema::hasTable('ecommerce_store_addresses')){
            Schema::create('ecommerce_store_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete()->index();
                $table->string('country');
                $table->string('state');
                $table->string('city');
                $table->string('closest_landmark')->nullable();
                $table->string('street_address');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_store_addresses');
    }
};
