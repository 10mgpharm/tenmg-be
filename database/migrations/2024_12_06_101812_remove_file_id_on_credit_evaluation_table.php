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
        // check if constraint exists
        $constraintExists = Schema::hasColumn('credit_txn_history_evaluations', 'evaluation_business_customer_file_unique');
        if ($constraintExists) {
            Schema::table('credit_txn_history_evaluations', function (Blueprint $table) {
                $table->dropUnique('evaluation_business_customer_file_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $constraintExists = Schema::hasColumn('credit_txn_history_evaluations', 'evaluation_business_customer_file_unique');
        if (! $constraintExists) {
            Schema::table('credit_txn_history_evaluations', function (Blueprint $table) {
                $table->unique(['business_id', 'customer_id', 'transaction_file_id'], 'evaluation_business_customer_file_unique');
            });
        }
    }
};
