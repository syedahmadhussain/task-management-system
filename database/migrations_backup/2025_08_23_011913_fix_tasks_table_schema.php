<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');

            $table->unsignedBigInteger('assigned_to')->nullable()->after('project_id');

            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');

            $table->string('priority_temp')->default('medium')->after('assigned_to');

            $table->string('status_temp')->default('pending')->after('priority_temp');

            $table->unsignedBigInteger('org_id')->nullable()->after('user_id');
            $table->text('completion_notes')->nullable()->after('completed_at');
            $table->json('tags')->nullable()->after('completion_notes');

            $table->foreign('org_id')->references('id')->on('organizations')->onDelete('cascade');
        });

        // Copy user_id data to assigned_to
        DB::statement('UPDATE tasks SET assigned_to = user_id');

        // Copy priority data (convert numbers to strings)
        DB::statement("
            UPDATE tasks SET priority_temp = CASE
                WHEN priority = 1 OR priority <= 1 THEN 'low'
                WHEN priority = 2 THEN 'medium'
                WHEN priority = 3 THEN 'high'
                WHEN priority >= 4 THEN 'urgent'
                ELSE 'medium'
            END
        ");

        // Copy status data
        DB::statement('UPDATE tasks SET status_temp = status');

        // Set org_id based on project's organization
        DB::statement('
            UPDATE tasks t
            JOIN projects p ON t.project_id = p.id
            SET t.org_id = p.org_id
        ');

        // Drop old columns and rename new ones
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['priority', 'status']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->renameColumn('priority_temp', 'priority');
            $table->renameColumn('status_temp', 'status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Remove added columns
            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['org_id']);
            $table->dropColumn([
                'description',
                'assigned_to',
                'org_id',
                'completion_notes',
                'tags'
            ]);

            // Restore original priority as tinyint
            $table->tinyInteger('priority')->default(3)->after('name');

            // Restore original status as enum
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending')->after('due_date');
        });
    }
};
