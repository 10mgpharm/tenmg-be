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
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('department')->nullable();
            $table->json('employment_type')->nullable();
            $table->longText('mission')->nullable();
            $table->longText('responsibilities')->nullable();
            $table->json('requirements')->nullable();
            $table->longText('compensation')->nullable();
            $table->longText('flexibility')->nullable();
            $table->longText('how_to_apply')->nullable();
            $table->string('apply_url')->nullable();
            $table->string('location_type')->default('REMOTE');
            $table->longText('about_company')->nullable();
            $table->string('status')->default('PUBLISHED');
            $table->timestamps();
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->integer('expected_salary')->nullable();
            $table->enum('salary_type', ['bi-weekly', 'monthly', 'annually']);
            $table->string('notice_period')->nullable();
            $table->string('referral_source')->nullable();
            $table->string('resume')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_listings');
    }
};
