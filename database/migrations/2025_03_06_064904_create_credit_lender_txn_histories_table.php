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
        Schema::create('credit_lender_txn_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lender_id');//same as business_id
            $table->string('identifier');
            $table->decimal('amount', 10,2);
            $table->enum('type', ['Deposit', 'Withdrawal', 'Loan Disbursal', 'Loan Repayment']);
            $table->string('description');
            $table->string('status');
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();

            $table->string('transactionable_type')->nullable(); // Polymorphic type
            $table->unsignedBigInteger('transactionable_id')->nullable(); // Polymorphic ID
            $table->index(
                ['transactionable_type', 'transactionable_id'],
                'txn_hist_transactionable_index' // Custom index name
            );

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_lender_txn_histories');
    }
};
