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

        if (!Schema::hasColumn('ecommerce_payment_methods', 'slug')) {
            Schema::table('ecommerce_payment_methods', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
            });
        }

        if (!Schema::hasColumn('ecommerce_payment_methods', 'status')) {
            Schema::table('ecommerce_payment_methods', function (Blueprint $table) {
                $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_payment_methods', function (Blueprint $table) {
            $table->dropColumn('slug');
            $table->dropColumn('status');
        });
    }
};
