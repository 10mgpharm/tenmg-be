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
        if (! Schema::hasColumns('users', ['use_two_factor', 'two_factor_secret'])) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('use_two_factor')->default(false)->after('active');
                $table->string('two_factor_secret')->nullable()->after('use_two_factor');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumns('users', ['use_two_factor', 'two_factor_secret'])) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['use_two_factor', 'two_factor_secret']);
            });
        }
    }
};
