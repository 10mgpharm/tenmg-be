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
        if (! Schema::hasTable('credit_offers')) {
            Schema::create('credit_offers', function (Blueprint $table) {
                $table->id();

                $table->string('identifier')->nullable()->unique(); // system generated e.g LO-VENDOR_CODE-YEARMONTHDAY-PRIMARY_ID i.e LO-10MG-20240901-23
                $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade'); // BusinessType::VENDOR
                $table->foreignId('customer_id')->constrained('credit_customers')->onDelete('cascade');
                $table->foreignId('application_id')->constrained('credit_applications')->onDelete('cascade');

                $table->decimal('offer_amount', 18, 2)->nullable();
                $table->json('repayment_breakdown', 18, 2)->nullable()->comment('generated the repayment breakdown and if offer is accepted, use it to create the loan repayment schedules');

                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->text('rejection_reason')->nullable()->comment('specify rejection reason');

                $table->boolean('has_mandate')->default(false)->comment('specify if a customer has account mandate setup for repayment');
                $table->boolean('has_active_debit_card')->default(false)->comment('specify if a customer has_active_debit_card for repayment');

                // if offer is approved and not valid; disbursement should not happen
                // disbursement happen only if customer accept offer and they have has_mandate or has_active_debit_card setup for repayment
                $table->boolean('is_valid')->default(false)->comment('offer can only be valid if customer has_mandate or has_active_debit_card');

                $table->unique(['business_id', 'customer_id', 'identifier'], 'offer_business_customer_identifier_unique');
                $table->timestamps();
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_offers');
    }
};
