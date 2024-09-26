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
        Schema::dropIfExists('credit_customer_debit_mandates');

        Schema::create('credit_customer_debit_mandates', function (Blueprint $table) {
            $table->id();

            $table->string('identifier')->unique(); // system generated e.g ACCMNDT-VENDOR_CODE-YEARMONTHDAY-PRIMARY_ID i.e ACCMNDT-10MG-20240901-23
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade'); // BusinessType::VENDOR
            $table->foreignId('customer_id')->constrained('credit_customers')->onDelete('cascade');

            $table->string('reference')->unique();
            $table->string('authorization_code')->nullable();
            $table->boolean('active')->default(false);
            $table->string('last4')->nullable();
            $table->string('channel')->nullable();
            $table->string('card_type')->nullable();
            $table->string('bank')->nullable();
            $table->integer('exp_month')->nullable();
            $table->integer('exp_year')->nullable();
            $table->string('country_code')->nullable();
            $table->string('brand')->nullable();
            $table->boolean('reusable')->default(false);
            $table->string('signature')->nullable();
            $table->string('account_name')->nullable();
            $table->string('integration')->nullable();
            $table->string('domain')->nullable();

            $table->boolean('chargeable')->default(false);

            $table->index(['business_id', 'customer_id'], 'mandate_business_customer_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_customer_debit_mandates');
    }
};
