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
        Schema::create('credit_scores', function (Blueprint $table) {
            $table->id();

            $table->string('identifier')->nullable()->unique(); // system generated e.g CSCORES-VENDOR_CODE-YEARMONTHDAY-PRIMARY_ID i.e CSCORES-10MG-20240901-23
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade'); // BusinessType::VENDOR
            $table->foreignId('customer_id')->constrained('credit_customers')->onDelete('cascade');

            // linked the most active evaluation result used for the credit score check
            $table->foreignId('txn_evaluation_id')->nullable()->constrained('credit_txn_history_evaluations')->onDelete('cascade');

            // since business rules can be set to active or not, the system need to pick the current business rules used to run this check
            $table->json('business_rule_json')->nullable()->comment('store the current business rule used to perform this credit score that yield the credit_score_result');
            $table->json('credit_score_result')->nullable()->comment('store result of the business rules comparison with txn history evaluation result');

            $table->float('score_percent')->nullable();
            $table->float('score_value')->nullable();
            $table->float('score_total')->nullable();

            $table->foreignId('created_by_id')->nullable()->constrained('business_users')->onDelete('cascade');

            $table->enum('source', ['DASHBOARD', 'API']);

            $table->unique(['business_id', 'customer_id', 'identifier'], 'score_business_customer_identifier_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_scores');
    }
};
