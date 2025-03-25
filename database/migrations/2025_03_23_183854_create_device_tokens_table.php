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
        if(Schema::hasColumn('users', 'fcm_token')){
            Schema::dropColumns('users', 'fcm_token');
        }
        if(!Schema::hasTable('device_tokens')){

            Schema::create('device_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('fcm_token');
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['fcm_token', 'user_id',]);
            });

        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
