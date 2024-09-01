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
        Schema::create('credit_repayment_debit_cards', function (Blueprint $table) {
            $table->id();

            $table->string('identifier')->nullable()->unique(); // system generated e.g DCRDS-VENDOR_CODE-YEARMONTHDAY-PRIMARY_ID i.e DCRDS-10MG-20240901-23
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade'); // BusinessType::VENDOR
            $table->foreignId('customer_id')->constrained('credit_customers')->onDelete('cascade');

            // ...mandate information
            // TODO: Check paystack documentation to implement auto-debit card authorisation and add additional columns

            $table->index(['business_id', 'customer_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_repayment_debit_cards');
    }
};
