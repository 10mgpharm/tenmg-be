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
        Schema::create('ecommerce_payouts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('initiated_by_id')->constrained('users')->nullOnDelete()->index();
            $table->foreignId('recipient_business_id')->constrained('businesses')->cascadeOnDelete()->index();
            $table->foreignId('recipient_bank_id')->constrained('bank_accounts')->nullOnDelete()->index();

            $table->string('reference')->nullable();
            $table->string('txn_id')->unique()->index();
            
            $table->enum('payout_type', ['TRANSFER', 'WITHDRAWAL', 'REFUND']);
            $table->string('channel')->index();
            $table->enum('status', ['PENDING', 'PROCESSING', 'COMPLETED', 'FAILED', 'REVERSED'])->default('PENDING')->index();
            
            $table->decimal('amount_sent', 18, 2);
            $table->decimal('channel_fee', 18, 2)->default(0.00);
            $table->json('channel_response')->nullable();
            $table->string('channel_reference')->nullable()->index();
        
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_payouts');
    }
};
