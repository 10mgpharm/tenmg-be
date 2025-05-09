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
        Schema::create('tenmg_transaction_histories', function (Blueprint $table) {
            $table->id();
            $table->string('identifier');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['CREDIT', 'DEBIT']);
            $table->string('transaction_group');
            $table->string('description');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenmg_transaction_histories');
    }
};
