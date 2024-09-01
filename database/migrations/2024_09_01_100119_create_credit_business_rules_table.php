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
        Schema::create('credit_business_rules', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique()->comment('This is identifier of the rule set by admin at system level e.g MinTransactionMonths');
            $table->string('description')->nullable()->comment('Describes the rule e.g The minimum allowed number of transaciton months');
            $table->string('condition')->nullable()->comment('Describes condition e.g GreaterThan, LessThan, GreaterThanOrEqual, LessThanOrEqual, NotEqual, Equals, Includes, Excludes, Contains');
            $table->string('logical_operator')->nullable()->comment('Describes operator e.g >, <, >=, <=, !=, ==, ~, $, !~');

            // score_weight is uses to compute and determine score_percent, score_value and score_total stored on credit_scores table
            $table->float('score_weight')->default(10)->comment('determines the numeric value score assign if this rule is applied and it passes');
            $table->string('compare_value')->comment('value each rule is compare against when performing credit score on a txn_history_evaluation');
            $table->boolean('active')->default(true)->comment('determine if a rule is active or not');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_business_rules');
    }
};
