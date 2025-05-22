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
        Schema::table('ecommerce_payments', function (Blueprint $table) {

            if (!Schema::hasColumn('ecommerce_payments', 'status')) {
                $table->enum('status', ['initiated', 'success', 'pending', 'failed', 'abandoned'])->after('id');
            }

            if (!Schema::hasColumn('ecommerce_payments', 'reference')) {
                $table->string('reference')->unique()->after('status');
            }

            if (!Schema::hasColumn('ecommerce_payments', 'external_reference')) {
                $table->string('external_reference')->nullable()->after('reference');
            }

            if (!Schema::hasColumn('ecommerce_payments', 'customer_id')) {
                $table->foreignId('customer_id')->constrained('users')->onDelete('cascade')->after('external_reference');
            }

            if (!Schema::hasColumn('ecommerce_payments', 'amount')) {
                $table->decimal('amount', 18, 2)->after('customer_id');
            }

            if (!Schema::hasColumn('ecommerce_payments', 'fee')) {
                $table->decimal('fee', 18, 2)->default(0)->after('amount');
            }

            if (!Schema::hasColumn('ecommerce_payments', 'total_amount')) {
                $table->decimal('total_amount', 18, 2)->after('fee');
            }

            if (!Schema::hasColumn('ecommerce_payments', 'comment')) {
                $table->text('comment')->nullable()->after('total_amount');
            }

            if (!Schema::hasColumn('ecommerce_payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('comment');
            }

            if (!Schema::hasColumn('ecommerce_payments', 'currency')) {
                $table->string('currency')->after('paid_at');
            }

            if (!Schema::hasColumn('ecommerce_payments', 'channel')) {
                $table->string('channel')->nullable()->after('currency');
            }

            if (!Schema::hasColumn('ecommerce_payments', 'meta')) {
                $table->json('meta')->nullable()->after('channel');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_payments', function (Blueprint $table) {
            //
        });
    }
};
