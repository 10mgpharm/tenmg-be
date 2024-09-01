<?php

use App\Enums\BusinessType;
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
        Schema::create('credit_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade'); // BusinessType::VENDOR
            $table->foreignId('avatar_id')->nullable()->constrained('files')->onDelete('set null');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('identifier')->unique(); // system generated e.g CUS-VENDOR_CODE-YEARMONTHDAY-PRIMARY_ID i.e CUS-10MG-20240901-230, CUS-TUYIL-20240901-19
            $table->boolean('active')->default(true);

            $table->unique(['identifier', 'email', 'business_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_customers');
    }
};
