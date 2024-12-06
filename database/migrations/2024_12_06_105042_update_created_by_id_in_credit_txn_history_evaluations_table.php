<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('credit_txn_history_evaluations', function (Blueprint $table) {
            // Drop the old foreign key constraint
            $table->dropForeign(['created_by_id']); // Drops the foreign key referencing `business`

            // Modify the foreign key to reference the `users` table
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('credit_txn_history_evaluations', function (Blueprint $table) {
            // Drop the foreign key referencing `users`
            $table->dropForeign(['created_by_id']);

            // Revert the foreign key to reference the `business` table
            $table->foreign('created_by_id')->references('id')->on('businesses')->onDelete('cascade');
        });
    }
};
