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
        if (! Schema::hasTable('credit_disbursements')) {
            Schema::create('credit_disbursements', function (Blueprint $table) {
                $table->id();

                $table->string('identifier')->nullable()->unique(); // system generated e.g DIS-VENDOR_CODE-YEARMONTHDAY-PRIMARY_ID i.e DIS-10MG-20240901-23

                $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade'); // BusinessType::VENDOR
                $table->foreignId('customer_id')->constrained('credit_customers')->onDelete('cascade');
                $table->foreignId('application_id')->constrained('credit_applications')->onDelete('cascade');
                $table->foreignId('loan_id')->constrained('credit_loans')->onDelete('cascade');

                $table->decimal('disbursed_amount', 18, 2)->nullable()->comment('10mg voucher amount disbursed');
                $table->string('voucher_code')->nullable(); // system generated e.g 10MG-GENERATED_CHAR-TIMESTAMO i.e 10MG-RC93JKDSJKNK-2024090123001
                $table->string('status')->nullable(); // PENDING, CANCELLED, DISBURSED

                // TODO: Support disbursing to customer bank account in future if required
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_disbursement');
    }
};
