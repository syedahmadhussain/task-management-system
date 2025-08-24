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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'completed', 'on_hold', 'cancelled'])->default('active');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('org_id')->constrained('organizations')->onDelete('cascade');
            $table->decimal('budget', 12, 2)->nullable();
            $table->tinyInteger('priority')->default(3);
            $table->timestamps();

            $table->index(['org_id', 'status']);
            $table->index(['manager_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
