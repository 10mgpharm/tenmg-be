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
        if(!Schema::hasColumn('app_notifications', 'is_lender')) {
            Schema::table('app_notifications', function (Blueprint $table) {
                $table->boolean('is_lender')->default(false)->after('is_vendor');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(Schema::hasColumn('app_notifications', 'is_lender')) {
            Schema::table('app_notifications', function (Blueprint $table) {
                $table->dropColumn('is_lender');
            });
        }
    }
};
