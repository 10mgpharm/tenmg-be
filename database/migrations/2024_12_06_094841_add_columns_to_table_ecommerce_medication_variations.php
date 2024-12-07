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
        Schema::table('ecommerce_medication_variations', function (Blueprint $table) {

            if (Schema::hasColumn('ecommerce_medication_variations', 'presentation')) {
                $table->string('presentation')->nullable()->change();
            }

            if (Schema::hasColumn('ecommerce_medication_variations', 'package_per_roll')) {
                $table->integer('package_per_roll')->nullable()->change();
            }

            if (! Schema::hasColumn('ecommerce_medication_variations', 'ecommerce_presentation_id')) {
                $table->foreignId('ecommerce_presentation_id')->nullable()->after('ecommerce_medication_type_id')->constrained('ecommerce_presentations')->nullOnDelete();
            }

            if (! Schema::hasColumn('ecommerce_medication_variations', 'ecommerce_package_id')) {
                $table->foreignId('ecommerce_package_id')->nullable()->after('ecommerce_presentation_id')->constrained('ecommerce_packages')->nullOnDelete();
            }

            if (! Schema::hasColumn('ecommerce_medication_variations', 'active')) {
                $table->boolean('active')->default(false);
            }

            if (! Schema::hasColumn('ecommerce_medication_variations', 'status')) {
                $table->enum('status', array_map(fn ($status) => $status->value, StatusEnum::cases()))
                ->default(StatusEnum::PENDING->value)
                ->nullable();
            }
            if (! Schema::hasColumn('ecommerce_medication_variations', 'status_comment')) {
                $table->text('status_comment')->nullable();
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_medication_variations', function (Blueprint $table) {
            //
        });
    }
};
