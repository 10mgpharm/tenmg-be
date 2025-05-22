<?php

use App\Enums\StatusEnum;
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
        Schema::table('ecommerce_products', function (Blueprint $table) {

            if (! Schema::hasColumn('ecommerce_products', 'description')) {
                $table->text('description')->nullable()->after('slug');
            }
            
            if (Schema::hasColumn('ecommerce_products', 'min_delivery_duration')) {
                $table->integer('min_delivery_duration')->nullable()->change();
            }

            if (Schema::hasColumn('ecommerce_products', 'max_delivery_duration')) {
                $table->integer('max_delivery_duration')->nullable()->change();
            }

            if (! Schema::hasColumn('ecommerce_products', 'ecommerce_measurement_id')) {
                $table->foreignId('ecommerce_measurement_id')->nullable()->after('ecommerce_variation_id')->constrained('ecommerce_measurements')->nullOnDelete();
            }

            if (! Schema::hasColumn('ecommerce_products', 'ecommerce_presentation_id')) {
                $table->foreignId('ecommerce_presentation_id')->nullable()->after('ecommerce_measurement_id')->constrained('ecommerce_presentations')->nullOnDelete();
            }

            if (! Schema::hasColumn('ecommerce_products', 'ecommerce_package_id')) {
                $table->foreignId('ecommerce_package_id')->nullable()->after('ecommerce_presentation_id')->constrained('ecommerce_packages')->nullOnDelete();
            }

            if (! Schema::hasColumn('ecommerce_products', 'weight')) {
                $table->string('weight')->nullable()->after('ecommerce_package_id');
            }

            if (! Schema::hasColumn('ecommerce_products', 'value_strength')) {
                $table->string('value_strength')->nullable()->after('weight');
            }

            if (! Schema::hasColumn('ecommerce_products', 'active')) {
                $table->boolean('active')->default(false);
            }

            if (! Schema::hasColumn('ecommerce_products', 'status')) {
                $table->enum('status', array_map(fn ($status) => $status->value, StatusEnum::cases()))
                ->default(StatusEnum::PENDING->value)
                ->nullable();
            }
            if (! Schema::hasColumn('ecommerce_products', 'status_comment')) {
                $table->text('status_comment')->nullable();
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_products', function (Blueprint $table) {
            //
        });
    }
};
