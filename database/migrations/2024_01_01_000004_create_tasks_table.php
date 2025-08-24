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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->decimal('estimated_time', 5, 2); // hours with 2 decimal places
            $table->date('due_date');
            $table->string('status')->default('pending'); // pending, in_progress, completed
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_notes')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'completed_at']);
            $table->index(['due_date', 'priority']);
            $table->index(['assigned_to', 'status']);
            $table->index(['org_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
