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
        if (! Schema::hasTable('credit_affordabilities')) {
            Schema::create('credit_affordabilities', function (Blueprint $table) {
                $table->id();

                // score are set in bound if a customer credit score is 45%
                // system checks affordability where lower_bound >= 45 && upper_bound < 45
                $table->integer('lower_bound');
                $table->integer('upper_bound');

                // affordability amount are set as suggestion and user loan can only approve if
                // the amount they requested falls within the %percentage lower_bound and upper_bound
                $table->decimal('base_amount', 18, 2);
                $table->decimal('max_amount', 18, 2);

                $table->boolean('active')->default(true)->comment('determine if the affordability is active or not');

                $table->boolean('is_default')->default(false)->comment('fallback when all affordability for a credit score is not found');

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_affordabilities');
    }
};
