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
        Schema::create('credit_lender_bank_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lender_id')->constrained('businesses')->cascadeOnDelete();

            $table->string('bank_name');
            $table->string('bank_code');

            $table->string('account_name');
            $table->string('account_number')->index();
            $table->boolean('active')->default(true);
            $table->string('bvn');
            $table->boolean('is_bvn_verified')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_lender_bank_accounts');
    }
};
