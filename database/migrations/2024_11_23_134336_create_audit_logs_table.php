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
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();

                // References to the businesses involved in the log
                $table->foreignId('creatable_business_id')->nullable()
                    ->constrained('businesses')->nullOnDelete();
                $table->foreignId('targetable_business_id')->nullable()
                    ->constrained('businesses')->nullOnDelete();

                // Event and action information
                $table->string('event'); // E.g., "oauth_access.destroy"
                $table->string('action'); // E.g., "Revoked token for user X"

                // Optional detailed description
                $table->text('description')->nullable();

                // Logging metadata
                $table->ipAddress('ip_address');
                $table->text('user_agent')->nullable();
                $table->enum('crud_type', ['CREATE', 'READ', 'UPDATE', 'DELETE', 'PATCH'])
                    ->comment('Indicates the CRUD operation associated with the log');

                // Polymorphic relationships for creatable and targetable models
                $table->morphs('creatable');
                $table->morphs('targetable');

                // Timestamps
                $table->timestamps();

                // Indexing for performance
                $table->index(['event', 'action']); // For filtering logs by event and action
                $table->index(['creatable_business_id', 'targetable_business_id']);
                $table->index(['ip_address']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
