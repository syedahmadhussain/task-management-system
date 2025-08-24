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
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->renameColumn('manager_id', 'assigned_user_id');
            $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop the assigned_user_id foreign key constraint
            $table->dropForeign(['assigned_user_id']);
            // Rename back to manager_id
            $table->renameColumn('assigned_user_id', 'manager_id');
            // Re-add the original foreign key constraint
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
        });
    }
};
