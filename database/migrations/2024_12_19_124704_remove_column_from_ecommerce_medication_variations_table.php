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
        Schema::table('ecommerce_medication_variations', function (Blueprint $table) {
            $table->dropForeign('ecommerce_medication_variations_ecommerce_package_id_foreign');
            $table->dropColumn('ecommerce_package_id');
            $table->dropColumn('presentation');
            $table->dropColumn('strength');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_medication_variations', function (Blueprint $table) {

            $table->string('ecommerce_package_id')->nullable();
            $table->string('presentation')->nullable();
            $table->string('strength')->nullable();

        });
    }
};
