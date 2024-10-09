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
        Schema::create('ecommerce_bank_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')->constrained('businesses')->cascadeOnDelete();

            $table->string('bank_name');
            $table->string('bank_code');

            $table->string('account_name');
            $table->string('account_number')->index();
            $table->boolean('active')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_bank_accounts');
    }
};
