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
        Schema::create('credit_txn_history_evaluations', function (Blueprint $table) {
            $table->id();

            $table->string('identifier')->unique(); // system generated e.g EVAL-VENDOR_CODE-YEARMONTHDAY-PRIMARY_ID i.e EVAL-10MG-20240901-23

            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade'); // BusinessType::VENDOR
            $table->foreignId('customer_id')->constrained('credit_customers')->onDelete('cascade');

            $table->foreignId('transaction_file_id')->nullable()->constrained('files')->onDelete('set null');
            $table->enum('file_format', ['CSV', 'JSON', 'EXCEL'])->default('JSON');
            $table->enum('upload_source', ['DASHBOARD', 'API'])->default('DASHBOARD');

            // PENDING- when file is uploaded, IN_PROGRESS- when evaluation is running, FAILED- if evaluation failed, DONE- if evaluation is successful
            $table->enum('status', ['PENDING', 'IN_PROGRESS', 'FAILED', 'DONE'])->default('PENDING');
            $table->json('evaluation_result')->nullable()->comment('store result of the evaluation from AI-model response');

            $table->foreignId('created_by_id')->constrained('business_users')->onDelete('cascade');

            $table->unique(['business_id', 'customer_id', 'transaction_file_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_txn_history_evaluations');
    }
};
