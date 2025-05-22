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
        if(!Schema::hasTable('ecommerce_packages')){
            Schema::create('ecommerce_packages', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
    
                $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
    
                $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
    
                $table->boolean('active')->default(false);
    
                $table->enum('status', array_map(fn ($status) => $status->value, StatusEnum::cases()))
                ->default(StatusEnum::PENDING->value)
                ->nullable();
                $table->text('status_comment')->nullable();
    
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_packages');
    }
};
