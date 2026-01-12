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
        Schema::create('tenmg_credit_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_id')->unique();
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->string('sdk_url', 1024)->nullable();
            $table->string('initiated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenmg_credit_requests');
    }
};
