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
        if (! Schema::hasTable('credit_customer_debit_mandates')) {
            Schema::create('credit_customer_debit_mandates', function (Blueprint $table) {
                $table->id();

                $table->string('identifier')->nullable()->unique(); // system generated e.g ACCMNDT-VENDOR_CODE-YEARMONTHDAY-PRIMARY_ID i.e ACCMNDT-10MG-20240901-23
                $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade'); // BusinessType::VENDOR
                $table->foreignId('customer_id')->constrained('credit_customers')->onDelete('cascade');

                // ...mandate information
                // TODO: Check paystack documentation to run a new migration that add additional columns

                $table->index(['business_id', 'customer_id', 'identifier'], 'mandate_business_customer_identifier_unique');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_customer_debit_mandates');
    }
};
