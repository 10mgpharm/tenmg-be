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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->string('name');
            $table->foreignId('logo_id')->nullable()->constrained('files')->onDelete('set null');
            $table->enum('type', ['ADMIN', 'SUPPLIER', 'VENDOR', 'CUSTOMER_PHARMACY', 'CUSTOMER_HOSPITAL'])->nullable();
            $table->text('address');
            $table->string('contact_person');
            $table->string('contact_phone');
            $table->string('contact_email');
            $table->boolean('active')->default(true);
            $table->enum('status', ['PENDING_VERIFICATION', 'VERIFIED', 'SUSPENDED', 'BANNED'])->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
