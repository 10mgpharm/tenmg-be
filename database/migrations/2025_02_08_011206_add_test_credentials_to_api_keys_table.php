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
        if (Schema::hasTable('api_keys')) {
            Schema::table('api_keys', function (Blueprint $table) {

                $table->string('encryption_key')->unique()->nullable()->after('callback_url');
                $table->string('test_encryption_key')->unique()->nullable()->after('callback_url');

                $table->string('test_callback_url')->nullable()->after('callback_url');
                $table->string('test_webhook_url')->nullable()->after('callback_url');

                $table->string('test_key')->unique()->nullable()->after('callback_url');
                $table->string('test_secret')->unique()->nullable()->after('callback_url');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('api_keys')) {
            Schema::table('api_keys', function (Blueprint $table) {
                $table->dropColumn([
                    'test_key',
                    'test_secret',
                    'test_webhook_url',
                    'test_callback_url',
                    'encryption_key',
                    'test_encryption_key',
                ]);
            });
        }
    }
};
