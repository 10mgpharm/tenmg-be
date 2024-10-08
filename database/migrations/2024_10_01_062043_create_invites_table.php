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
        Schema::dropIfExists('invites');
        Schema::create('invites', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email');
            $table->enum('status', ['INVITED', 'ACCEPTED', 'REJECTED', 'REMOVED'])->default('INVITED');
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->string('invite_token')->unique()->index();
            $table->timestamp('expires_at');
            $table->timestamps();

            // Unique constraint per business for invited, accepted
            $table->unique(['email', 'business_id'], 'unique_email_per_business')->whereNotIn('status', ['REJECTED', 'REMOVED']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invites');
    }
};
