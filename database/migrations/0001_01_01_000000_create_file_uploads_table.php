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
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('model_id'); // e.g 1
            $table->string('model_type'); // e.g \App\Models\Business, \App\Models\User

            $table->string('name');
            $table->string('url');
            $table->string('path');
            $table->string('mime_type');
            $table->string('extension');
            $table->integer('size')->nullable();

            $table->index(['model_id', 'model_type'], 'files_model_id_model_type_index');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_uploads');
    }
};
