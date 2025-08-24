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
        Schema::table('projects', function (Blueprint $table) {
            $table->enum('status', ['active', 'completed', 'on_hold', 'cancelled'])->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->tinyInteger('priority')->default(3);
        });

        DB::table('projects')
            ->whereNull('start_date')
            ->update(['start_date' => now()->format('Y-m-d')]);

        Schema::table('projects', function (Blueprint $table) {
            $table->date('start_date')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['status', 'start_date', 'end_date', 'budget', 'priority']);
        });
    }
};
