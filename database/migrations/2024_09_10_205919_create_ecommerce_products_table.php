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
        Schema::create('ecommerce_products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete()->index();
            $table->foreignId('ecommerce_brand_id')->nullable()->constrained('ecommerce_brands')->nullOnDelete()->index();
            $table->foreignId('thumbnail_file_id')->nullable()->constrained('files')->nullOnDelete()->index();
            $table->foreignId('ecommerce_medication_type_id')->constrained('ecommerce_medication_types')->cascadeOnDelete()->index();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete()->index();
            $table->foreignId('ecommerce_variation_id')->constrained('ecommerce_medication_variations')->cascadeOnDelete()->index();

            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete()->index();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete()->index();

            $table->integer('quantity');
            $table->decimal('actual_price', 18, 2);
            $table->decimal('discount_price', 18, 2)->nullable();
            $table->integer('min_delivery_duration');
            $table->integer('max_delivery_duration');

            $table->timestamp('expired_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_products');
    }
};
