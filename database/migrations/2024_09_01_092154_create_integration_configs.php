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
        // this is for only BusinessType::VENDOR
        Schema::create('integration_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');

            $table->enum('mode', ['TEST', 'LIVE'])->comment('determines the mode of the integration, if on test mode use test api keys and vice-versa');

            $table->string('test_public_key');
            $table->string('test_secret_key');
            $table->text('test_webhook_url')->nullable();
            $table->text('test_callback_url')->nullable();
            $table->string('test_ip_whitelist')->nullable();

            $table->string('live_public_key');
            $table->string('live_secret_key');
            $table->text('live_webhook_url')->nullable();
            $table->text('live_callback_url')->nullable();
            $table->string('live_ip_whitelist')->nullable();

            // this field determine if the vendor is giving their customer loan directly or 10mg gives their customer loan on behalf of vendor
            $table->enum('provider_mode', ['10MG', 'VENDOR'])->default('10MG')->comment('when set to 10MG, its 10mg admin that will have access to approve or reject loan and also create offer else the vendor perform the action');

            // this field allows vendor to configure if they want to use their own external integration to collect loan repayment, default to internal if the provider is 10mg
            // if vendor is the loan_provider and does not have their own integration, they can choose to use 10mg loan repayment integration. 10mg will help them settle it to their account linked
            $table->enum('collection_mode', ['INTERNAL', 'EXTERNAL'])->default('10MG')->comment('if loan_provider is VENDOR, collection can be outside or within the system. But if 10mg collection is default to internal');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_settings');
    }
};
