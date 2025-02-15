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
        Schema::create('credit_fincra_debit_mandates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->nullable()->constrained('businesses')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('credit_customers')->onDelete('cascade');
            $table->foreignId('application_id')->nullable()->constrained('credit_applications')->onDelete('cascade');

            $table->decimal('amount', 18, 2); // total to be deducted from the customer
            $table->string('description'); // system provided description
            $table->text('response_description'); // NIBSS provided description

            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();

            $table->string('customer_account_number')->nullable();
            $table->string('customer_account_name')->nullable();
            $table->string('customer_bank_code')->nullable();
            $table->string('customer_address')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();

            $table->string('reference')->nullable()->unique();
            $table->enum('status', ['initiated', 'approved', 'completed', 'failed', 'pending'])->default('initiated');

            $table->string('currency')->nullable()->default('NGN');

            $table->json('response')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_fincra_debit_mandates');
    }
};
