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
        if(!Schema::hasColumns('businesses', ['license_verification_status', 'license_verification_comment'])){
            Schema::table('businesses', function (Blueprint $table) {
                $table->enum('license_verification_status', ['REJECTED', 'APPROVED', 'PENDING'])->nullable()->after('expiry_date');
                $table->string('license_verification_comment')->nullable()->after('license_verification_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(Schema::hasColumns('businesses', ['license_verification_status', 'license_verification_comment'])){
            Schema::table('businesses', function (Blueprint $table) {
                $table->dropColumn(['license_verification_status', 'license_verification_comment']);
            });
        }
    }
};
