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
        if (! Schema::hasTable('credit_repayment_schedules')) {
            Schema::create('credit_repayment_schedules', function (Blueprint $table) {
                $table->id();

                $table->foreignId('loan_id')->constrained('credit_loans')->onDelete('cascade');
                $table->unsignedBigInteger('payment_id')->nullable()->index(); //repayment_log_id

                $table->decimal('amount', 18, 2)->nullable();
                $table->decimal('late_fee', 18, 2)->nullable();
                $table->datetime('due_date')->nullable();

                $table->string('payment_status')->nullable(); // PENDING, PAID

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_repayment_schedules');
    }
};
