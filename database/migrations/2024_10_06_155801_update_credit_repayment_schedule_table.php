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
        Schema::table('credit_repayment_schedules', function (Blueprint $table) {

            $table->after('payment_id', function($table) {
                $table->decimal('principal', 18, 2);
                $table->decimal('interest', 18, 2);
                $table->decimal('balance', 18, 2);
            });
            $table->renameColumn('amount', 'total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credit_repayment_schedules', function (Blueprint $table) {
            $table->renameColumn('total_amount', 'amount');
            $table->dropColumn(['principal', 'interest', 'balance']);
        });
    }
};
