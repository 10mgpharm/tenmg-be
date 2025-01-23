<?php

use App\Enums\DiscountCustomerLimitEnum;
use App\Enums\DiscountApplicationMethodEnum;
use App\Enums\DiscountTypeEnum;
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
        Schema::create('ecommerce_discounts', function (Blueprint $table) {
            $table->id();

            // Defines whether the discount is applied manually via a coupon code or automatically
            $table->enum('application_method', array_map(fn($case) => $case->value, DiscountApplicationMethodEnum::cases()))->nullable();

            // Unique coupon code, unique per business, nullable if the discount is automatic
            $table->string('coupon_code')->nullable();
            
            // Discount type: fixed amount or percentage
            $table->enum('type', array_map(fn($case) => $case->value, DiscountTypeEnum::cases()))->nullable();

            // Discount value: the percentage (0-100) or fixed amount
            $table->decimal('amount', 10, 2)->nullable();

            // JSON field for specific products or categories the discount applies to
            $table->json('applicable_products')->nullable();

            // Limits on how many times a customer can use the discount
            $table->enum('customer_limit', array_map(fn($case) => $case->value, DiscountCustomerLimitEnum::cases()))->nullable();

            // Minimum cart value for the discount to apply
            $table->decimal('minimum_order_amount', 10, 2)->nullable();

            // Maximum discount value (applicable only for percentage discounts)
            $table->decimal('maximum_discount_amount', 10, 2)->nullable();

            // Time constraints for the discount
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();

            // Foreign key to associate discounts with businesses
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();

            // Tracks the user who created or updated the discount
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Ensures that each coupon code is unique per business
            $table->unique(['coupon_code', 'business_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_discounts');
    }
};
