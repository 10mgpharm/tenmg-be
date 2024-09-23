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
        if (!Schema::hasColumn('businesses', 'cac_document_id')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->foreignId('cac_document_id')->nullable()
                ->after('expiry_date')
                ->constrained('file_uploads')
                ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('businesses', 'cac_document_id')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('cac_document_id');
            });
        }
    }
};
