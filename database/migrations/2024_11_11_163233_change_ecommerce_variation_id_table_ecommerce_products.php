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
        if(Schema::hasColumn('ecommerce_products', 'ecommerce_variation_id')){
            Schema::table('ecommerce_products', function (Blueprint $table) {
                $table->foreignId('ecommerce_variation_id')->change()->nullable()->constrained('ecommerce_medication_variations')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
    }
};
