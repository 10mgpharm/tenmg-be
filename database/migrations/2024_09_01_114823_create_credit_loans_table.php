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
        Schema::create('credit_loans', function (Blueprint $table) {
            $table->id();

            $table->string('identifier')->nullable()->unique(); // system generated e.g LN-VENDOR_CODE-YEARMONTHDAY-PRIMARY_ID i.e LN-10MG-20240901-23

            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade'); // BusinessType::VENDOR
            $table->foreignId('customer_id')->constrained('credit_customers')->onDelete('cascade');
            $table->foreignId('application_id')->constrained('credit_applications')->onDelete('cascade');
            $table->foreignId('offer_id')->constrained('credit_offers')->onDelete('cascade');

            $table->decimal('capital_amount', 18, 2)->nullable();
            $table->decimal('interest_amount', 18, 2)->nullable();
            $table->decimal('total_amount', 18, 2)->nullable();

            $table->datetime('repaymemt_start_date')->nullable();
            $table->datetime('repaymemt_end_date')->nullable();

            $table->string('status')->nullable(); // OPEN->PENDING_DISBURSEMENT->DISBURSED->ONGOING_REPAYMENT->PAID/CLOSED

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_loans');
    }
};
