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
        Schema::create('credit_applications', function (Blueprint $table) {
            $table->id();

            $table->string('identifier')->nullable()->unique(); // system generated e.g APP-VENDOR_CODE-YEARMONTHDAY-PRIMARY_ID i.e APP-10MG-20240901-23
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade'); // BusinessType::VENDOR
            $table->foreignId('customer_id')->constrained('credit_customers')->onDelete('cascade');

            $table->decimal('requested_amount', 18, 2)->nullable();
            $table->decimal('interest_amount', 18, 2)->nullable();
            $table->decimal('total_amount', 18, 2)->nullable();
            $table->float('interest_rate')->nullable(); // fetch from system settings
            $table->string('duration_in_months')->nullable();

            $table->enum('source', ['DASHBOARD', 'API']);

            $table->string('status')->nullable();

            $table->unique(['business_id', 'customer_id', 'identifier']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_applications');
    }
};
